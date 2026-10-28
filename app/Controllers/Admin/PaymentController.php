<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\PaymentService;

class PaymentController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager'];

    private const STATUSES = ['initiated' => 'Initiated', 'pending' => 'Pending', 'successful' => 'Successful', 'failed' => 'Failed', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded', 'partially_refunded' => 'Partially refunded'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) {
            $where[] = "(p.internal_reference LIKE ? OR p.provider_reference LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ?)"; array_push($p, "%{$q}%", "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($s = $this->request->query('status')) { $where[] = 'p.status = ?'; $p[] = $s; }
        if ($f = $this->request->query('from')) { $where[] = 'p.created_at >= ?'; $p[] = $f . ' 00:00:00'; }
        if ($t = $this->request->query('to')) { $where[] = 'p.created_at <= ?'; $p[] = $t . ' 23:59:59'; }
        [$rows, $pager] = $this->paged('payments p JOIN enrolments e ON e.id = p.enrolment_id JOIN users u ON u.id = e.student_id JOIN cohorts h ON h.id = e.cohort_id WHERE ' . implode(' AND ', $where), $p,
            'p.*, u.first_name, u.last_name, u.email, h.cohort_name', 'p.created_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Payments', 'rows' => $rows, 'pager' => $pager,
            'intro' => 'Dates filter on the day the payment attempt was created (UTC).',
            'filters' => [['name' => 'q', 'label' => 'Reference, name or email'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES],
                ['name' => 'from', 'label' => 'From', 'type' => 'date'], ['name' => 'to', 'label' => 'To', 'type' => 'date']],
            'columns' => [
                ['label' => 'Reference', 'render' => fn($r) => $r['internal_reference']],
                ['label' => 'Student', 'html' => true, 'render' => fn($r) => e($r['first_name'] . ' ' . $r['last_name']) . '<br><span class="text-slate-500">' . e($r['cohort_name']) . '</span>'],
                ['label' => 'Amount', 'render' => fn($r) => number_format((float) $r['amount'], 2) . ' ' . $r['currency']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
                ['label' => 'Method', 'render' => fn($r) => $r['payment_method'] ?? ''],
                ['label' => 'Created', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y, g:i A')],
            ],
            'actions' => fn($r) => [['label' => 'Open', 'url' => url('/admin/payments/' . $r['id'])]],
        ]);
    }

    public function show(string $id): void
    {
        $p = Database::one('SELECT p.*, u.first_name, u.last_name, u.email, h.cohort_name, e.enrolment_number, g.display_name AS gateway_name
                            FROM payments p JOIN enrolments e ON e.id = p.enrolment_id JOIN users u ON u.id = e.student_id JOIN cohorts h ON h.id = e.cohort_id
                            LEFT JOIN payment_gateways g ON g.id = p.gateway_id WHERE p.id = ?', [(int) $id]) ?? $this->abort(404);
        $json = $p['provider_response'] ? json_encode(json_decode($p['provider_response']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '';
        $this->page('admin/payments/show', ['title' => 'Payment ' . $p['internal_reference'], 'p' => $p, 'json' => $json,
            'canRefund' => \App\Core\Auth::adminHasRole('super_admin', 'finance_manager')]);
    }

    /** Ask the provider again (e.g. webhook missed or student closed the tab). */
    public function verify(string $id): void
    {
        $p = Database::one('SELECT internal_reference, status FROM payments WHERE id = ?', [(int) $id]) ?? $this->abort(404);
        try {
            $r = (new PaymentService())->verifyAndConfirm($p['internal_reference']);
        } catch (\RuntimeException $e) {
            $r = ['ok' => false, 'message' => 'Could not reach the payment provider: ' . $e->getMessage()];
        }
        $this->audit('payment.reverified', 'payment', (int) $id, ['status' => $p['status']], ['result' => $r['message']]);
        $this->done($r['message'], '/admin/payments/' . $id, $r['ok'] ? 'success' : 'error');
    }

    public function refund(string $id): void
    {
        $this->allow('finance_manager');
        $amount = (float) $this->request->post('amount', 0);
        $note = trim((string) $this->request->post('note', ''));
        if ($note === '') { $this->done('Add a note with the refund reference.', '/admin/payments/' . (int) $id, 'error'); }
        $r = (new PaymentService())->recordManualRefund((int) $id, $amount, $note);
        $this->done($r['message'], '/admin/payments/' . (int) $id, $r['ok'] ? 'success' : 'error');
    }
}
