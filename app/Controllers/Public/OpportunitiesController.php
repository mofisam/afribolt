<?php
namespace App\Controllers\Public;

use App\Core\Controller;

/** Career Accelerator pages (CV revamp, interview coaching, internships, job support). Copy lives in the views. */
class OpportunitiesController extends Controller
{
    public function index(): void
    {
        $this->view('public/opportunities', ['title' => 'Career Accelerator']);
    }
}
