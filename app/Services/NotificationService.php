<?php
namespace App\Services;

use App\Core\Database;
use App\Models\Notification;

class NotificationService
{
    /**
     * Create a notification for a student and (unless $email is false) email them about it:
     * "You have one announcement from Afribolt" / "You have a new notification from Afribolt".
     * Pass $email = false when the caller already sends its own email for the same event.
     */
    public function toStudent(int $userId, string $type, string $title, string $message, ?string $entityType = null, ?int $entityId = null, ?string $actionUrl = null, bool $email = true): void
    {
        Notification::create([
            'user_id' => $userId, 'notification_type' => $type, 'title' => $title, 'message' => $message,
            'related_entity_type' => $entityType, 'related_entity_id' => $entityId, 'action_url' => $actionUrl,
        ]);
        if ($email) { $this->emailAbout($userId, $type, $title, $message, $actionUrl); }
    }

    /** Active students who have not switched notification emails off (the column exists after migration 009). */
    private function recipient(int $userId): ?array
    {
        try { $u = Database::one("SELECT id, email, first_name, email_notifications FROM users WHERE id = ? AND account_status = 'active'", [$userId]); }
        catch (\Throwable $e) { $u = Database::one("SELECT id, email, first_name FROM users WHERE id = ? AND account_status = 'active'", [$userId]); }
        return ($u && (int) ($u['email_notifications'] ?? 1) === 1) ? $u : null;
    }

    private function emailAbout(int $userId, string $type, string $title, string $message, ?string $actionUrl): void
    {
        try {
            $u = $this->recipient($userId);
            if (!$u) { return; }
            $isAnn = str_starts_with($type, 'announcement');
            (new MailService())->queue($userId, $u['email'], 'notification',
                $isAnn ? 'You have one announcement from Afribolt' : 'You have a new notification from Afribolt',
                ['name' => $u['first_name'], 'title' => $title, 'message' => $message, 'isAnnouncement' => $isAnn,
                 'link' => url($isAnn ? '/student/notifications' : ($actionUrl ?: '/student/notifications'))]);
        } catch (\Throwable $e) {
            error_log('Notification email not queued: ' . $e->getMessage()); // never block the notification itself
        }
    }

    /** Email every active student about a general announcement (no cohort). Returns how many emails were queued. */
    public function emailAllStudents(string $title, string $body): int
    {
        @set_time_limit(300);
        try { $rows = Database::all("SELECT id, email, first_name FROM users WHERE account_status = 'active' AND email_notifications = 1"); }
        catch (\Throwable $e) { $rows = Database::all("SELECT id, email, first_name FROM users WHERE account_status = 'active'"); }
        $mail = new MailService(); $n = 0;
        foreach ($rows as $u) {
            try {
                $mail->queue((int) $u['id'], $u['email'], 'notification', 'You have one announcement from Afribolt',
                    ['name' => $u['first_name'], 'title' => $title, 'message' => mb_strimwidth($body, 0, 700, '...'), 'isAnnouncement' => true, 'link' => url('/student/notifications')]);
                $n++;
            } catch (\Throwable $e) { error_log('Announcement email not queued: ' . $e->getMessage()); }
        }
        return $n;
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
