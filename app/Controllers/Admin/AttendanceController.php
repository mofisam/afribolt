<?php
namespace App\Controllers\Admin;

use App\Core\Database;

class AttendanceController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    public function index(): void
    {
        $sessions = Database::all("SELECT s.id, s.title, s.starts_at, s.timezone, h.cohort_name FROM class_sessions s JOIN cohorts h ON h.id = s.cohort_id
                                   WHERE s.starts_at <= UTC_TIMESTAMP() + INTERVAL 1 DAY ORDER BY s.starts_at DESC LIMIT 200");
        $sid = (int) $this->request->query('session_id', 0);
        $session = $sid ? Database::one('SELECT s.*, h.cohort_name FROM class_sessions s JOIN cohorts h ON h.id = s.cohort_id WHERE s.id = ?', [$sid]) : null;
        $roster = $session ? Database::all(
            "SELECT e.id AS enrolment_id, e.enrolment_number, u.first_name, u.last_name, a.attendance_status, a.joined_at, a.notes
             FROM enrolments e JOIN users u ON u.id = e.student_id
             LEFT JOIN session_attendance a ON a.enrolment_id = e.id AND a.session_id = ?
             WHERE e.cohort_id = ? AND e.status IN ('confirmed','completed') ORDER BY u.first_name, u.last_name", [$sid, (int) $session['cohort_id']]) : [];
        $this->page('admin/attendance/index', ['title' => 'Attendance', 'sessions' => $sessions, 'session' => $session, 'roster' => $roster]);
    }

    public function save(): void
    {
        $sid = (int) $this->request->post('session_id');
        $session = Database::one('SELECT id, cohort_id FROM class_sessions WHERE id = ?', [$sid]) ?? $this->abort(404);
        $status = (array) ($_POST['status'] ?? []);
        $notes = (array) ($_POST['notes'] ?? []);
        $valid = Database::all("SELECT id FROM enrolments WHERE cohort_id = ? AND status IN ('confirmed','completed')", [(int) $session['cohort_id']]);
        $n = 0;
        foreach ($valid as $v) {
            $eid = (int) $v['id'];
            $st = $status[$eid] ?? '';
            if (!in_array($st, ['present', 'late', 'absent', 'excused'], true)) { continue; }
            Database::execute(
                'INSERT INTO session_attendance (session_id, enrolment_id, attendance_status, notes, marked_by_admin_id) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE attendance_status = VALUES(attendance_status), notes = VALUES(notes), marked_by_admin_id = VALUES(marked_by_admin_id)',
                [$sid, $eid, $st, mb_substr(trim((string) ($notes[$eid] ?? '')), 0, 500) ?: null, $this->adminId()]);
            $n++;
        }
        $this->audit('attendance.saved', 'class_session', $sid, null, ['students' => $n]);
        $this->done("Attendance saved for {$n} student(s).", '/admin/attendance?session_id=' . $sid);
    }
}
