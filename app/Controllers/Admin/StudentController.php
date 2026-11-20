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
            'actions' => fn($r) => [['label' => 'View', 'url' => url('/admin/students/' . $r['id'])],
                ['label' => 'Delete', 'icon' => 'delete', 'danger' => true, 'url' => url('/admin/students/' . $r['id'] . '/delete'), 'post' => true, 'confirm' => 'Delete this student account? Only possible for accounts with no enrolments, courses or payments (spam or test accounts). This cannot be undone.']],
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

    /** Delete an account that never did anything (spam / test). Students with enrolments, courses or payments are kept for the records. */
    public function delete(string $id): void
    {
        $u = \App\Models\User::find((int) $id) ?? $this->abort(404);
        $uid = (int) $u['id']; $back = '/admin/students';
        $enr = (int) \App\Core\Database::value('SELECT COUNT(*) FROM enrolments WHERE student_id = ?', [$uid]);
        $acc = (int) \App\Core\Database::value('SELECT COUNT(*) FROM course_access WHERE user_id = ?', [$uid]);
        $payJoin = 'FROM payments p LEFT JOIN enrolments e ON e.id = p.enrolment_id LEFT JOIN course_access ca ON ca.id = p.course_access_id';
        try { $pay = (int) \App\Core\Database::value("SELECT COUNT(*) {$payJoin} LEFT JOIN career_launch_registrations cl ON cl.id = p.career_launch_id WHERE COALESCE(e.student_id, ca.user_id, cl.user_id) = ?", [$uid]); }
        catch (\Throwable $e) { $pay = (int) \App\Core\Database::value("SELECT COUNT(*) {$payJoin} WHERE COALESCE(e.student_id, ca.user_id) = ?", [$uid]); }
        if ($enr || $acc || $pay) {
            $this->done('This student has ' . ($enr ? $enr . ' enrolment(s), ' : '') . ($acc ? $acc . ' course(s), ' : '') . ($pay ? $pay . ' payment(s), ' : '') . 'so the account is kept for your records.', $back, 'error');
        }
        $this->audit('student.deleted', 'user', $uid, null, ['email' => $u['email']]);
        // soft delete; the email is freed so the person can register again later
        $this->tryDelete(fn() => \App\Core\Database::execute("UPDATE users SET deleted_at = UTC_TIMESTAMP(), email = CONCAT('deleted+', id, '@deleted.invalid') WHERE id = ?", [$uid]), 'Student account deleted.', $back);
    }
}
