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
        $html = View::capture('emails/' . $templateCode, $data + ['subject' => $subject, 'recipient' => $to], 'email');
        file_put_contents(base_path("storage/cache/email_{$id}.html"), $html);
        return $id;
    }

    /** Send up to $limit queued emails. Delivery is SMTP when MAIL_HOST is set in .env, otherwise PHP mail(). */
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
            if ($ok) { $sent++; } // keep the rendered copy so an admin can resend it later
        }
        $this->purgeOldCopies();
        return $sent;
    }

    /** Delete saved email copies older than 60 days (they are kept after sending so "Resend" works). */
    private function purgeOldCopies(): void
    {
        foreach (glob(base_path('storage/cache/email_*.html')) ?: [] as $f) {
            if (@filemtime($f) < time() - 60 * 86400) { @unlink($f); }
        }
    }

    /**
     * Send an already-logged email again, right now. Uses the saved copy of the message.
     * @throws \RuntimeException when the copy no longer exists or delivery fails
     */
    public function resend(int $id): void
    {
        $row = Database::one('SELECT * FROM email_logs WHERE id = ?', [$id]) ?? throw new \RuntimeException('Email not found.');
        $file = base_path("storage/cache/email_{$id}.html");
        if (!is_file($file)) { throw new \RuntimeException('The saved copy of this message is gone (emails sent before this update, or older than 60 days, cannot be resent).'); }
        try {
            $this->deliver($row['recipient_email'], $row['subject'], (string) file_get_contents($file));
        } catch (\Throwable $e) {
            Database::execute("UPDATE email_logs SET status = 'failed', attempts = attempts + 1, last_error = ? WHERE id = ?", [mb_substr($e->getMessage(), 0, 500), $id]);
            throw $e;
        }
        Database::execute("UPDATE email_logs SET status = 'sent', attempts = attempts + 1, last_error = NULL, sent_at = UTC_TIMESTAMP() WHERE id = ?", [$id]);
    }

    /** Send one email immediately (used by the admin "Send test email" button). Throws on failure. */
    public function sendNow(string $to, string $subject, string $html): void
    {
        if (!$this->deliver($to, $subject, $html)) { throw new \RuntimeException('mail() returned false. Configure SMTP in .env (see MAIL_HOST).'); }
    }

    protected function deliver(string $to, string $subject, string $html): bool
    {
        $from = (string) config('mail.from_address');
        $name = (string) config('mail.from_name');
        if (config('mail.driver') === 'smtp') {
            $host = (string) config('mail.host');
            if ($host === '') { throw new \RuntimeException('MAIL_DRIVER is smtp but MAIL_HOST is empty in .env.'); }
            (new SmtpMailer($host, (int) config('mail.port'), (string) config('mail.encryption'), (string) config('mail.username'), (string) config('mail.password'), (int) config('mail.timeout')))
                ->send($from, $name, $to, $subject, $html);
            return true;
        }
        $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\nFrom: {$name} <{$from}>\r\n";
        return mail($to, $subject, $html, $headers);
    }
}
