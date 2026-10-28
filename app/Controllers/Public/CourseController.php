<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\{Cohort, Course, CourseCategory};

class CourseController extends Controller
{
    public function index(): void
    {
        $category = (string) $this->request->query('category', '');
        $this->view('public/courses/index', [
            'title'      => 'Courses',
            'courses'    => Course::published($category ?: null),
            'categories' => CourseCategory::all('display_order', 'ASC'),
            'activeCategory' => $category,
        ]);
    }

    public function show(string $slug): void
    {
        $course = Course::findPublishedBySlug($slug) ?? $this->abort(404);
        $this->view('public/courses/show', [
            'title'   => $course['title'],
            'course'  => $course,
            'cohorts' => Cohort::publicList((int) $course['id']),
        ]);
    }
}
