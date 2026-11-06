<?php
// 0 7 * * *  php /path/to/cron/jobs_digest.php
// Emails learners who opted in a short list of new jobs that match them (at most once every 3 days).
require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Services\MailService;
use App\Services\Jobs\JobRecommendationService;

$svc = new JobRecommendationService();
$mail = new MailService();
$sent = 0;
foreach (Database::all("SELECT u.id, u.email, u.first_name, p.last_alert_at FROM user_job_preferences p JOIN users u ON u.id = p.user_id
        WHERE p.alerts_enabled = 1 AND u.deleted_at IS NULL AND u.account_status IN ('active','pending_verification')
          AND (p.last_alert_at IS NULL OR p.last_alert_at < UTC_TIMESTAMP() - INTERVAL 3 DAY)") as $u) {
    $since = $u['last_alert_at'] ?: gmdate('Y-m-d H:i:s', time() - 7 * 86400);
    $jobs = array_values(array_filter($svc->forUser((int) $u['id'], 12, false), fn($j) => $j['score'] >= 40 && ($j['published_at'] ?? '') > $since));
    $jobs = array_slice($jobs, 0, 5);
    if (!$jobs) { continue; }
    $mail->queue((int) $u['id'], $u['email'], 'job-digest', count($jobs) . ' new job' . (count($jobs) > 1 ? 's' : '') . ' that match you', ['name' => $u['first_name'], 'jobs' => $jobs]);
    Database::execute('UPDATE user_job_preferences SET last_alert_at = UTC_TIMESTAMP() WHERE user_id = ?', [(int) $u['id']]);
    $sent++;
}
echo gmdate('c') . " queued {$sent} job digest(s)\n";
