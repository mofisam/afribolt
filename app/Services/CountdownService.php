<?php
namespace App\Services;

use App\Models\ClassSession;

/**
 * Countdowns are calculated from stored timestamps, never stored as seconds.
 * We hand the browser a UTC ISO target; countdown.js ticks it down.
 */
class CountdownService
{
    /**
     * @return array{type:string,label:string,target:string,session?:array}|null
     */
    public function forEnrolment(array $enrolment): ?array
    {
        if (!in_array($enrolment['status'], ['confirmed', 'completed'], true) && $enrolment['status'] !== 'pending_payment') {
            return null;
        }
        $now = utc_now();
        if ($now < $enrolment['start_at']) {
            return ['type' => 'cohort', 'label' => 'Programme starts in', 'target' => utc_iso($enrolment['start_at'])];
        }
        $next = ClassSession::nextForCohort((int) $enrolment['cohort_id']);
        if ($next) {
            $live = $now >= $next['starts_at'];
            return [
                'type'    => 'session',
                'label'   => $live ? 'Class in progress, ends in' : 'Next class starts in',
                'target'  => utc_iso($live ? $next['ends_at'] : $next['starts_at']),
                'session' => $next,
            ];
        }
        return null;
    }
}
