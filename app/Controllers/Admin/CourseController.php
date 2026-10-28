<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\{Course, CourseCategory};
use App\Services\UploadService;

class CourseController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const RULES = [
        'category_id' => 'integer', 'title' => 'required|max:200', 'slug' => 'max:220', 'short_description' => 'max:500',
        'description' => 'max:20000', 'learning_objectives' => 'max:5000', 'prerequisites' => 'max:5000',
        'default_duration_weeks' => 'integer', 'course_level' => 'required|in:beginner,intermediate,advanced,all_levels',
        'status' => 'required|in:draft,published,archived',
    ];

    private function categoryOptions(): array
    {
        $o = ['' => 'No category'];
        foreach (CourseCategory::all('display_order', 'ASC') as $c) { $o[$c['id']] = $c['name'] . ($c['status'] === 'inactive' ? ' (inactive)' : ''); }
        return $o;
    }

    private function fields(): array
    {
        return [
            ['name' => 'title', 'label' => 'Title', 'required' => true],
            ['name' => 'slug', 'label' => 'URL slug', 'half' => true, 'help' => 'Blank = generated from the title.'],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => $this->categoryOptions(), 'half' => true],
            ['name' => 'short_description', 'label' => 'Short description', 'type' => 'textarea', 'rows' => 2, 'help' => 'Shown on course cards (max 500 characters).'],
            ['name' => 'description', 'label' => 'Full description', 'type' => 'textarea', 'rows' => 8],
            ['name' => 'learning_objectives', 'label' => 'Learning objectives', 'type' => 'textarea', 'help' => 'One per line.'],
            ['name' => 'prerequisites', 'label' => 'Requirements', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'default_duration_weeks', 'label' => 'Typical duration (weeks)', 'type' => 'number', 'half' => true, 'min' => 1],
            ['name' => 'course_level', 'label' => 'Level', 'type' => 'select', 'half' => true, 'default' => 'all_levels',
                'options' => ['all_levels' => 'All levels', 'beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced']],
            ['name' => 'thumbnail', 'label' => 'Thumbnail image', 'type' => 'file', 'help' => 'JPG, PNG or WebP, up to 2 MB.'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'default' => 'draft', 'options' => ['draft' => 'Draft (hidden)', 'published' => 'Published', 'archived' => 'Archived']],
        ];
    }

    public function index(): void
    {
        $where = ['c.deleted_at IS NULL']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = 'c.title LIKE ?'; $p[] = "%{$q}%"; }
        if ($s = $this->request->query('status')) { $where[] = 'c.status = ?'; $p[] = $s; }
        if ($cat = $this->request->query('category')) { $where[] = 'c.category_id = ?'; $p[] = (int) $cat; }

        [$rows, $pager] = $this->paged(
            'courses c LEFT JOIN course_categories cat ON cat.id = c.category_id WHERE ' . implode(' AND ', $where), $p,
            'c.*, cat.name AS category_name, (SELECT COUNT(*) FROM cohorts h WHERE h.course_id = c.id AND h.deleted_at IS NULL) AS cohort_count',
            'c.updated_at DESC'
        );
        $this->page('admin/crud/index', [
            'title' => 'Courses', 'createUrl' => url('/admin/courses/create'), 'createLabel' => 'New course',
            'rows' => $rows, 'pager' => $pager,
            'filters' => [
                ['name' => 'q', 'label' => 'Search title'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']],
                ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => array_filter($this->categoryOptions(), fn($k) => $k !== '', ARRAY_FILTER_USE_KEY)],
            ],
            'columns' => [
                ['label' => 'Title', 'render' => fn($r) => $r['title']],
                ['label' => 'Category', 'render' => fn($r) => $r['category_name'] ?? 'None'],
                ['label' => 'Cohorts', 'render' => fn($r) => $r['cohort_count']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
                ['label' => 'Updated', 'render' => fn($r) => local_date($r['updated_at'], null, 'j M Y')],
            ],
            'actions' => function ($r) {
                $a = [['label' => 'Edit', 'url' => url('/admin/courses/' . $r['id'] . '/edit')]];
                if ($r['status'] === 'published') {
                    $a[] = ['label' => 'View', 'url' => url('/courses/' . $r['slug'])];
                    $a[] = ['label' => 'Unpublish', 'url' => url('/admin/courses/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'draft']];
                } elseif ($r['status'] !== 'archived') {
                    $a[] = ['label' => 'Publish', 'url' => url('/admin/courses/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'published']];
                }
                if ($r['status'] !== 'archived') {
                    $a[] = ['label' => 'Archive', 'url' => url('/admin/courses/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'archived'], 'confirm' => 'Archive this course? It will disappear from the site.'];
                }
                return $a;
            },
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'New course', 'action' => url('/admin/courses'), 'fields' => $this->fields(), 'values' => [], 'cancelUrl' => url('/admin/courses'), 'multipart' => true]);
    }

    private function rowFrom(array $d, ?array $existing): array
    {
        $row = [
            'category_id' => $d['category_id'] ? (int) $d['category_id'] : null,
            'title' => $d['title'],
            'slug' => $this->uniqueSlug('courses', $d['slug'] ?: $d['title'], $existing ? (int) $existing['id'] : null),
            'short_description' => $d['short_description'], 'description' => $d['description'],
            'learning_objectives' => $d['learning_objectives'], 'prerequisites' => $d['prerequisites'],
            'default_duration_weeks' => $d['default_duration_weeks'] ? (int) $d['default_duration_weeks'] : null,
            'course_level' => $d['course_level'], 'status' => $d['status'],
        ];
        $thumb = (new UploadService())->image($this->request->file('thumbnail'));
        if ($thumb) { $row['thumbnail_url'] = $thumb; }
        if ($d['status'] === 'published' && empty($existing['published_at'])) { $row['published_at'] = utc_now(); }
        return $row;
    }

    public function store(): void
    {
        $d = $this->validate(self::RULES, '/admin/courses/create');
        $row = $this->rowFrom($d, null) + ['created_by_admin_id' => $this->adminId()];
        $id = Course::create($row);
        $this->audit('course.created', 'course', $id, null, $row);
        $this->done('Course created.', '/admin/courses');
    }

    public function edit(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $this->page('admin/crud/form', ['title' => 'Edit course', 'action' => url('/admin/courses/' . $c['id']), 'fields' => $this->fields(), 'values' => $c, 'cancelUrl' => url('/admin/courses'), 'multipart' => true]);
    }

    public function update(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(self::RULES, '/admin/courses/' . $c['id'] . '/edit');
        $row = $this->rowFrom($d, $c);
        Course::update((int) $c['id'], $row);
        $this->audit('course.updated', 'course', (int) $c['id'], $c, $row);
        $this->done('Course updated.', '/admin/courses');
    }

    public function status(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!in_array($new, ['draft', 'published', 'archived'], true)) { $this->abort(422); }
        $row = ['status' => $new];
        if ($new === 'published' && empty($c['published_at'])) { $row['published_at'] = utc_now(); }
        Course::update((int) $c['id'], $row);
        $this->audit('course.status_changed', 'course', (int) $c['id'], ['status' => $c['status']], $row);
        $this->done("Course set to {$new}.", '/admin/courses');
    }
}
