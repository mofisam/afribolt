<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\User;

class StudentController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager', 'content_manager'];

    public function index(): void
    {
        $where = ['u.deleted_at IS NULL']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) {
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)"; array_push($p, "%{$q}%", "%{$q}%", "%{$q}%");
        }
        if ($s = $this->request->query('status')) { $where[] = 'u.account_status = ?'; $p[] = $s; }
        [$rows, $pager] = $this->paged('users u WHERE ' . implode(' AND ', $where), $p,
            'u.*, (SELECT COUNT(*) FROM enrolments e WHERE e.student_id = u.id) AS enrolment_count', 'u.created_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Students', 'rows' => $rows, 'pager' => $pager,
            'filters' => [['name' => 'q', 'label' => 'Name, email or phone'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending_verification' => 'Pending verification', 'active' => 'Active', 'suspended' => 'Suspended', 'closed' => 'Closed']]],
            'columns' => [
                ['label' => 'Name', 'render' => fn($r) => $r['first_name'] . ' ' . $r['last_name']],
                ['label' => 'Email', 'render' => fn($r) => $r['email']],
                ['label' => 'Phone', 'render' => fn($r) => $r['phone'] ?? ''],
                ['label' => 'Enrolments', 'render' => fn($r) => $r['enrolment_count']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['account_status'])],
                ['label' => 'Joined', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y')],
            ],
            'actions' => fn($r) => [['label' => 'View', 'url' => url('/admin/students/' . $r['id'])]],
        ]);
    }

    public function show(string $id): void
    {
        $u = User::find((int) $id) ?? $this->abort(404);
        unset($u['password_hash']);
        $enrolments = Database::all(
            'SELECT e.*, h.cohort_name, h.cohort_code, c.title AS course_title FROM enrolments e JOIN cohorts h ON h.id = e.cohort_id JOIN courses c ON c.id = h.course_id WHERE e.student_id = ? ORDER BY e.registered_at DESC',
            [(int) $u['id']]);
        $this->page('admin/students/show', ['title' => $u['first_name'] . ' ' . $u['last_name'], 's' => $u, 'enrolments' => $enrolments, 'canEdit' => \App\Core\Auth::adminHasRole('super_admin', 'admin')]);
    }

    public function status(string $id): void
    {
        $this->allow('admin');
        $u = User::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!in_array($new, ['active', 'suspended', 'closed'], true)) { $this->abort(422); }
        User::update((int) $u['id'], ['account_status' => $new]);
        $this->audit('student.status_changed', 'user', (int) $u['id'], ['account_status' => $u['account_status']], ['account_status' => $new]);
        $this->done("Account set to {$new}.", '/admin/students/' . $u['id']);
    }
}
