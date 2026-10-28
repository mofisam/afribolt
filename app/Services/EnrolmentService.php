<?php
namespace App\Services;

use App\Core\Database;
use App\Models\{Cohort, Enrolment};

class EnrolmentService
{
    public function __construct(
        private RegistrationPolicyService $policy = new RegistrationPolicyService(),
        private NotificationService $notify = new NotificationService()
    ) {}

    /**
     * Create an enrolment safely.
     * The cohort row is locked (SELECT ... FOR UPDATE) so two students can't take the last seat together.
     *
     * @return array{ok:bool, message:string, enrolment_id?:int, status?:string}
     */
    public function register(int $studentId, int $cohortId): array
    {
        return Database::transaction(function () use ($studentId, $cohortId) {
            $cohort = Cohort::lockById($cohortId);
            if (!$cohort) {
                return ['ok' => false, 'message' => 'Cohort not found.'];
            }
            $seats = Cohort::seatsTaken($cohortId);
            $verdict = $this->policy->check($cohort, $seats, $studentId);
            if (!$verdict['ok']) {
                return ['ok' => false, 'message' => $verdict['message']];
            }

            $fee = (float) $cohort['fee_amount'];
            if ($verdict['waitlist']) {
                $status = 'waitlisted';
            } elseif ((int) $cohort['require_payment'] && $fee > 0) {
                $status = 'pending_payment';
            } else {
                $status = 'confirmed'; // free cohort
            }

            $id = Enrolment::create([
                'enrolment_number' => 'AFB-' . gmdate('ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'student_id'       => $studentId,
                'cohort_id'        => $cohortId,
                'status'           => $status,
                'fee_amount'       => $fee,
                'currency'         => $cohort['currency'],
                'confirmed_at'     => $status === 'confirmed' ? utc_now() : null,
            ]);

            $this->notify->toStudent($studentId, 'enrolment_created', 'Registration received',
                match ($status) {
                    'confirmed'       => "You're registered for {$cohort['cohort_name']}.",
                    'waitlisted'      => "You're on the waitlist for {$cohort['cohort_name']}.",
                    default           => "Complete payment to confirm your seat in {$cohort['cohort_name']}.",
                }, 'enrolment', $id, '/student/dashboard');

            return ['ok' => true, 'message' => 'Registration received.', 'enrolment_id' => $id, 'status' => $status];
        });
    }

    private const HOLDS = ['pending_payment', 'pending_review', 'confirmed'];

    /** Admin: confirm manually (e.g. bank transfer received). */
    public function confirm(int $id, string $note): array { return $this->moveTo($id, 'confirmed', $note, 'enrolment.confirmed_manually'); }

    /** Admin: waitlisted -> pending_payment (or confirmed if free). */
    public function promote(int $id, string $note = ''): array
    {
        $en = Enrolment::find($id);
        if (!$en || $en['status'] !== 'waitlisted') { return ['ok' => false, 'message' => 'Only waitlisted enrolments can be promoted.']; }
        $cohort = Cohort::find((int) $en['cohort_id']);
        $to = ((int) $cohort['require_payment'] && (float) $en['fee_amount'] > 0) ? 'pending_payment' : 'confirmed';
        return $this->moveTo($id, $to, $note ?: 'Promoted from waitlist', 'enrolment.promoted');
    }

    public function cancel(int $id, string $note): array { return $this->moveTo($id, 'cancelled', $note, 'enrolment.cancelled'); }
    public function complete(int $id): array { return $this->moveTo($id, 'completed', '', 'enrolment.completed'); }

    private function moveTo(int $id, string $new, string $note, string $auditAction): array
    {
        return Database::transaction(function () use ($id, $new, $note, $auditAction) {
            $en = Database::one('SELECT * FROM enrolments WHERE id = ? FOR UPDATE', [$id]);
            if (!$en) { return ['ok' => false, 'message' => 'Enrolment not found.']; }
            $cohort = Cohort::lockById((int) $en['cohort_id']);
            if (in_array($new, self::HOLDS, true) && !in_array($en['status'], self::HOLDS, true)
                && $cohort['capacity'] !== null && Cohort::seatsTaken((int) $cohort['id']) >= (int) $cohort['capacity']) {
                return ['ok' => false, 'message' => 'The cohort is full. Increase capacity first.'];
            }
            $adminId = \App\Core\Auth::id('admin');
            $line = '[' . utc_now() . ' UTC, admin #' . $adminId . '] ' . $auditAction . ($note !== '' ? ': ' . $note : '');
            $data = ['status' => $new, 'admin_notes' => trim(($en['admin_notes'] ?? '') . "\n" . $line)];
            if ($new === 'confirmed') { $data['confirmed_at'] = utc_now(); }
            if ($new === 'cancelled') { $data['cancelled_at'] = utc_now(); }
            if ($new === 'completed') { $data['completed_at'] = utc_now(); }
            Enrolment::update($id, $data);
            AuditService::log($auditAction, 'enrolment', $id, ['status' => $en['status']], ['status' => $new, 'note' => $note]);

            $msg = match ($new) {
                'confirmed' => "Your seat in {$cohort['cohort_name']} is confirmed.",
                'pending_payment' => "A seat opened up in {$cohort['cohort_name']}. Pay to secure it.",
                'cancelled' => "Your registration for {$cohort['cohort_name']} was cancelled.",
                default => "Your enrolment in {$cohort['cohort_name']} was updated.",
            };
            $this->notify->toStudent((int) $en['student_id'], 'enrolment_' . $new, 'Enrolment update', $msg, 'enrolment', $id, '/student/dashboard');
            return ['ok' => true, 'message' => 'Enrolment updated.'];
        });
    }
}
