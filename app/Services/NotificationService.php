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

    /**
     * Number shown on the notification bell: unread notifications (cohort announcements arrive as notifications)
     * plus general announcements the student has not opened yet.
     */
    public function badgeCount(int $userId): int
    {
        $n = $this->unreadCount($userId);
        try { $n += $this->unseenAnnouncements($userId); } catch (\Throwable $e) { /* migration 006 not run yet */ }
        return $n;
    }

    /** General (non-cohort) announcements published since the student last opened their notifications page. */
    public function unseenAnnouncements(int $userId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM announcements a JOIN users u ON u.id = ?
             WHERE a.status = 'published' AND a.cohort_id IS NULL AND a.visibility IN ('public','enrolled_students')
               AND (a.publish_at IS NULL OR a.publish_at <= UTC_TIMESTAMP())
               AND (a.expires_at IS NULL OR a.expires_at > UTC_TIMESTAMP())
               AND COALESCE(a.publish_at, a.created_at) > COALESCE(u.announcements_seen_at, u.created_at)",
            [$userId]
        );
    }

    public function markAnnouncementsSeen(int $userId): void
    {
        Database::execute('UPDATE users SET announcements_seen_at = UTC_TIMESTAMP() WHERE id = ?', [$userId]);
    }
}
