<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Session};
use App\Models\{Enrolment, Payment};
use App\Services\PaymentService;

class PaymentController extends Controller
{
    /** POST /student/enrolments/{id}/pay: create attempt, redirect to the gateway. */
    public function start(string $id): void
    {
        $student = Auth::user('student');
        $enrolment = Enrolment::findOwned((int) $id, (int) $student['id']) ?? $this->abort(404);
        try {
            $this->redirect((new PaymentService())->initiate($enrolment, $student));
        } catch (\RuntimeException $e) {
            error_log('Payment start failed: ' . $e->getMessage());
            Session::flash('error', 'We could not start the payment. Please try again shortly.');
            $this->redirect('/student/dashboard');
        }
    }

    /** GET /student/payments/callback?reference=...: browser returns here; we still verify server-side. */
    public function callback(): void
    {
        $reference = (string) ($this->request->query('reference') ?? $this->request->query('trxref') ?? '');
        $payment = $reference ? Payment::findByReference($reference) : null;
        $enrolment = $payment ? Enrolment::findOwned((int) $payment['enrolment_id'], (int) Auth::id('student')) : null;
        if (!$enrolment) {
            Session::flash('error', 'Payment reference not found.');
            $this->redirect('/student/dashboard');
        }
        $result = (new PaymentService())->verifyAndConfirm($reference);
        Session::flash($result['ok'] ? 'success' : 'error', $result['ok'] ? 'Payment confirmed. Your seat is secured!' : $result['message']);
        $this->redirect('/student/dashboard');
    }
}
