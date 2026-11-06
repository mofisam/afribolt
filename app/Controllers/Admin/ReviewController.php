<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\CourseReview;
use App\Services\CourseCatalogService;

class ReviewController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($s = $this->request->query('status')) { $where[] = 'r.status = ?'; $p[] = $s; }
        if ($c = $this->request->query('course')) { $where[] = 'r.course_id = ?'; $p[] = (int) $c; }
        if ($m = $this->request->query('rating')) { $where[] = 'r.rating = ?'; $p[] = (int) $m; }
        $courses = array_column(Database::all('SELECT id, title FROM courses WHERE deleted_at IS NULL ORDER BY title'), 'title', 'id');
        [$rows, $pager] = $this->paged('course_reviews r JOIN courses c ON c.id = r.course_id JOIN users u ON u.id = r.user_id WHERE ' . implode(' AND ', $where), $p,
            'r.*, c.title AS course_title, u.first_name, u.last_name', 'r.created_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Course reviews', 'rows' => $rows, 'pager' => $pager,
            'intro' => 'Hide reviews that are abusive or off-topic. Hidden reviews stop counting towards the course rating.',
            'filters' => [['name' => 'course', 'label' => 'Course', 'type' => 'select', 'options' => $courses],
                ['name' => 'rating', 'label' => 'Rating', 'type' => 'select', 'options' => [5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => ['published' => 'Published', 'hidden' => 'Hidden']]],
            'columns' => [
                ['label' => 'Course', 'render' => fn($r) => $r['course_title']],
                ['label' => 'Learner', 'render' => fn($r) => $r['first_name'] . ' ' . $r['last_name']],
                ['label' => 'Rating', 'html' => true, 'render' => fn($r) => '<span aria-label="' . (int) $r['rating'] . ' out of 5" style="color:#e59819">' . str_repeat('&#9733;', (int) $r['rating']) . '</span><span style="color:#cbd5e1">' . str_repeat('&#9733;', 5 - (int) $r['rating']) . '</span>'],
                ['label' => 'Comment', 'render' => fn($r) => mb_strimwidth((string) $r['comment'], 0, 140, '...')],
                ['label' => 'Date', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y')],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'] === 'hidden' ? 'closed' : 'published') ],
            ],
            'actions' => fn($r) => [['label' => $r['status'] === 'published' ? 'Hide' : 'Show', 'url' => url('/admin/reviews/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => $r['status'] === 'published' ? 'hidden' : 'published']]],
        ]);
    }

    public function status(string $id): void
    {
        $r = CourseReview::find((int) $id) ?? $this->abort(404);
        $new = $this->request->post('status') === 'hidden' ? 'hidden' : 'published';
        CourseReview::update((int) $r['id'], ['status' => $new]);
        $this->audit('review.' . $new, 'course_review', (int) $r['id'], ['status' => $r['status']], ['status' => $new]);
        CourseCatalogService::refresh((int) $r['course_id']);
        $this->done("Review {$new}.", '/admin/reviews');
    }
}
