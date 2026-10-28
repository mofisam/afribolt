<?php
namespace App\Services;

use App\Core\{Database, View};
use App\Models\EmailLog;

/**
 * Emails are queued in email_logs, then sent by cron/send_queued_emails.php.
 * Rendered HTML is stored in storage/cache so the cron job can send without rebuilding context.
 */
class MailService
{
    public function queue(?int $userId, string $to, string $templateCode, string $subject, array $data = []): int
    {
        $id = EmailLog::create([
            'user_id' => $userId, 'recipient_email' => $to, 'template_code' => $templateCode, 'subject' => $subject,
        ]);
        $html = View::capture('emails/' . $templateCode, $data + ['subject' => $subject], 'email');
        file_put_contents(base_path("storage/cache/email_{$id}.html"), $html);
        return $id;
    }

    /** Send up to $limit queued emails. Replace deliver() with SMTP/API once the provider is chosen. */
    public function sendQueued(int $limit = 25): int
    {
        $max = (int) config('mail.max_attempts', 3);
        $rows = Database::all("SELECT * FROM email_logs WHERE status = 'queued' AND attempts < ? ORDER BY id LIMIT ?", [$max, $limit]);
        $sent = 0;
        foreach ($rows as $row) {
            $file = base_path("storage/cache/email_{$row['id']}.html");
            $html = is_file($file) ? file_get_contents($file) : '<p>' . htmlspecialchars($row['subject']) . '</p>';
            try {
                $ok = $this->deliver($row['recipient_email'], $row['subject'], $html);
                $err = $ok ? null : 'mail() returned false';
            } catch (\Throwable $e) {
                $ok = false;
                $err = $e->getMessage();
            }
            Database::execute(
                'UPDATE email_logs SET status = ?, attempts = attempts + 1, last_error = ?, sent_at = ' . ($ok ? 'UTC_TIMESTAMP()' : 'NULL') . ' WHERE id = ?',
                [$ok ? 'sent' : ((int) $row['attempts'] + 1 >= $max ? 'failed' : 'queued'), $err, (int) $row['id']]
            );
            if ($ok) { $sent++; @unlink($file); }
        }
        return $sent;
    }

    protected function deliver(string $to, string $subject, string $html): bool
    {
        $from = config('mail.from_address');
        $name = config('mail.from_name');
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: {$name} <{$from}>\r\n";
        return mail($to, $subject, $html, $headers);
    }
}
