<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller};
use App\Models\{Announcement, ClassSession, Enrolment};
use App\Services\{CountdownService, LearningService, NotificationService};

class DashboardController extends Controller
{
    public function index(): void
    {
        $student = Auth::user('student');
        $countdown = new CountdownService();

        $enrolments = Enrolment::forStudent((int) $student['id']);
        foreach ($enrolments as &$e) {
            $e['countdown'] = $countdown->forEnrolment($e);
            $e['sessions']  = $e['status'] === 'confirmed' ? ClassSession::forCohort((int) $e['cohort_id']) : [];
        }
        unset($e);

        $learning = [];
        $svc = new LearningService();
        foreach (\App\Core\Database::all(
            "SELECT c.id, c.title, c.slug, c.thumbnail_url, ca.last_accessed_at FROM course_access ca JOIN courses c ON c.id = ca.course_id
             WHERE ca.user_id = ? AND ca.status = 'active' AND ca.completed_at IS NULL AND c.lessons_count > 0 ORDER BY COALESCE(ca.last_accessed_at, ca.enrolled_at) DESC LIMIT 2", [(int) $student['id']]) as $c) {
            $c['progress'] = $svc->summary((int) $student['id'], (int) $c['id']);
            $learning[] = $c;
        }

        $this->view('student/dashboard', [
            'title'         => 'My dashboard',
            'learning'      => $learning,
            'student'       => $student,
            'enrolments'    => $enrolments,
            'announcements' => Announcement::forStudent((int) $student['id']),
            'unread'        => (new NotificationService())->unreadCount((int) $student['id']),
        ], 'student');
    }
}
