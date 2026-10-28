<?php
namespace App\Services;

use App\Models\Enrolment;

/**
 * Decides whether a student may register for a cohort RIGHT NOW.
 * Pure rules, no writes. EnrolmentService calls this inside a DB transaction
 * (with the cohort row locked) so capacity can't be oversold.
 */
class RegistrationPolicyService
{
    /**
     * @return array{ok:bool, waitlist:bool, message:string}
     */
    public function check(array $cohort, int $seatsTaken, ?int $studentId = null): array
    {
        $deny = fn(string $m) => ['ok' => false, 'waitlist' => false, 'message' => $m];
        $now = utc_now();

        if (!in_array($cohort['status'], ['published', 'open', 'in_progress'], true)) {
            return $deny(match ($cohort['status']) {
                'closed'    => 'Registration for this cohort is closed.',
                'postponed' => 'This cohort has been postponed. Check back for the new date.',
                'cancelled' => 'This cohort has been cancelled.',
                'completed' => 'This cohort has ended.',
                default     => 'This cohort is not open for registration.',
            });
        }
        if (!(int) $cohort['registration_enabled']) {
            return $deny('Registration is currently disabled for this cohort.');
        }
        if ($cohort['registration_opens_at'] && $now < $cohort['registration_opens_at']) {
            return $deny('Registration opens on ' . local_date($cohort['registration_opens_at'], $cohort['timezone']) . '.');
        }
        if ($cohort['registration_closes_at'] && $now > $cohort['registration_closes_at']) {
            return $deny('The registration deadline has passed.');
        }
        if ($now >= $cohort['start_at'] && !(int) $cohort['allow_registration_after_start']) {
            return $deny('This cohort has already started and late registration is not allowed.');
        }
        if ($studentId && Enrolment::forStudentAndCohort($studentId, (int) $cohort['id'])) {
            return $deny('You are already registered for this cohort.');
        }

        $full = $cohort['capacity'] !== null && $seatsTaken >= (int) $cohort['capacity'];
        if ($full) {
            if ((int) $cohort['waitlist_enabled']) {
                return ['ok' => true, 'waitlist' => true, 'message' => 'This cohort is full. You can join the waitlist.'];
            }
            return $deny('This cohort is full.');
        }
        return ['ok' => true, 'waitlist' => false, 'message' => 'Registration is open.'];
    }
}
