<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\{Cohort, Course, Job};

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('public/home', [
            'title'      => 'Learn at your own pace. Get hired.',
            'courses'    => Course::search(['mode' => 'self_paced', 'sort' => 'popular'], 8),
            'courseTotal' => Course::searchCount(['mode' => 'self_paced']),
            'categories' => Course::categoryCounts(),
            'jobs'       => Job::latest(6),
            'jobTotal'   => Job::searchCount([]),
            'cohorts'    => array_slice(Cohort::publicList(), 0, 3),
        ]);
    }
}
