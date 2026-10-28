<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\EnrolmentService;

class EnrolmentController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager'];

    private const STATUSES = ['pending_payment' => 'Awaiting payment', 'pending_review' => 'Needs review', 'confirmed' => 'Confirmed', 'waitlisted' => 'Waitlisted',
        'cancelled' => 'Cancelled', 'completed' => 'Completed', 'refunded' => 'Refunded'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) {
            $where[] = "(e.enrolment_number LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)"; array_push($p, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($s = $this->request->query('status')) { $where[] = 'e.status = ?'; $p[] = $s; }
        if ($c = $this->request->query('cohort')) { $where[] = 'e.cohort_id = ?'; $p[] = (int) $c; }
        $cohorts = [];
        foreach (Database::all('SELECT id, cohort_name FROM cohorts WHERE deleted_at IS NULL ORDER BY start_at DESC') as $h) { $cohorts[$h['id']] = $h['cohort_name']; }

        [$rows, $pager] = $this->paged('enrolments e JOIN users u ON u.id = e.student_id JOIN cohorts h ON h.id = e.cohort_id WHERE ' . implode(' AND ', $where), $p,
            'e.*, u.first_name, u.last_name, u.email, h.cohort_name', 'e.registered_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Enrolments', 'rows' => $rows, 'pager' => $pager,
            'filters' => [['name' => 'q', 'label' => 'Ref, name or email'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES],
                ['name' => 'cohort', 'label' => 'Cohort', 'type' => 'select', 'options' => $cohorts]],
            'columns' => [
                ['label' => 'Ref', 'render' => fn($r) => $r['enrolment_number']],
                ['label' => 'Student', 'html' => true, 'render' => fn($r) => e($r['first_name'] . ' ' . $r['last_name']) . '<br><span class="text-slate-500">' . e($r['email']) . '</span>'],
                ['label' => 'Cohort', 'render' => fn($r) => $r['cohort_name']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'], $r['status'] === 'pending_review' ? 'badge-warn' : '')],
                ['label' => 'Paid', 'render' => fn($r) => number_format((float) $r['amount_paid'], 2) . ' / ' . number_format((float) $r['fee_amount'], 2) . ' ' . $r['currency']],
                ['label' => 'Registered', 'render' => fn($r) => local_date($r['registered_at'], null, 'j M Y')],
            ],
            'actions' => fn($r) => [['label' => 'Open', 'url' => url('/admin/enrolments/' . $r['id'])]],
        ]);
    }

    public function show(string $id): void
    {
        $e = Database::one('SELECT e.*, u.first_name, u.last_name, u.email, u.phone, h.cohort_name, h.cohort_code, h.capacity, h.timezone, c.title AS course_title
                            FROM enrolments e JOIN users u ON u.id = e.student_id JOIN cohorts h ON h.id = e.cohort_id JOIN courses c ON c.id = h.course_id WHERE e.id = ?', [(int) $id]) ?? $this->abort(404);
        $this->page('admin/enrolments/show', [
            'title' => 'Enrolment ' . $e['enrolment_number'], 'e' => $e,
            'payments' => Database::all('SELECT * FROM payments WHERE enrolment_id = ? ORDER BY created_at DESC', [(int) $e['id']]),
        ]);
    }

    public function action(string $id): void
    {
        $e = Database::one('SELECT id, status FROM enrolments WHERE id = ?', [(int) $id]) ?? $this->abort(404);
        $note = trim((string) $this->request->post('note', ''));
        $svc = new EnrolmentService();
        $result = match ((string) $this->request->post('action')) {
            'confirm'  => $note === '' ? ['ok' => false, 'message' => 'Add a note explaining why this is being confirmed manually (e.g. bank transfer reference).'] : $svc->confirm((int) $e['id'], $note),
            'promote'  => $svc->promote((int) $e['id'], $note),
            'cancel'   => $svc->cancel((int) $e['id'], $note),
            'complete' => $svc->complete((int) $e['id']),
            'note'     => $this->addNote((int) $e['id'], $note),
            default    => ['ok' => false, 'message' => 'Unknown action.'],
        };
        $this->done($result['message'], '/admin/enrolments/' . $e['id'], $result['ok'] ? 'success' : 'error');
    }

    private function addNote(int $id, string $note): array
    {
        if ($note === '') { return ['ok' => false, 'message' => 'Write a note first.']; }
        Database::execute("UPDATE enrolments SET admin_notes = CONCAT(COALESCE(admin_notes, ''), ?) WHERE id = ?",
            ["\n[" . utc_now() . ' UTC, admin #' . $this->adminId() . '] ' . $note, $id]);
        return ['ok' => true, 'message' => 'Note added.'];
    }
}
