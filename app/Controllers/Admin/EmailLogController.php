<?php
namespace App\Controllers\Admin;

use App\Models\EmailLog;

class EmailLogController extends BaseAdminController
{
    protected array $roles = ['admin'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($s = $this->request->query('status')) { $where[] = 'status = ?'; $p[] = $s; }
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = '(recipient_email LIKE ? OR subject LIKE ?)'; $p[] = "%{$q}%"; $p[] = "%{$q}%"; }
        [$rows, $pager] = $this->paged('email_logs WHERE ' . implode(' AND ', $where), $p, '*', 'id DESC', 30);
        $this->page('admin/crud/index', [
            'title' => 'Email log', 'rows' => $rows, 'pager' => $pager,
            'intro' => '<p>Emails are queued here and sent by <code>cron/send_queued_emails.php</code>. Failed emails keep their rendered message so you can retry.</p>'
                . '<p class="mt-2"><strong>Sending method:</strong> ' . (config('mail.driver') === 'smtp' ? 'SMTP via ' . e((string) config('mail.host')) . ':' . (int) config('mail.port') . ' (' . e((string) config('mail.encryption')) . ')' : '<span class="text-red-700 font-bold">PHP mail() (set MAIL_HOST in .env to use SMTP)</span>') . '</p>'
                . '<form method="post" action="' . e(url('/admin/email-logs/test')) . '" class="mt-3 flex flex-wrap items-end gap-2">' . csrf_field()
                . '<div><label class="label text-sm" for="test_to">Send a test email to</label><input class="field" type="email" id="test_to" name="to" required placeholder="you@example.com"></div>'
                . '<button class="btn btn-ghost" type="submit">Send test</button></form>',
            'filters' => [['name' => 'q', 'label' => 'Recipient or subject'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['queued' => 'Queued', 'sent' => 'Sent', 'failed' => 'Failed']]],
            'columns' => [
                ['label' => 'Queued', 'render' => fn($r) => local_date($r['queued_at'], null, 'j M, g:i A')],
                ['label' => 'To', 'render' => fn($r) => $r['recipient_email']],
                ['label' => 'Template', 'render' => fn($r) => $r['template_code']],
                ['label' => 'Subject', 'render' => fn($r) => $r['subject']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
                ['label' => 'Tries', 'render' => fn($r) => $r['attempts']],
                ['label' => 'Last error', 'render' => fn($r) => $r['last_error'] ?? ''],
            ],
            'actions' => fn($r) => [['label' => 'Resend', 'icon' => 'send', 'show_label' => true, 'url' => url('/admin/email-logs/' . $r['id'] . '/resend'), 'post' => true,
                'confirm' => 'Send this email again to ' . $r['recipient_email'] . ' now?']],
        ]);
    }

    /** Send a real email right now so SMTP settings can be checked without waiting for cron. */
    public function test(): void
    {
        $to = trim((string) $this->request->post('to', ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { $this->done('Enter a valid email address.', '/admin/email-logs', 'error'); }
        try {
            (new \App\Services\MailService())->sendNow($to, 'Afribolt test email', \App\Core\View::capture('emails/test', ['subject' => 'Afribolt test email', 'recipient' => $to], 'email'));
        } catch (\Throwable $e) {
            $this->done('Test failed: ' . $e->getMessage(), '/admin/email-logs', 'error');
        }
        $this->done('Test email sent to ' . $to . '. Check the inbox (and spam folder).', '/admin/email-logs');
    }

    /** Send this email again immediately and show the result (works for sent, queued and failed emails). */
    public function resend(string $id): void
    {
        $e = EmailLog::find((int) $id) ?? $this->abort(404);
        try { (new \App\Services\MailService())->resend((int) $e['id']); }
        catch (\Throwable $ex) { $this->done('Could not resend: ' . $ex->getMessage(), '/admin/email-logs', 'error'); }
        $this->audit('email.resent', 'email_log', (int) $e['id'], null, ['to' => $e['recipient_email']]);
        $this->done('Email resent to ' . $e['recipient_email'] . '. Check the inbox (and spam folder).', '/admin/email-logs');
    }

    public function retry(string $id): void
    {
        $e = EmailLog::find((int) $id) ?? $this->abort(404);
        if ($e['status'] === 'failed') { EmailLog::update((int) $e['id'], ['status' => 'queued', 'attempts' => 0, 'last_error' => null]); }
        $this->done('Email re-queued.', '/admin/email-logs');
    }
}
