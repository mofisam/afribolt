<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Session};
use App\Services\{CareerLaunchPricing, CareerLaunchService, PaymentService};

class CareerLaunchController extends Controller
{
    /** POST /student/career-launch/pay: start Paystack checkout at the current price, then redirect to Paystack. */
    public function pay(): void
    {
        $student = Auth::user('student');
        $reg = CareerLaunchService::forUser((int) $student['id']);
        if (!$reg) { Session::flash('error', 'Join Career Launch first.'); $this->redirect('/career-accelerator#register'); }
        if ($reg['status'] === 'paid') { Session::flash('success', 'You have already paid. Your stages are unlocked.'); $this->redirect('/student/dashboard'); }
        if ($reg['status'] === 'cancelled') { Session::flash('error', 'This registration was cancelled. Please contact us.'); $this->redirect('/student/dashboard'); }
        if (!CareerLaunchService::paystackReady()) {
            Session::flash('error', 'Online payment is not switched on yet. Please use the payment link we send you, or contact us.');
            $this->redirect('/student/dashboard');
        }
        $price = CareerLaunchPricing::current()['price'];
        try {
            $this->redirect((new PaymentService())->initiateCareerLaunch($reg, $student, (float) $price));
        } catch (\RuntimeException $e) {
            error_log('Career Launch payment start failed: ' . $e->getMessage());
            Session::flash('error', 'We could not start the payment. Please try again shortly.');
            $this->redirect('/student/dashboard');
        }
    }
}
