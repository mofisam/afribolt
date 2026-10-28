<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Session};
use App\Models\Cohort;
use App\Services\EnrolmentService;

class EnrolmentController extends Controller
{
    /** POST /cohorts/{code}/register */
    public function register(string $code): void
    {
        $student = Auth::user('student');
        $cohort = Cohort::findPublicByCode($code) ?? $this->abort(404);

        if (config('security.require_email_verification') && empty($student['email_verified_at'])) {
            Session::flash('error', 'Please verify your email address before registering.');
            $this->redirect('/student/dashboard');
        }
        if (!$this->request->post('accept_terms')) {
            Session::flash('error', 'Please accept the terms to continue.');
            $this->redirect('/cohorts/' . $code);
        }

        $result = (new EnrolmentService())->register((int) $student['id'], (int) $cohort['id']);
        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            $this->redirect('/cohorts/' . $code);
        }
        Session::flash('success', $result['status'] === 'pending_payment'
            ? 'Registration received. Complete payment to confirm your seat.'
            : ($result['status'] === 'waitlisted' ? 'You are on the waitlist. We will notify you if a seat opens.' : 'You are registered!'));
        $this->redirect('/student/dashboard');
    }
}
