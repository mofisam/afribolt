<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\{Cohort, Course};

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('public/home', [
            'title'   => 'Learn live. Get hired.',
            'courses' => array_slice(Course::published(), 0, 3),
            'cohorts' => array_slice(Cohort::publicList(), 0, 3),
        ]);
    }
}
