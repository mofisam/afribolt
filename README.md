# AfriBolt Technologies: Online Learning Platform

Plain-PHP MVC (no framework, no Composer): HTML / Tailwind / JS / PHP 8.1+ / MySQL 8.0.16+ via MySQLi.
Built from the project requirements doc and `live_online_educational_platform_schema.sql` (included in `database/schema.sql`).

## Quick start (local)

1. **Database**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/migrations/001_create_enquiries.sql
   mysql -u root -p < database/migrations/002_announcements_instructor_author.sql
   ```
2. **Config**: `cp .env.example .env`, then set `DB_*`, `APP_URL`, and `SEED_ADMIN_PASSWORD`.
3. **Seed**
   ```bash
   php database/seed.php --sample      # admin + gateway row + a sample course/cohort (drop --sample for production)
   ```
4. **Run**
   ```bash
   php -S localhost:8000 -t public
   ```
   - Site: http://localhost:8000
   - Admin: http://localhost:8000/admin/login (email/password from `.env`)
5. **Styles**: in development the layouts load the Tailwind CDN. For production download the
   [standalone Tailwind CLI](https://github.com/tailwindlabs/tailwindcss/releases) and run:
   `./tailwindcss -i resources/css/input.css -o public/assets/css/tailwind.css --minify`
   (then set `APP_ENV=production`).

## How a request flows

`public/index.php` -> `App` (config, session, errors) -> `Router` (routes/*.php, CSRF, middleware) ->
`Controller` (thin) -> `Service` (business rules) -> `Model` (SQL only) -> `View` inside a layout.

| Folder | Purpose |
|---|---|
| `app/Core` | The mini-framework: Router, Request/Response, Database (MySQLi), Model base, View, Session, Csrf, Validator, Auth |
| `app/Controllers` | Public, Auth, Student, Admin, Instructor, Webhook |
| `app/Services` | Registration policy, enrolment (transaction-safe capacity), payments, webhooks, join access, countdowns, notifications, mail, audit |
| `app/Models` | One per table, all prepared statements |
| `app/Views` | layouts, partials, pages, emails, errors |
| `cron/` | Email queue, class reminders, releasing unpaid seats, cohort status updates |

## Rules enforced in code (from the requirements)

- All timestamps stored in **UTC**; converted for display with `local_date()` (`app/Helpers/date.php`). Countdowns are computed in the browser from a UTC target (never stored as seconds).
- Capacity and duplicate checks run inside a DB transaction with the cohort row locked (`EnrolmentService`).
- A paid enrolment is only confirmed after **server-side verification** with the provider (`PaymentService::verifyAndConfirm`), used by both the browser callback and the webhook.
- Webhooks are idempotent via `payment_webhook_events` (unique gateway + event id) and verified by HMAC signature.
- Meeting URLs never appear in page HTML. Only `POST /student/sessions/{id}/join` returns one, after enrolment + join-window checks (`JoinAccessService`).
- Passwords use `password_hash`; every query is a prepared statement; every view output goes through `e()`.

## Cron (UTC server clock is fine)

```
* * * * *     php /path/to/cron/send_queued_emails.php
*/10 * * * *  php /path/to/cron/session_reminders.php
*/15 * * * *  php /path/to/cron/expire_pending_payments.php
*/15 * * * *  php /path/to/cron/update_cohort_statuses.php
```
Set `APP_URL` so links inside emails are correct when sent from cron.

## Payments

`app/Services/Gateways/PaystackGateway.php` is an **example adapter** (the gateway is still to be confirmed with the client).
Add the keys to `.env`, and point the provider's webhook to `https://YOUR-DOMAIN/webhooks/paystack`.

## What is built

Public site, student accounts and dashboard, enrolment + payment + webhook flow, all services, cron jobs, seed, **and the full admin area and instructor portal**:

| Admin module | What it does | Roles (super admin always allowed) |
|---|---|---|
| Cohorts | Create/edit with dates, registration window, late registration, capacity, waitlist, fee, payment rule; status changes; assign instructors; audited | admin, content_manager |
| Class sessions | Add one or a weekly series, meeting link/ID/encrypted passcode, join-window minutes, reschedule/postpone/cancel (notifies students) | admin, content_manager |
| Courses, Categories | CRUD, publish/unpublish/archive, thumbnail upload | admin, content_manager |
| Instructors | Accounts + assignment (instructor portal login at `/instructor/login`) | admin |
| Students | Search, profile, enrolments, suspend/activate/close | admin, finance_manager, content_manager (view); admin changes status |
| Enrolments | Filters, manual confirm (note required), promote from waitlist, cancel, complete, notes | admin, finance_manager |
| Payments | Filters, provider response, re-verify with provider, record a manual refund | admin, finance_manager (refund: finance_manager) |
| Announcements | Per-cohort or global, schedule/expiry, notifies the cohort on first publish | admin, content_manager |
| Attendance | Mark per session (joins via the platform are pre-filled) | admin, content_manager (and instructors for their cohorts) |
| Reports | Registrations by status, revenue by month, cohort fill rate, CSV export | admin, finance_manager |
| Audit log, Email log | Read-only history; retry failed emails | admin |
| Gateways | Enable/disable, public key, currencies, webhook URL + recent webhook events | finance_manager |
| Settings | `platform_settings` editor (default timezone/currency feed new cohorts/sessions) | admin |
| Admin users | Create admins and assign roles | super_admin only |

Admin sidebar: grouped sections with icons, role-aware links, "needs attention" badges (enrolments to review, classes this week with no meeting link, failed emails), a mobile drawer and a collapsible icon rail (remembered per browser).

Instructor portal (`/instructor`): dashboard with stats and a "no meeting link" warning; per cohort they can add/edit meeting link, ID, passcode and recording link, start/finish a class, mark attendance, export the roster (CSV) and see each student's attendance rate; post announcements to their own cohorts (students are notified; they can archive their own posts); edit their profile and change their password. Instructors cannot change class times, cancel classes, fees or enrolments. Those stay with admins.

New in this update: set `APP_KEY` in `.env` (needed only if you store meeting passcodes):
`php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"`

### Known limits (by design, until confirmed with the client)
- "Record a refund" only records a refund you already paid out; it does not call the gateway.
- Deposit/instalment payment plans are not offered at checkout (the cohort form hides that setting).
- Instructors have no self-service password reset yet (admin sets the password).

## Before going live

- Document root must be `public/` (the root `.htaccess` is only a fallback). Serve over HTTPS.
- `APP_ENV=production`, `APP_DEBUG=false`, change the seeded admin password, delete the sample cohort.
- Replace TODO policy/FAQ pages with approved text. Confirm: gateway + currencies, join window, refund policy, email provider (`MailService::deliver`).
- Add login rate-limiting by IP (current throttle is per-session) and role checks per admin role (`Auth::adminHasRole`).
- Back up the database and test a restore.
