<?php
namespace App\Controllers\Public;

use App\Core\Controller;

class PageController extends Controller
{
    public function faq(): void { $this->view('public/pages/faq', ['title' => 'FAQs']); }
    public function privacy(): void { $this->view('public/pages/privacy', ['title' => 'Privacy policy']); }
    public function terms(): void { $this->view('public/pages/terms', ['title' => 'Terms of use']); }
    public function refund(): void { $this->view('public/pages/refund', ['title' => 'Refund & cancellation policy']); }
}
