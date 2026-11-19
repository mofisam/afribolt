<?php
namespace App\Controllers\Employer;

use App\Core\{Auth, Controller, Database};

class DashboardController extends Controller
{
    public function index(): void
    {
        $emp = Auth::user('employer');
        $id = (int) $emp['id'];
        $this->view('employer/dashboard', [
            'title' => 'Dashboard', 'emp' => $emp,
            'live' => (int) Database::value("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'published'", [$id]),
            'waiting' => (int) Database::value("SELECT COUNT(*) FROM jobs WHERE employer_id = ? AND status = 'pending'", [$id]),
            'applicants' => (int) Database::value('SELECT COUNT(*) FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ?', [$id]),
            'fresh' => (int) Database::value("SELECT COUNT(*) FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ? AND a.status = 'submitted'", [$id]),
        ], 'employer');
    }
}
