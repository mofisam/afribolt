<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller};
use App\Models\{Announcement, ClassSession, Enrolment};
use App\Services\{CountdownService, NotificationService};

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

        $this->view('student/dashboard', [
            'title'         => 'My dashboard',
            'student'       => $student,
            'enrolments'    => $enrolments,
            'announcements' => Announcement::forStudent((int) $student['id']),
            'unread'        => (new NotificationService())->unreadCount((int) $student['id']),
        ], 'student');
    }
}
