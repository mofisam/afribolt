<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Enrolment, Payment, PaymentGateway, User};
use App\Services\Gateways\{GatewayInterface, PaystackGateway};

class PaymentService
{
    private GatewayInterface $gateway;

    public function __construct(?GatewayInterface $gateway = null)
    {
        $this->gateway = $gateway ?? new PaystackGateway(); // pick by config('payment.provider') when more adapters exist
    }

    public function gateway(): GatewayInterface { return $this->gateway; }

    /** Create a payment attempt and return the provider checkout URL. */
    public function initiate(array $enrolment, array $student): string
    {
        $due = round((float) $enrolment['fee_amount'] - (float) $enrolment['amount_paid'], 2);
        if ($enrolment['status'] !== 'pending_payment' || $due <= 0) {
            throw new \RuntimeException('Nothing to pay for this enrolment.');
        }
        $gw = PaymentGateway::findBy('provider_code', $this->gateway->code());
        if ($gw && !(int) $gw['is_enabled']) {
            throw new \RuntimeException('Payments are currently disabled.');
        }
        $ref = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));

        Payment::create([
            'enrolment_id' => (int) $enrolment['id'], 'gateway_id' => $gw['id'] ?? null,
            'payment_type' => 'course_fee', 'internal_reference' => $ref, 'provider_reference' => $ref,
            'amount' => $due, 'currency' => $enrolment['currency'], 'status' => 'initiated',
        ]);
        $init = $this->gateway->initialize($student['email'], $due, $enrolment['currency'], $ref, url('/student/payments/callback'));
        return $init['authorization_url'];
    }

    /** Start checkout for a self-paced course purchase (no cohort enrolment involved). */
    public function initiateCourse(array $access, array $course, array $student, float $amount): string
    {
        $gw = PaymentGateway::findBy('provider_code', $this->gateway->code());
        if ($gw && !(int) $gw['is_enabled']) {
            throw new \RuntimeException('Payments are currently disabled.');
        }
        $ref = 'PAY-' . strtoupper(bin2hex(random_bytes(8)));
        Payment::create([
            'enrolment_id' => null, 'course_access_id' => (int) $access['id'], 'gateway_id' => $gw['id'] ?? null,
            'payment_type' => 'course_fee', 'internal_reference' => $ref, 'provider_reference' => $ref,
            'amount' => $amount, 'currency' => $course['price_currency'], 'status' => 'initiated',
        ]);
        $init = $this->gateway->initialize($student['email'], $amount, $course['price_currency'], $ref, url('/student/payments/callback'));
        return $init['authorization_url'];
    }

    /**
     * Called by BOTH the browser callback and the webhook. Safe to call repeatedly (idempotent).
     * An enrolment is only confirmed after the provider confirms amount + currency server-side.
     */
    public function verifyAndConfirm(string $reference): array
    {
        $payment = Payment::findByReference($reference);
        if (!$payment) {
            return ['ok' => false, 'message' => 'Unknown payment reference.'];
        }
        if ($payment['status'] === 'successful') {
            return ['ok' => true, 'message' => 'Payment already confirmed.'];
        }

        $result = $this->gateway->verify($reference); // network call OUTSIDE the DB transaction

        return Database::transaction(function () use ($reference, $result) {
            $payment = Payment::lockByReference($reference);
            if ($payment['status'] === 'successful') {
                return ['ok' => true, 'message' => 'Payment already confirmed.'];
            }
            $matches = $result['success']
                && abs($result['amount'] - (float) $payment['amount']) < 0.01
                && strtoupper($result['currency']) === strtoupper($payment['currency']);

            if (!$matches) {
                Payment::update((int) $payment['id'], [
                    'status' => 'failed',
                    'failure_reason' => $result['success'] ? 'Amount or currency mismatch' : 'Provider reported not successful',
                    'provider_response' => json_encode($result['raw']),
                ]);
                return ['ok' => false, 'message' => 'Payment could not be verified.'];
            }

            Payment::update((int) $payment['id'], [
                'status' => 'successful', 'paid_at' => utc_now(), 'payment_method' => $result['method'],
                'provider_transaction_id' => $result['transaction_id'], 'provider_response' => json_encode($result['raw']),
            ]);

            if (empty($payment['enrolment_id'])) { // self-paced course purchase
                (new CourseAccessService())->activate((int) $payment['course_access_id'], (float) $payment['amount'], $payment['currency']);
                return ['ok' => true, 'message' => 'Payment confirmed.'];
            }
            $enrol = Database::one('SELECT * FROM enrolments WHERE id = ? FOR UPDATE', [(int) $payment['enrolment_id']]);
            $paid = round((float) $enrol['amount_paid'] + (float) $payment['amount'], 2);
            $fullyPaid = $paid >= (float) $enrol['fee_amount'];
            // If the seat was already released (enrolment auto-cancelled while the student paid late),
            // don't silently re-confirm: flag it for an admin to review/refund.
            $seatReleased = in_array($enrol['status'], ['cancelled', 'refunded'], true);
            $confirm = $fullyPaid && !$seatReleased;
            Enrolment::update((int) $enrol['id'], [
                'amount_paid' => $paid,
                'status' => $seatReleased ? 'pending_review' : ($fullyPaid ? 'confirmed' : $enrol['status']),
                'confirmed_at' => $confirm ? utc_now() : $enrol['confirmed_at'],
            ]);

            if ($confirm) {
                $student = User::find((int) $enrol['student_id']);
                (new NotificationService())->toStudent((int) $enrol['student_id'], 'payment_confirmed', 'Payment confirmed',
                    'Your seat is confirmed. See you in class!', 'enrolment', (int) $enrol['id'], '/student/dashboard');
                if ($student) {
                    (new MailService())->queue((int) $student['id'], $student['email'], 'payment-confirmed',
                        'Payment confirmed', ['student' => $student, 'enrolment' => $enrol, 'payment' => $payment]);
                }
            }
            return ['ok' => true, 'message' => 'Payment confirmed.'];
        });
    }

    /**
     * Record a refund that was made outside the platform (bank transfer / provider dashboard).
     * Does NOT call the gateway. Build gateway refunds once the refund policy is confirmed.
     */
    public function recordManualRefund(int $paymentId, float $amount, string $note): array
    {
        return Database::transaction(function () use ($paymentId, $amount, $note) {
            $p = Database::one('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
            if (!$p || !in_array($p['status'], ['successful', 'partially_refunded'], true)) {
                return ['ok' => false, 'message' => 'Only successful payments can be refunded.'];
            }
            $max = round((float) $p['amount'] - (float) $p['refunded_amount'], 2);
            if ($amount <= 0 || $amount > $max) {
                return ['ok' => false, 'message' => 'Refund must be between 0.01 and ' . number_format($max, 2) . '.'];
            }
            $refunded = round((float) $p['refunded_amount'] + $amount, 2);
            Payment::update($paymentId, [
                'refunded_amount' => $refunded,
                'status' => $refunded >= (float) $p['amount'] ? 'refunded' : 'partially_refunded',
            ]);
            if (empty($p['enrolment_id'])) { // self-paced course payment: a full refund ends access
                if ($refunded >= (float) $p['amount']) {
                    \App\Models\CourseAccess::update((int) $p['course_access_id'], ['status' => 'refunded']);
                    $cid = (int) Database::value('SELECT course_id FROM course_access WHERE id = ?', [(int) $p['course_access_id']]);
                    CourseCatalogService::refresh($cid);
                }
                AuditService::log('payment.refund_recorded', 'payment', $paymentId, ['refunded_amount' => $p['refunded_amount']], ['refunded_amount' => $refunded, 'note' => $note]);
                return ['ok' => true, 'message' => 'Refund recorded.'];
            }
            $en = Database::one('SELECT * FROM enrolments WHERE id = ? FOR UPDATE', [(int) $p['enrolment_id']]);
            $paid = max(0, round((float) $en['amount_paid'] - $amount, 2));
            $data = ['amount_paid' => $paid, 'admin_notes' => trim(($en['admin_notes'] ?? '') . "\n[" . utc_now() . ' UTC] refund recorded: ' . $amount . ($note !== '' ? " ({$note})" : ''))];
            if ($paid <= 0) { $data['status'] = 'refunded'; }
            Enrolment::update((int) $en['id'], $data);
            AuditService::log('payment.refund_recorded', 'payment', $paymentId, ['refunded_amount' => $p['refunded_amount']], ['refunded_amount' => $refunded, 'note' => $note]);
            return ['ok' => true, 'message' => 'Refund recorded.'];
        });
    }
}
