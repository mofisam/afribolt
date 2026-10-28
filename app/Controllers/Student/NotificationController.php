<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Database};

class NotificationController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id('student');
        $items = Database::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [$uid]);
        $this->view('student/notifications', ['title' => 'Notifications', 'items' => $items], 'student');
    }

    public function markAllRead(): void
    {
        Database::execute('UPDATE notifications SET read_at = UTC_TIMESTAMP() WHERE user_id = ? AND read_at IS NULL', [(int) Auth::id('student')]);
        $this->redirect('/student/notifications');
    }
}
