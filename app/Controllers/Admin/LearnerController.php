<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\{CourseAccess, User};
use App\Services\{CourseAccessService, CourseCatalogService, NotificationService};

/** Self-paced learners: see who has access, grant access (e.g. scholarships), revoke or restore. */
class LearnerController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager'];

    private function courseOptions(): array
    {
        return array_column(Database::all("SELECT id, title FROM courses WHERE deleted_at IS NULL AND delivery_mode IN ('self_paced','both') ORDER BY title"), 'title', 'id');
    }

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)"; array_push($p, "%{$q}%", "%{$q}%"); }
        if ($c = $this->request->query('course')) { $where[] = 'ca.course_id = ?'; $p[] = (int) $c; }
        if ($s = $this->request->query('status')) { $where[] = 'ca.status = ?'; $p[] = $s; }
        [$rows, $pager] = $this->paged('course_access ca JOIN users u ON u.id = ca.user_id JOIN courses c ON c.id = ca.course_id WHERE ' . implode(' AND ', $where), $p,
            "ca.*, u.first_name, u.last_name, u.email, c.title AS course_title, c.lessons_count,
             (SELECT COUNT(*) FROM lesson_progress lp JOIN lessons l ON l.id = lp.lesson_id AND l.status = 'published' WHERE lp.user_id = ca.user_id AND lp.course_id = ca.course_id AND lp.completed_at IS NOT NULL) AS done",
            'ca.created_at DESC');

        $opts = '';
        foreach ($this->courseOptions() as $id => $t) { $opts .= '<option value="' . (int) $id . '">' . e($t) . '</option>'; }
        $intro = '<form method="post" action="' . e(url('/admin/learners/grant')) . '" class="bg-white border rounded-lg p-4 flex flex-wrap gap-3 items-end">' . csrf_field()
            . '<div><label class="label text-sm" for="g_email">Grant free access to (student email)</label><input class="field" type="email" id="g_email" name="email" required></div>'
            . '<div><label class="label text-sm" for="g_course">Course</label><select class="field" id="g_course" name="course_id" required>' . $opts . '</select></div>'
            . '<button class="btn btn-primary" type="submit">Grant access</button></form>'
            . '<p class="mt-2 text-sm">Students who joined a live cohort get access automatically when the course allows it, so they are not listed until they open a lesson.</p>';

        $this->page('admin/crud/index', [
            'title' => 'Self-paced learners', 'rows' => $rows, 'pager' => $pager, 'intro' => $intro,
            'filters' => [['name' => 'q', 'label' => 'Name or email'], ['name' => 'course', 'label' => 'Course', 'type' => 'select', 'options' => $this->courseOptions()],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['pending' => 'Pending payment', 'active' => 'Active', 'revoked' => 'Revoked', 'refunded' => 'Refunded']]],
            'columns' => [
                ['label' => 'Learner', 'html' => true, 'render' => fn($r) => e($r['first_name'] . ' ' . $r['last_name']) . '<br><span class="text-slate-500">' . e($r['email']) . '</span>'],
                ['label' => 'Course', 'render' => fn($r) => $r['course_title']],
                ['label' => 'How', 'render' => fn($r) => str_replace('_', ' ', $r['source'])],
                ['label' => 'Paid', 'render' => fn($r) => number_format((float) $r['amount_paid'], 2) . ' ' . $r['currency']],
                ['label' => 'Progress', 'render' => fn($r) => ($r['lessons_count'] ? (int) floor(100 * $r['done'] / $r['lessons_count']) : 0) . '% (' . (int) $r['done'] . '/' . (int) $r['lessons_count'] . ')' . ($r['completed_at'] ? ' done' : '')],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'] === 'active' ? 'active' : ($r['status'] === 'pending' ? 'pending' : 'closed'))],
                ['label' => 'Last active', 'render' => fn($r) => $r['last_accessed_at'] ? local_date($r['last_accessed_at'], null, 'j M Y') : 'Never'],
            ],
            'actions' => fn($r) => $r['status'] === 'active'
                ? [['label' => 'Revoke', 'url' => url('/admin/learners/' . $r['id'] . '/revoke'), 'post' => true, 'confirm' => 'Remove this learner\'s access?']]
                : ($r['status'] === 'revoked' ? [['label' => 'Restore', 'url' => url('/admin/learners/' . $r['id'] . '/restore'), 'post' => true]] : []),
        ]);
    }

    public function grant(): void
    {
        $d = $this->validate(['email' => 'required|email', 'course_id' => 'required|integer'], '/admin/learners');
        $user = User::findByEmail($d['email']);
        $course = \App\Models\Course::find((int) $d['course_id']);
        if (!$user || !$course) { $this->done('No student with that email, or the course was not found.', '/admin/learners', 'error'); }
        $id = (new CourseAccessService())->grant((int) $user['id'], (int) $course['id'], 'admin_grant');
        (new NotificationService())->toStudent((int) $user['id'], 'course_access', 'You have access to a course', "You can now learn {$course['title']}.", 'course', (int) $course['id'], '/learn/' . $course['slug']);
        $this->audit('learner.granted', 'course_access', $id, null, ['user_id' => (int) $user['id'], 'course_id' => (int) $course['id']]);
        $this->done('Access granted to ' . $user['email'] . '.', '/admin/learners');
    }

    private function setStatus(string $id, string $new): void
    {
        $a = CourseAccess::find((int) $id) ?? $this->abort(404);
        CourseAccess::update((int) $a['id'], ['status' => $new]);
        $this->audit('learner.' . $new, 'course_access', (int) $a['id'], ['status' => $a['status']], ['status' => $new]);
        CourseCatalogService::refresh((int) $a['course_id']);
        $this->done('Access ' . ($new === 'active' ? 'restored' : 'revoked') . '.', '/admin/learners');
    }
    public function revoke(string $id): void { $this->setStatus($id, 'revoked'); }
    public function restore(string $id): void { $this->setStatus($id, 'active'); }
}
