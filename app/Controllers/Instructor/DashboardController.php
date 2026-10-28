<?php
namespace App\Controllers\Instructor;

use App\Core\{Auth, Controller, Database};

class DashboardController extends Controller
{
    public function index(): void
    {
        $me = Auth::user('instructor');
        $iid = (int) $me['id'];
        $cohorts = Database::all(
            "SELECT h.*, c.title AS course_title, ci.assignment_role,
                    (SELECT COUNT(*) FROM enrolments e WHERE e.cohort_id = h.id AND e.status = 'confirmed') AS students
             FROM cohort_instructors ci JOIN cohorts h ON h.id = ci.cohort_id JOIN courses c ON c.id = h.course_id
             WHERE ci.instructor_id = ? AND h.deleted_at IS NULL AND h.status NOT IN ('draft','cancelled') ORDER BY h.start_at DESC", [$iid]);
        $upcoming = Database::all(
            "SELECT s.*, h.cohort_name FROM class_sessions s JOIN cohort_instructors ci ON ci.cohort_id = s.cohort_id JOIN cohorts h ON h.id = s.cohort_id
             WHERE ci.instructor_id = ? AND s.status IN ('scheduled','live') AND s.ends_at > UTC_TIMESTAMP() ORDER BY s.starts_at LIMIT 8", [$iid]);
        // Classes in the next 7 days with no meeting link: the single most useful nudge
        $noLink = array_values(array_filter($upcoming, fn($s) => empty($s['meeting_url']) && $s['starts_at'] <= gmdate('Y-m-d H:i:s', time() + 7 * 86400)));
        $stats = [
            'cohorts'  => count(array_filter($cohorts, fn($c) => in_array($c['status'], ['published', 'open', 'closed', 'in_progress'], true))),
            'students' => array_sum(array_map(fn($c) => (int) $c['students'], $cohorts)),
            'week'     => count(array_filter($upcoming, fn($s) => $s['starts_at'] <= gmdate('Y-m-d H:i:s', time() + 7 * 86400))),
        ];
        $this->view('instructor/dashboard', ['title' => 'My classes', 'me' => $me, 'cohorts' => $cohorts, 'upcoming' => $upcoming, 'noLink' => $noLink, 'stats' => $stats], 'instructor');
    }
}
