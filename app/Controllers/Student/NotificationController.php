<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Database};
use App\Models\Announcement;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id('student');
        $filter = $this->request->query('filter') === 'unread' ? 'unread' : 'all';
        $unread = (int) Database::value('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL', [$uid]);
        $items = Database::all('SELECT * FROM notifications WHERE user_id = ?' . ($filter === 'unread' ? ' AND read_at IS NULL' : '') . ' ORDER BY created_at DESC, id DESC LIMIT 50', [$uid]);

        // General announcements (not tied to a cohort) have no notification row, so list them here too.
        $announcements = []; $seenAt = null;
        try {
            $seenAt = (string) Database::value('SELECT COALESCE(announcements_seen_at, created_at) FROM users WHERE id = ?', [$uid]);
            $announcements = array_values(array_filter(Announcement::forStudent($uid, 20), fn($a) => empty($a['cohort_id'])));
            (new NotificationService())->markAnnouncementsSeen($uid); // bell clears once they have been shown
        } catch (\Throwable $e) { /* migration 006 not run yet */ }

        $this->view('student/notifications', [
            'title' => 'Notifications', 'items' => $items, 'announcements' => $announcements, 'seenAt' => $seenAt,
            'unread' => $unread, 'filter' => $filter,
        ], 'student');
    }

    public function markAllRead(): void
    {
        Database::execute('UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL', [(int) Auth::id('student')]);
        $this->redirect('/student/notifications');
    }

    private function mine(string $id): array
    {
        return Database::one('SELECT * FROM notifications WHERE id = ? AND user_id = ?', [(int) $id, (int) Auth::id('student')]) ?? $this->abort(404);
    }

    public function markRead(string $id): void
    {
        $n = $this->mine($id);
        Database::execute('UPDATE notifications SET read_at = COALESCE(read_at, UTC_TIMESTAMP()) WHERE id = ?', [(int) $n['id']]);
        $this->redirect('/student/notifications' . ($this->request->post('filter') === 'unread' ? '?filter=unread' : ''));
    }

    /** Mark as read, then go to the page the notification points at. */
    public function open(string $id): void
    {
        $n = $this->mine($id);
        Database::execute('UPDATE notifications SET read_at = COALESCE(read_at, UTC_TIMESTAMP()) WHERE id = ?', [(int) $n['id']]);
        $to = (string) ($n['action_url'] ?? '');
        $this->redirect(($to !== '' && $to[0] === '/' && !str_starts_with($to, '//')) ? $to : '/student/notifications');
    }
}
