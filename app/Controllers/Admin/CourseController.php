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
        'delivery_mode' => 'required|in:live,self_paced,both', 'instructor_id' => 'integer', 'price' => 'numeric', 'price_currency' => 'required|min:3|max:3',
        'promo_price' => 'numeric', 'promo_ends_at' => 'date', 'preview_video_url' => 'url', 'language' => 'max:40',
    ];

    private function categoryOptions(): array
    {
        $o = ['' => 'No category'];
        foreach (CourseCategory::all('display_order', 'ASC') as $c) { $o[$c['id']] = $c['name'] . ($c['status'] === 'inactive' ? ' (inactive)' : ''); }
        return $o;
    }

    private function instructorOptions(): array
    {
        $o = ['' => 'None'];
        foreach (Database::all("SELECT id, first_name, last_name FROM instructors WHERE account_status <> 'suspended' ORDER BY first_name") as $i) { $o[$i['id']] = $i['first_name'] . ' ' . $i['last_name']; }
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
            ['type' => 'heading', 'label' => 'How it is taught and sold'],
            ['name' => 'delivery_mode', 'label' => 'Delivery', 'type' => 'select', 'default' => 'live', 'options' => ['live' => 'Live cohorts only', 'self_paced' => 'Self-paced only (on demand)', 'both' => 'Both live cohorts and self-paced'],
                'help' => 'Self-paced courses need a curriculum (sections and lessons). Save the course, then use the Curriculum link.'],
            ['name' => 'instructor_id', 'label' => 'Instructor shown on the course page', 'type' => 'select', 'options' => $this->instructorOptions(), 'half' => true],
            ['name' => 'language', 'label' => 'Language', 'half' => true, 'default' => 'English'],
            ['name' => 'price', 'label' => 'Self-paced price', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'half' => true, 'default' => '0', 'help' => '0 = free to enrol. Live cohort fees are set on each cohort.'],
            ['name' => 'price_currency', 'label' => 'Currency (3 letters)', 'half' => true, 'default' => $this->defaultCurrency()],
            ['name' => 'promo_price', 'label' => 'Sale price (optional)', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'half' => true],
            ['name' => 'promo_ends_at', 'label' => 'Sale ends (optional)', 'type' => 'datetime', 'half' => true, 'help' => 'Times are in ' . $this->defaultTz() . '. Shows a countdown on the course page.'],
            ['name' => 'preview_video_url', 'label' => 'Promo video link (optional)', 'type' => 'url', 'help' => 'YouTube, Vimeo or a direct .mp4 link. Shown on the course page.'],
            ['name' => 'has_certificate', 'label' => 'Issue a certificate of completion', 'type' => 'checkbox', 'default' => 1],
            ['name' => 'cohort_includes_access', 'label' => 'Live cohort students also get the on-demand lessons', 'type' => 'checkbox', 'default' => 1],
            ['type' => 'heading', 'label' => 'Listing'],
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
                ['label' => 'Delivery', 'render' => fn($r) => ['live' => 'Live', 'self_paced' => 'Self-paced', 'both' => 'Live + self-paced'][$r['delivery_mode']] ?? $r['delivery_mode']],
                ['label' => 'Cohorts / lessons', 'render' => fn($r) => $r['cohort_count'] . ' / ' . $r['lessons_count']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
                ['label' => 'Updated', 'render' => fn($r) => local_date($r['updated_at'], null, 'j M Y')],
            ],
            'actions' => function ($r) {
                $a = [['label' => 'Edit', 'url' => url('/admin/courses/' . $r['id'] . '/edit')]];
                if ($r['delivery_mode'] !== 'live') { $a[] = ['label' => 'Curriculum', 'url' => url('/admin/courses/' . $r['id'] . '/curriculum')]; }
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

    private function rowFrom(array $d, ?array $existing, string $back): array
    {
        $tz = $this->defaultTz();
        $price = round((float) ($d['price'] ?? 0), 2);
        $promo = $d['promo_price'] !== null ? round((float) $d['promo_price'], 2) : null;
        $errors = [];
        if ($price < 0) { $errors['price'] = 'Price cannot be negative.'; }
        if ($promo !== null && $promo >= $price) { $errors['promo_price'] = 'The sale price must be lower than the normal price.'; }
        if ($d['preview_video_url'] && !\App\Services\VideoService::parse($d['preview_video_url'])) { $errors['preview_video_url'] = 'Use a YouTube, Vimeo or direct https .mp4/.webm link.'; }
        $selfPaced = in_array($d['delivery_mode'], ['self_paced', 'both'], true);
        if ($d['status'] === 'published' && $selfPaced && (int) ($existing['lessons_count'] ?? 0) < 1) {
            $errors['status'] = 'Add at least one published lesson in the curriculum before publishing a self-paced course. Save it as a draft first.';
        }
        if ($errors) {
            \App\Core\Session::flashInput($this->request->all(), $errors);
            \App\Core\Session::flash('error', 'Please fix the highlighted fields.');
            $this->redirect($back);
        }
        $row = [
            'category_id' => $d['category_id'] ? (int) $d['category_id'] : null,
            'title' => $d['title'],
            'slug' => $this->uniqueSlug('courses', $d['slug'] ?: $d['title'], $existing ? (int) $existing['id'] : null),
            'short_description' => $d['short_description'], 'description' => $d['description'],
            'learning_objectives' => $d['learning_objectives'], 'prerequisites' => $d['prerequisites'],
            'default_duration_weeks' => $d['default_duration_weeks'] ? (int) $d['default_duration_weeks'] : null,
            'course_level' => $d['course_level'], 'status' => $d['status'],
            'delivery_mode' => $d['delivery_mode'], 'instructor_id' => $d['instructor_id'] ? (int) $d['instructor_id'] : null,
            'price' => $price, 'price_currency' => strtoupper($d['price_currency']), 'promo_price' => $promo,
            'promo_ends_at' => $promo !== null ? $this->fromInput($d['promo_ends_at'], $tz) : null,
            'preview_video_url' => $d['preview_video_url'], 'language' => $d['language'] ?: 'English',
            'has_certificate' => $this->checkbox('has_certificate'), 'cohort_includes_access' => $this->checkbox('cohort_includes_access'),
        ];
        $thumb = (new UploadService())->image($this->request->file('thumbnail'));
        if ($thumb) { $row['thumbnail_url'] = $thumb; }
        if ($d['status'] === 'published' && empty($existing['published_at'])) { $row['published_at'] = utc_now(); }
        return $row;
    }

    public function store(): void
    {
        $d = $this->validate(self::RULES, '/admin/courses/create');
        $row = $this->rowFrom($d, null, '/admin/courses/create') + ['created_by_admin_id' => $this->adminId()];
        $id = Course::create($row);
        $this->audit('course.created', 'course', $id, null, $row);
        $this->done('Course created.', '/admin/courses');
    }

    public function edit(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $c['promo_ends_at'] = $this->toInput($c['promo_ends_at'], $this->defaultTz());
        $this->page('admin/crud/form', ['title' => 'Edit course', 'action' => url('/admin/courses/' . $c['id']), 'fields' => $this->fields(), 'values' => $c, 'cancelUrl' => url('/admin/courses'), 'multipart' => true]);
    }

    public function update(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(self::RULES, '/admin/courses/' . $c['id'] . '/edit');
        $row = $this->rowFrom($d, $c, '/admin/courses/' . $c['id'] . '/edit');
        Course::update((int) $c['id'], $row);
        $this->audit('course.updated', 'course', (int) $c['id'], $c, $row);
        $this->done('Course updated.', '/admin/courses');
    }

    public function status(string $id): void
    {
        $c = Course::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!in_array($new, ['draft', 'published', 'archived'], true)) { $this->abort(422); }
        if ($new === 'published' && in_array($c['delivery_mode'], ['self_paced', 'both'], true) && (int) $c['lessons_count'] < 1) {
            $this->done('Add at least one published lesson before publishing a self-paced course.', '/admin/courses/' . $c['id'] . '/curriculum', 'error');
        }
        $row = ['status' => $new];
        if ($new === 'published' && empty($c['published_at'])) { $row['published_at'] = utc_now(); }
        Course::update((int) $c['id'], $row);
        $this->audit('course.status_changed', 'course', (int) $c['id'], ['status' => $c['status']], $row);
        $this->done("Course set to {$new}.", '/admin/courses');
    }
}
