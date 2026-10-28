<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\CourseCategory;

class CategoryController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'required' => true, 'half' => true],
            ['name' => 'slug', 'label' => 'URL slug', 'half' => true, 'help' => 'Leave blank to generate from the name.'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'display_order', 'label' => 'Display order', 'type' => 'number', 'half' => true, 'default' => 0, 'min' => 0],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'half' => true, 'options' => ['active' => 'Active', 'inactive' => 'Inactive'], 'default' => 'active'],
        ];
    }

    private const RULES = ['name' => 'required|max:150', 'slug' => 'max:180', 'description' => 'max:1000', 'display_order' => 'integer', 'status' => 'required|in:active,inactive'];

    public function index(): void
    {
        $rows = Database::all("SELECT c.*, (SELECT COUNT(*) FROM courses x WHERE x.category_id = c.id AND x.deleted_at IS NULL) AS course_count FROM course_categories c ORDER BY display_order, name");
        $this->page('admin/crud/index', [
            'title' => 'Course categories', 'createUrl' => url('/admin/categories/create'), 'createLabel' => 'New category',
            'rows' => $rows,
            'columns' => [
                ['label' => 'Name', 'render' => fn($r) => $r['name']],
                ['label' => 'Slug', 'render' => fn($r) => $r['slug']],
                ['label' => 'Order', 'render' => fn($r) => $r['display_order']],
                ['label' => 'Courses', 'render' => fn($r) => $r['course_count']],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
            ],
            'actions' => fn($r) => [
                ['label' => 'Edit', 'url' => url('/admin/categories/' . $r['id'] . '/edit')],
                ['label' => $r['status'] === 'active' ? 'Deactivate' : 'Activate', 'url' => url('/admin/categories/' . $r['id'] . '/toggle'), 'post' => true],
            ],
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'New category', 'action' => url('/admin/categories'), 'fields' => $this->fields(), 'values' => [], 'cancelUrl' => url('/admin/categories')]);
    }

    public function store(): void
    {
        $d = $this->validate(self::RULES, '/admin/categories/create');
        $row = [
            'name' => $d['name'], 'slug' => $this->uniqueSlug('course_categories', $d['slug'] ?: $d['name']),
            'description' => $d['description'], 'display_order' => (int) ($d['display_order'] ?? 0), 'status' => $d['status'],
        ];
        $id = CourseCategory::create($row);
        $this->audit('category.created', 'course_category', $id, null, $row);
        $this->done('Category created.', '/admin/categories');
    }

    public function edit(string $id): void
    {
        $cat = CourseCategory::find((int) $id) ?? $this->abort(404);
        $this->page('admin/crud/form', ['title' => 'Edit category', 'action' => url('/admin/categories/' . $cat['id']), 'fields' => $this->fields(), 'values' => $cat, 'cancelUrl' => url('/admin/categories')]);
    }

    public function update(string $id): void
    {
        $cat = CourseCategory::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(self::RULES, '/admin/categories/' . $cat['id'] . '/edit');
        $row = [
            'name' => $d['name'], 'slug' => $this->uniqueSlug('course_categories', $d['slug'] ?: $d['name'], (int) $cat['id']),
            'description' => $d['description'], 'display_order' => (int) ($d['display_order'] ?? 0), 'status' => $d['status'],
        ];
        CourseCategory::update((int) $cat['id'], $row);
        $this->audit('category.updated', 'course_category', (int) $cat['id'], $cat, $row);
        $this->done('Category updated.', '/admin/categories');
    }

    public function toggle(string $id): void
    {
        $cat = CourseCategory::find((int) $id) ?? $this->abort(404);
        $new = $cat['status'] === 'active' ? 'inactive' : 'active';
        CourseCategory::update((int) $cat['id'], ['status' => $new]);
        $this->audit('category.status_changed', 'course_category', (int) $cat['id'], ['status' => $cat['status']], ['status' => $new]);
        $this->done("Category {$new}.", '/admin/categories');
    }
}
