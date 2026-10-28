<?php
namespace App\Controllers\Public;

use App\Core\Controller;

class AboutController extends Controller
{
    public function index(): void
    {
        $this->view('public/about', ['title' => 'About AfriBolt Technologies']);
    }
}
