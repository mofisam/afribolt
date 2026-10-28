<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\Announcement;
use App\Services\NotificationService;

class AnnouncementController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const STATUSES = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];

    private function cohortOptions(): array
    {
        $o = ['' => 'Everyone (not tied to one cohort)'];
        foreach (Database::all('SELECT id, cohort_name FROM cohorts WHERE deleted_at IS NULL ORDER BY start_at DESC') as $c) { $o[$c['id']] = $c['cohort_name']; }
        return $o;
    }

    private function fields(): array
    {
        return [
            ['name' => 'cohort_id', 'label' => 'Audience', 'type' => 'select', 'options' => $this->cohortOptions()],
            ['name' => 'title', 'label' => 'Title', 'required' => true],
            ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'rows' => 7, 'required' => true],
            ['name' => 'visibility', 'label' => 'Who can see it', 'type' => 'select', 'half' => true, 'default' => 'enrolled_students',
                'options' => ['enrolled_students' => 'Enrolled students', 'public' => 'Public', 'staff_only' => 'Staff only']],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'options' => self::STATUSES, 'default' => 'draft',
                'help' => 'Enrolled students of the chosen cohort get a notification when it is first published.'],
            ['name' => 'publish_at', 'label' => 'Show from (optional)', 'type' => 'datetime', 'half' => true, 'help' => 'Times are in ' . $this->defaultTz() . '.'],
            ['name' => 'expires_at', 'label' => 'Hide after (optional)', 'type' => 'datetime', 'half' => true],
        ];
    }

    private const RULES = ['cohort_id' => 'integer', 'title' => 'required|max:200', 'body' => 'required|max:10000',
        'visibility' => 'required|in:enrolled_students,public,staff_only', 'status' => 'required|in:draft,published,archived', 'publish_at' => 'date', 'expires_at' => 'date'];

    private function rowFrom(array $d): array
    {
        $tz = $this->defaultTz();
        return ['cohort_id' => $d['cohort_id'] ? (int) $d['cohort_id'] : null, 'title' => $d['title'], 'body' => $d['body'], 'visibility' => $d['visibility'],
            'status' => $d['status'], 'publish_at' => $this->fromInput($d['publish_at'], $tz), 'expires_at' => $this->fromInput($d['expires_at'], $tz)];
    }

    private function notifyIfFirstPublish(?array $old, array $row, int $id): void
    {
        if ($row['status'] === 'published' && ($old['status'] ?? '') !== 'published' && $row['cohort_id'] && $row['visibility'] !== 'staff_only') {
            (new NotificationService())->toCohort((int) $row['cohort_id'], 'announcement', $row['title'], mb_strimwidth($row['body'], 0, 140, '...'), '/student/dashboard');
        }
    }

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($s = $this->request->query('status')) { $where[] = 'a.status = ?'; $p[] = $s; }
        [$rows, $pager] = $this->paged('announcements a LEFT JOIN cohorts h ON h.id = a.cohort_id WHERE ' . implode(' AND ', $where), $p, 'a.*, h.cohort_name', 'a.created_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Announcements', 'createUrl' => url('/admin/announcements/create'), 'createLabel' => 'New announcement', 'rows' => $rows, 'pager' => $pager,
            'filters' => [['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES]],
            'columns' => [
                ['label' => 'Title', 'render' => fn($r) => $r['title']],
                ['label' => 'Audience', 'render' => fn($r) => $r['cohort_name'] ?? 'Everyone'],
                ['label' => 'Visibility', 'render' => fn($r) => str_replace('_', ' ', $r['visibility'])],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
                ['label' => 'Created', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y')],
            ],
            'actions' => function ($r) {
                $a = [['label' => 'Edit', 'url' => url('/admin/announcements/' . $r['id'] . '/edit')]];
                if ($r['status'] !== 'published') { $a[] = ['label' => 'Publish', 'url' => url('/admin/announcements/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'published']]; }
                if ($r['status'] !== 'archived') { $a[] = ['label' => 'Archive', 'url' => url('/admin/announcements/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'archived']]; }
                return $a;
            },
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'New announcement', 'action' => url('/admin/announcements'), 'fields' => $this->fields(), 'values' => [], 'cancelUrl' => url('/admin/announcements')]);
    }

    public function store(): void
    {
        $d = $this->validate(self::RULES, '/admin/announcements/create');
        $row = $this->rowFrom($d) + ['created_by_admin_id' => $this->adminId()];
        $id = Announcement::create($row);
        $this->audit('announcement.created', 'announcement', $id, null, ['title' => $row['title'], 'status' => $row['status']]);
        $this->notifyIfFirstPublish(null, $row, $id);
        $this->done('Announcement saved.', '/admin/announcements');
    }

    public function edit(string $id): void
    {
        $a = Announcement::find((int) $id) ?? $this->abort(404);
        $a['publish_at'] = $this->toInput($a['publish_at'], $this->defaultTz());
        $a['expires_at'] = $this->toInput($a['expires_at'], $this->defaultTz());
        $this->page('admin/crud/form', ['title' => 'Edit announcement', 'action' => url('/admin/announcements/' . $a['id']), 'fields' => $this->fields(), 'values' => $a, 'cancelUrl' => url('/admin/announcements')]);
    }

    public function update(string $id): void
    {
        $a = Announcement::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(self::RULES, '/admin/announcements/' . $a['id'] . '/edit');
        $row = $this->rowFrom($d);
        Announcement::update((int) $a['id'], $row);
        $this->audit('announcement.updated', 'announcement', (int) $a['id'], $a, ['title' => $row['title'], 'status' => $row['status'], 'visibility' => $row['visibility']]);
        $this->notifyIfFirstPublish($a, $row, (int) $a['id']);
        $this->done('Announcement updated.', '/admin/announcements');
    }

    public function status(string $id): void
    {
        $a = Announcement::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!isset(self::STATUSES[$new])) { $this->abort(422); }
        Announcement::update((int) $a['id'], ['status' => $new]);
        $this->audit('announcement.status_changed', 'announcement', (int) $a['id'], ['status' => $a['status']], ['status' => $new]);
        $this->notifyIfFirstPublish($a, $a['cohort_id'] ? ['status' => $new, 'cohort_id' => $a['cohort_id'], 'visibility' => $a['visibility'], 'title' => $a['title'], 'body' => $a['body']] : ['status' => $new, 'cohort_id' => null], (int) $a['id']);
        $this->done("Announcement {$new}.", '/admin/announcements');
    }
}
