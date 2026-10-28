<?php
namespace App\Services;

use App\Models\{Cohort, ClassSession};

/** Reschedule / postpone / cancel a session, audit it, and tell affected students. */
class ScheduleService
{
    public function __construct(private NotificationService $notify = new NotificationService()) {}

    public function reschedule(int $sessionId, string $newStartsAtUtc, string $newEndsAtUtc, ?string $newMeetingUrl = null): void
    {
        $old = ClassSession::find($sessionId);
        if (!$old) { return; }
        $data = ['starts_at' => $newStartsAtUtc, 'ends_at' => $newEndsAtUtc, 'status' => 'scheduled'];
        if ($newMeetingUrl !== null) { $data['meeting_url'] = $newMeetingUrl; }
        ClassSession::update($sessionId, $data);

        AuditService::log('session.rescheduled', 'class_session', $sessionId,
            ['starts_at' => $old['starts_at'], 'ends_at' => $old['ends_at']], $data);

        $cohort = Cohort::find((int) $old['cohort_id']);
        $this->notify->toCohort((int) $old['cohort_id'], 'session_rescheduled', 'Class rescheduled',
            "{$old['title']} now starts " . local_date($newStartsAtUtc, $old['timezone']) . '.', '/student/dashboard');
        // Countdown + join window recalculate automatically from the new starts_at.
    }

    public function setStatus(int $sessionId, string $status): void
    {
        if (!in_array($status, ['postponed', 'cancelled', 'completed', 'live', 'scheduled'], true)) { return; }
        $old = ClassSession::find($sessionId);
        if (!$old) { return; }
        ClassSession::update($sessionId, ['status' => $status]);
        AuditService::log('session.' . $status, 'class_session', $sessionId, ['status' => $old['status']], ['status' => $status]);
        if (in_array($status, ['postponed', 'cancelled'], true)) {
            $this->notify->toCohort((int) $old['cohort_id'], 'session_' . $status, 'Class ' . $status,
                "{$old['title']} has been {$status}.", '/student/dashboard');
        }
    }
}
