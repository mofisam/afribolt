<?php
namespace App\Controllers\Admin;

use App\Core\{Auth, Controller, Database};
use App\Models\PlatformSetting;

class DashboardController extends Controller
{
    public function index(): void
    {
        $admin = Auth::user('admin');
        $isAdmin = Auth::adminHasRole('super_admin', 'admin');
        $money = $isAdmin || Auth::adminHasRole('finance_manager');
        $teach = $isAdmin || Auth::adminHasRole('content_manager');
        $cur = (string) (PlatformSetting::value('default_currency') ?: 'NGN');

        $monthStart = gmdate('Y-m-01 00:00:00');
        $lastMonthStart = gmdate('Y-m-01 00:00:00', strtotime('first day of last month'));
        $paidStatuses = "('successful','partially_refunded','refunded')";
        $net = fn(string $from, string $to) => (float) Database::value(
            "SELECT COALESCE(SUM(amount - refunded_amount), 0) FROM payments WHERE status IN {$paidStatuses} AND currency = ? AND paid_at >= ? AND paid_at < ?", [$cur, $from, $to]);

        $kpi = [
            'students'     => (int) Database::value('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL'),
            'newStudents'  => (int) Database::value('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY'),
            'cohorts'      => (int) Database::value("SELECT COUNT(*) FROM cohorts WHERE deleted_at IS NULL AND status IN ('published','open','in_progress')"),
            'startingSoon' => (int) Database::value("SELECT COUNT(*) FROM cohorts WHERE deleted_at IS NULL AND status IN ('published','open') AND start_at BETWEEN UTC_TIMESTAMP() AND UTC_TIMESTAMP() + INTERVAL 14 DAY"),
            'confirmed'    => (int) Database::value("SELECT COUNT(*) FROM enrolments WHERE status = 'confirmed'"),
            'newEnrol'     => (int) Database::value("SELECT COUNT(*) FROM enrolments WHERE registered_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY"),
            'todayClasses' => (int) Database::value("SELECT COUNT(*) FROM class_sessions WHERE status IN ('scheduled','live') AND starts_at >= UTC_TIMESTAMP() - INTERVAL 12 HOUR AND starts_at < UTC_TIMESTAMP() + INTERVAL 24 HOUR"),
        ];
        $revThis = $money ? $net($monthStart, gmdate('Y-m-d H:i:s', strtotime('+1 day'))) : 0;
        $revLast = $money ? $net($lastMonthStart, $monthStart) : 0;

        // Things a human should look at today
        $attention = [];
        $add = function (int $n, string $label, string $hint, string $url, string $tone = 'warn') use (&$attention) {
            if ($n > 0) { $attention[] = compact('n', 'label', 'hint', 'url', 'tone'); }
        };
        if ($money) {
            $add((int) Database::value("SELECT COUNT(*) FROM enrolments WHERE status = 'pending_review'"), 'Enrolments need review', 'Usually a late payment after the seat was released.', '/admin/enrolments?status=pending_review');
            $add((int) Database::value("SELECT COUNT(*) FROM payments WHERE status = 'failed' AND created_at >= UTC_TIMESTAMP() - INTERVAL 7 DAY"), 'Failed payments (7 days)', 'Check whether students need help.', '/admin/payments?status=failed', 'bad');
            $add((int) Database::value("SELECT COUNT(*) FROM enrolments e JOIN cohorts h ON h.id = e.cohort_id WHERE e.status = 'waitlisted' AND h.capacity IS NOT NULL AND (SELECT COUNT(*) FROM enrolments x WHERE x.cohort_id = h.id AND x.status IN ('pending_payment','pending_review','confirmed')) < h.capacity"), 'Waitlisted students with a free seat', 'Promote them from the waitlist.', '/admin/enrolments?status=waitlisted', 'good');
        }
        if ($teach) {
            $add((int) Database::value("SELECT COUNT(*) FROM class_sessions WHERE status = 'scheduled' AND starts_at BETWEEN UTC_TIMESTAMP() AND UTC_TIMESTAMP() + INTERVAL 7 DAY AND (meeting_url IS NULL OR meeting_url = '')"), 'Classes this week without a meeting link', 'Students will see a disabled Join button.', '/admin/sessions?when=upcoming', 'bad');
        }
        if ($teach) {
            $add((int) Database::value("SELECT COUNT(*) FROM jobs WHERE status = 'pending'"), 'Jobs waiting for review', 'The AI held these for a human check (possible scam or low quality).', '/admin/jobs?status=pending');
            $add((int) Database::value("SELECT COUNT(*) FROM job_reports WHERE status = 'open'"), 'Job reports from visitors', 'Learners flagged these postings.', '/admin/jobs/reports', 'bad');
        }
        if ($isAdmin) {
            $add((int) Database::value("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'"), 'Failed emails', 'Retry them from the email log.', '/admin/email-logs?status=failed');
        }

        $upcoming = $teach ? Database::all(
            "SELECT s.id, s.title, s.starts_at, s.ends_at, s.timezone, s.status, s.meeting_url, h.cohort_name, h.id AS cohort_id,
                    (SELECT COUNT(*) FROM enrolments e WHERE e.cohort_id = s.cohort_id AND e.status = 'confirmed') AS students
             FROM class_sessions s JOIN cohorts h ON h.id = s.cohort_id
             WHERE s.status IN ('scheduled','live') AND s.ends_at > UTC_TIMESTAMP() ORDER BY s.starts_at LIMIT 6") : [];

        $fill = $teach ? Database::all(
            "SELECT h.id, h.cohort_name, h.status, h.capacity, h.start_at, h.timezone,
                    SUM(e.status = 'confirmed') AS confirmed, SUM(e.status IN ('pending_payment','pending_review')) AS pending, SUM(e.status = 'waitlisted') AS waitlisted
             FROM cohorts h LEFT JOIN enrolments e ON e.cohort_id = h.id
             WHERE h.deleted_at IS NULL AND h.status IN ('published','open','in_progress') GROUP BY h.id ORDER BY h.start_at ASC LIMIT 5") : [];

        // Net revenue for the last 6 months (filled with zeros so the chart has no gaps)
        $months = [];
        if ($money) {
            for ($i = 5; $i >= 0; $i--) { $months[gmdate('Y-m', strtotime("first day of -{$i} month"))] = 0.0; }
            foreach (Database::all("SELECT DATE_FORMAT(paid_at, '%Y-%m') AS m, SUM(amount - refunded_amount) AS v FROM payments
                                    WHERE status IN {$paidStatuses} AND currency = ? AND paid_at >= ? GROUP BY m", [$cur, gmdate('Y-m-01 00:00:00', strtotime('first day of -5 month'))]) as $r) {
                if (isset($months[$r['m']])) { $months[$r['m']] = (float) $r['v']; }
            }
        }

        $recent = $money ? Database::all(
            "SELECT e.id, e.enrolment_number, e.status, e.registered_at, u.first_name, u.last_name, ch.cohort_name
             FROM enrolments e JOIN users u ON u.id = e.student_id JOIN cohorts ch ON ch.id = e.cohort_id ORDER BY e.registered_at DESC LIMIT 6") : [];

        $this->view('admin/dashboard', compact('admin', 'isAdmin', 'money', 'teach', 'cur', 'kpi', 'revThis', 'revLast', 'attention', 'upcoming', 'fill', 'months', 'recent') + ['title' => 'Dashboard'], 'admin');
    }
}
