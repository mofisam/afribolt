<?php
// */10 * * * *  php /path/to/afribolt-platform/cron/session_reminders.php
// Reminds confirmed students about classes starting within the next hour (once per student per class).
require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Services\{MailService, NotificationService};

$sessions = Database::all("SELECT * FROM class_sessions WHERE status = 'scheduled' AND starts_at > UTC_TIMESTAMP() AND starts_at <= UTC_TIMESTAMP() + INTERVAL 65 MINUTE");
$notify = new NotificationService();
$mail = new MailService();
$count = 0;

foreach ($sessions as $s) {
    $students = Database::all("SELECT u.id, u.email, u.first_name FROM enrolments e JOIN users u ON u.id = e.student_id WHERE e.cohort_id = ? AND e.status = 'confirmed'", [(int) $s['cohort_id']]);
    foreach ($students as $u) {
        $already = Database::value("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND notification_type = 'session_reminder' AND related_entity_type = 'class_session' AND related_entity_id = ?", [(int) $u['id'], (int) $s['id']]);
        if ($already) { continue; }
        $notify->toStudent((int) $u['id'], 'session_reminder', 'Class starting soon', $s['title'] . ' starts at ' . local_date($s['starts_at'], $s['timezone'], 'g:i A'), 'class_session', (int) $s['id'], '/student/dashboard');
        $mail->queue((int) $u['id'], $u['email'], 'session-reminder', 'Class starting soon: ' . $s['title'], ['name' => $u['first_name'], 'session' => $s]);
        $count++;
    }
}
echo gmdate('c') . " queued {$count} reminder(s)\n";
