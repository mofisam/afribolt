<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Enrolment;

/**
 * The ONLY place a meeting URL leaves the database for a student.
 * Requirements 5.6: enrolment must be eligible AND now must be inside the joining window.
 */
class JoinAccessService
{
    /** @return array{ok:bool, message:string, url?:string, passcode?:?string} */
    public function urlFor(int $studentId, int $sessionId): array
    {
        $session = Database::one('SELECT * FROM class_sessions WHERE id = ? LIMIT 1', [$sessionId]);
        if (!$session) {
            return ['ok' => false, 'message' => 'Class not found.'];
        }
        $enrolment = Enrolment::forStudentAndCohort($studentId, (int) $session['cohort_id']);
        if (!$enrolment || $enrolment['status'] !== 'confirmed') {
            return ['ok' => false, 'message' => 'You are not enrolled in this cohort.'];
        }
        if (!in_array($session['status'], ['scheduled', 'live'], true)) {
            return ['ok' => false, 'message' => 'This class is ' . $session['status'] . '.'];
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $opens = utc_dt($session['starts_at'])->modify('-' . (int) $session['join_early_minutes'] . ' minutes');
        $closes = utc_dt($session['ends_at']);
        if ($now < $opens) {
            return ['ok' => false, 'message' => 'The class link opens at ' . local_date($opens->format('Y-m-d H:i:s'), $session['timezone'], 'g:i A') . '.'];
        }
        if ($now > $closes) {
            return ['ok' => false, 'message' => 'This class has ended.'];
        }
        if (empty($session['meeting_url'])) {
            return ['ok' => false, 'message' => 'The meeting link has not been added yet. Please check back shortly.'];
        }

        // Light attendance trail (optional feature): first join time
        Database::execute(
            "INSERT INTO session_attendance (session_id, enrolment_id, attendance_status, joined_at)
             VALUES (?, ?, 'present', UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE joined_at = COALESCE(joined_at, VALUES(joined_at))",
            [$sessionId, (int) $enrolment['id']]
        );
        $passcode = null;
        if (!empty($session['meeting_passcode_encrypted'])) {
            try { $passcode = \App\Core\Crypto::decrypt($session['meeting_passcode_encrypted']); } catch (\Throwable $e) { $passcode = null; }
        }
        return ['ok' => true, 'message' => 'OK', 'url' => $session['meeting_url'], 'passcode' => $passcode];
    }
}
