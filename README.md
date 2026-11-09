# AfriBolt Technologies: Online Learning Platform

Plain-PHP MVC (no framework, no Composer): HTML / Tailwind / JS / PHP 8.1+ / MySQL 8.0.16+ via MySQLi.
Built from the project requirements doc and `live_online_educational_platform_schema.sql` (included in `database/schema.sql`).

## Quick start (local)

1. **Database**
   ```bash
   mysql -u root -p < database/schema.sql
   mysql -u root -p < database/migrations/001_create_enquiries.sql
   mysql -u root -p < database/migrations/002_announcements_instructor_author.sql
   mysql -u root -p < database/migrations/003_self_paced_learning.sql
   mysql -u root -p < database/migrations/004_jobs.sql
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

## Jobs board (Nigeria) with AI

`/jobs` lists openings collected automatically, cleaned and categorised by AI, with scam checks, saved jobs and personal recommendations. The site is now positioned around **learning at your own pace first**, with live cohorts as the alternative.

**How it works**
1. **Collect:** `cron/jobs_sync.php` (every 30 min) pulls from every enabled source in *Admin > Job sources*: RSS/Atom feeds, the Jooble API, any JSON API (field-mapped in settings), or pages exposing schema.org `JobPosting` data. Requests identify themselves (`AfriBoltJobsBot`), honour robots.txt, rate-limit per host and refuse private network addresses.
2. **Clean:** HTML is stripped, places/states, pay, type and level are parsed with rules, and duplicates (same title + company + state) are skipped.
3. **AI:** Claude (`ANTHROPIC_API_KEY`) rewrites a short plain-text summary in its own words, normalises the title, picks a category, extracts skills, salary, deadline and state, and gives a **scam-risk** and **quality** score. Job text is treated as untrusted data (prompt-injection safe) and every field is validated before saving. Rules can only *raise* the scam score.
4. **Publish or hold:** low-risk, good-quality jobs publish automatically; risky/low-quality ones wait in *Admin > Jobs* for review. Without an AI key, rule-based cleaning is used and far fewer jobs auto-publish.
5. **Recommend:** each learner is scored against their chosen interests/skills and the topics of the courses they take; Claude writes a one-line "why this fits you" and a tip for the top matches (only interests/skills are sent, never names or emails). Job pages also suggest your courses that build the skills a job asks for. `cron/jobs_digest.php` emails opted-in learners.

**Testing a source:** in *Admin > Job sources* press **Test** to see exactly what a source returns (HTTP status, counts, first posting) without saving anything. For Jooble the system tries several endpoints and locations and tells you which worked. If Jooble returns nothing for Nigeria, check that the key is enabled for Nigeria in your Jooble account, or add another source: a RapidAPI **JSearch** example (covers Nigeria) is in the settings examples, and any RSS feed or JSON API can be added the same way.

**Important:** only add sources whose terms allow automated access (official APIs, public feeds, or written permission). The site shows our own short summary plus a link to the original posting and never republishes full job text. Set `ANTHROPIC_API_KEY` (and optionally `JOOBLE_API_KEY`) in `.env`; costs are capped by `ai_max_per_run` in `config/jobs.php`. Run `php database/seed.php --sample` for sample jobs and a disabled Jooble source.

## Self-paced courses (Udemy-style)

Every course has a **delivery mode**: live cohorts, self-paced (on demand), or both.

- **Catalog and course page:** search, filters (category, level, price, rating, live vs self-paced), sort; course page with promo video, what you'll learn, curriculum accordion with free previews, instructor, reviews, sale countdown and a sticky buy card.
- **Learner experience:** `My learning` with progress; a lesson player (video via YouTube/Vimeo/direct MP4, articles, quizzes with a 70% pass mark, downloadable files); progress and resume saved automatically; reviews and ratings; a printable **certificate** with a public verification link (`/certificate/CODE`).
- **Access:** free enrol, one-time purchase (same payment provider, verified server-side), admin grant, or automatically for confirmed live-cohort students (switchable per course). Lifetime access.
- **Admin:** Courses -> *Curriculum* builds sections and lessons (reorder, free preview, publish/draft), uploads downloadable files (stored privately), and quiz questions. *Self-paced learners* grants/revokes access; *Reviews* hides abusive reviews. Payments, reports and CSV exports include course purchases.
- **Not included yet (good next steps):** instructors authoring their own courses, learner Q&A, coupons, a shopping cart/wishlist, video upload/streaming hosting (use YouTube unlisted or Vimeo for now), subtitles.

`php database/seed.php --sample` also creates a sample self-paced course with article and quiz lessons. Add a video lesson from the Curriculum screen.

## Policy pages (FAQ, Privacy, Terms, Refunds)

Drafted from how the platform actually behaves (seat confirmed only after payment verification, join window, waitlist, card data never stored). The *business* terms are proposals, **not legal advice**: refund windows and percentages, retention periods, minimum age, governing law, hosting location. While `LEGAL_DRAFT=true` in `.env`, each of these is highlighted in yellow with a "Draft for review" banner. Get the client (and ideally a Nigerian lawyer) to confirm them, edit the wording in `app/Views/public/pages/`, then set `LEGAL_DRAFT=false` to remove the highlights and banner. FAQ answers live in `PageController::faq()`.

## Before going live

- Document root must be `public/` (the root `.htaccess` is only a fallback). Serve over HTTPS.
- `APP_ENV=production`, `APP_DEBUG=false`, change the seeded admin password, delete the sample cohort.
- Replace TODO policy/FAQ pages with approved text. Confirm: gateway + currencies, join window, refund policy, email provider (`MailService::deliver`).
- Add login rate-limiting by IP (current throttle is per-session) and role checks per admin role (`Auth::adminHasRole`).
- Back up the database and test a restore.
