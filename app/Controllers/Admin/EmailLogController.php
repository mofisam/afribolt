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
            'intro' => 'Emails are queued here and sent by <code>cron/send_queued_emails.php</code>. Failed emails keep their rendered message so you can retry.',
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
            'actions' => fn($r) => $r['status'] === 'failed' ? [['label' => 'Retry', 'url' => url('/admin/email-logs/' . $r['id'] . '/retry'), 'post' => true]] : [],
        ]);
    }

    public function retry(string $id): void
    {
        $e = EmailLog::find((int) $id) ?? $this->abort(404);
        if ($e['status'] === 'failed') { EmailLog::update((int) $e['id'], ['status' => 'queued', 'attempts' => 0, 'last_error' => null]); }
        $this->done('Email re-queued.', '/admin/email-logs');
    }
}
