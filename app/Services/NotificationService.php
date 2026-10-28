<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Notification;

class NotificationService
{
    public function toStudent(int $userId, string $type, string $title, string $message, ?string $entityType = null, ?int $entityId = null, ?string $actionUrl = null): void
    {
        Notification::create([
            'user_id' => $userId, 'notification_type' => $type, 'title' => $title, 'message' => $message,
            'related_entity_type' => $entityType, 'related_entity_id' => $entityId, 'action_url' => $actionUrl,
        ]);
    }

    /** Notify every active enrolled student of a cohort (schedule changes, announcements). */
    public function toCohort(int $cohortId, string $type, string $title, string $message, ?string $actionUrl = null): int
    {
        $students = Database::all("SELECT student_id FROM enrolments WHERE cohort_id = ? AND status IN ('confirmed','pending_payment')", [$cohortId]);
        foreach ($students as $s) {
            $this->toStudent((int) $s['student_id'], $type, $title, $message, 'cohort', $cohortId, $actionUrl);
        }
        return count($students);
    }

    public function unreadCount(int $userId): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$userId]);
    }
}
