<?php
namespace App\Controllers\Instructor;

use App\Core\{Auth, Controller, Crypto, Database, Session};
use App\Models\ClassSession;
use App\Services\{AuditService, ScheduleService};

/** Instructors only ever touch cohorts and sessions they are assigned to. */
class CohortController extends Controller
{
    private const PROVIDERS = ['none' => 'Not set', 'google_meet' => 'Google Meet', 'zoom' => 'Zoom', 'microsoft_teams' => 'Microsoft Teams', 'other' => 'Other'];

    private function assigned(int $cohortId): bool
    {
        return (bool) Database::value('SELECT COUNT(*) FROM cohort_instructors WHERE cohort_id = ? AND instructor_id = ?', [$cohortId, (int) Auth::id('instructor')]);
    }

    private function ownSession(string $id): array
    {
        $s = ClassSession::find((int) $id) ?? $this->abort(404);
        if (!$this->assigned((int) $s['cohort_id'])) { $this->abort(403); }
        return $s;
    }

    public function show(string $id): void
    {
        $cid = (int) $id;
        if (!$this->assigned($cid)) { $this->abort(403); }
        $cohort = Database::one('SELECT h.*, c.title AS course_title FROM cohorts h JOIN courses c ON c.id = h.course_id WHERE h.id = ? AND h.deleted_at IS NULL', [$cid]) ?? $this->abort(404);
        $this->view('instructor/cohort', [
            'title' => $cohort['cohort_name'], 'cohort' => $cohort,
            'sessions' => Database::all('SELECT * FROM class_sessions WHERE cohort_id = ? ORDER BY starts_at', [$cid]),
            'students' => $this->roster($cid),
        ], 'instructor');
    }

    private function roster(int $cid): array
    {
        return Database::all("SELECT u.first_name, u.last_name, u.email, e.enrolment_number,
                (SELECT COUNT(*) FROM session_attendance a WHERE a.enrolment_id = e.id AND a.attendance_status IN ('present','late')) AS attended,
                (SELECT COUNT(*) FROM class_sessions s WHERE s.cohort_id = e.cohort_id AND s.status = 'completed') AS held
                FROM enrolments e JOIN users u ON u.id = e.student_id WHERE e.cohort_id = ? AND e.status IN ('confirmed','completed') ORDER BY u.first_name, u.last_name", [$cid]);
    }

    public function rosterCsv(string $id): void
    {
        $cid = (int) $id;
        if (!$this->assigned($cid)) { $this->abort(403); }
        $cohort = Database::one('SELECT cohort_code FROM cohorts WHERE id = ?', [$cid]) ?? $this->abort(404);
        AuditService::log('roster.exported', 'cohort', $cid);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="roster_' . preg_replace('/[^A-Za-z0-9_-]/', '', $cohort['cohort_code']) . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Name', 'Email', 'Enrolment', 'Classes attended', 'Classes held']);
        foreach ($this->roster($cid) as $r) {
            $row = [$r['first_name'] . ' ' . $r['last_name'], $r['email'], $r['enrolment_number'], $r['attended'], $r['held']];
            fputcsv($out, array_map(fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], '=+-@') !== false ? "'" . $v : $v, $row));
        }
        fclose($out);
        exit;
    }

    // ---- meeting details (not dates, fees or cancellation: those stay with admins) ----
    private function sessionFields(): array
    {
        return [
            ['name' => 'description', 'label' => 'Topic / notes for students', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'meeting_provider', 'label' => 'Platform', 'type' => 'select', 'options' => self::PROVIDERS, 'half' => true],
            ['name' => 'meeting_id', 'label' => 'Meeting ID', 'half' => true],
            ['name' => 'meeting_url', 'label' => 'Meeting link', 'type' => 'url', 'help' => 'Students only receive this inside the join window.'],
            ['name' => 'meeting_passcode', 'label' => 'Passcode', 'help' => 'Leave blank to keep the current one. Stored encrypted.'],
            ['name' => 'recording_url', 'label' => 'Recording link (after class)', 'type' => 'url'],
        ];
    }

    public function editSession(string $id): void
    {
        $s = $this->ownSession($id);
        $this->view('admin/crud/form', [
            'title' => 'Meeting details: ' . $s['title'], 'action' => url('/instructor/sessions/' . $s['id']), 'fields' => $this->sessionFields(), 'values' => $s,
            'cancelUrl' => url('/instructor/cohorts/' . $s['cohort_id']),
            'intro' => 'Class time: ' . e(local_date($s['starts_at'], $s['timezone'])) . '. To change the time or cancel the class, ask an admin.',
        ], 'instructor');
    }

    public function updateSession(string $id): void
    {
        $s = $this->ownSession($id);
        $d = $this->validate(['description' => 'max:2000', 'meeting_provider' => 'required|in:' . implode(',', array_keys(self::PROVIDERS)),
            'meeting_id' => 'max:191', 'meeting_url' => 'url', 'meeting_passcode' => 'max:100', 'recording_url' => 'url'], '/instructor/sessions/' . $s['id'] . '/edit');
        $row = ['description' => $d['description'], 'meeting_provider' => $d['meeting_provider'], 'meeting_id' => $d['meeting_id'],
            'meeting_url' => $d['meeting_url'], 'recording_url' => $d['recording_url']];
        if ($d['meeting_passcode']) {
            try { $row['meeting_passcode_encrypted'] = Crypto::encrypt($d['meeting_passcode']); }
            catch (\RuntimeException $e) { Session::flash('error', $e->getMessage()); $this->redirect('/instructor/sessions/' . $s['id'] . '/edit'); }
        }
        ClassSession::update((int) $s['id'], $row);
        AuditService::log('session.meeting_updated_by_instructor', 'class_session', (int) $s['id'],
            ['meeting_provider' => $s['meeting_provider'], 'has_link' => (bool) $s['meeting_url']],
            ['meeting_provider' => $row['meeting_provider'], 'has_link' => (bool) $row['meeting_url'], 'link_changed' => $row['meeting_url'] !== $s['meeting_url'], 'passcode_changed' => !empty($d['meeting_passcode'])]);
        Session::flash('success', 'Meeting details saved.');
        $this->redirect('/instructor/cohorts/' . $s['cohort_id']);
    }

    /** Start / finish a class. */
    public function sessionStatus(string $id): void
    {
        $s = $this->ownSession($id);
        $new = (string) $this->request->post('status');
        if (!in_array($new, ['live', 'completed'], true) || !in_array($s['status'], ['scheduled', 'live'], true)) {
            Session::flash('error', 'That status change is not allowed. Ask an admin to postpone or cancel a class.');
            $this->redirect('/instructor/cohorts/' . $s['cohort_id']);
        }
        (new ScheduleService())->setStatus((int) $s['id'], $new);
        Session::flash('success', $new === 'live' ? 'Class marked as live.' : 'Class marked as completed. Don\'t forget attendance.');
        $this->redirect('/instructor/cohorts/' . $s['cohort_id']);
    }

    // ---- attendance (unchanged) ----
    public function attendance(string $id): void
    {
        $s = Database::one('SELECT s.*, h.cohort_name FROM class_sessions s JOIN cohorts h ON h.id = s.cohort_id WHERE s.id = ?', [(int) $id]) ?? $this->abort(404);
        if (!$this->assigned((int) $s['cohort_id'])) { $this->abort(403); }
        $roster = Database::all(
            "SELECT e.id AS enrolment_id, u.first_name, u.last_name, a.attendance_status, a.joined_at, a.notes
             FROM enrolments e JOIN users u ON u.id = e.student_id LEFT JOIN session_attendance a ON a.enrolment_id = e.id AND a.session_id = ?
             WHERE e.cohort_id = ? AND e.status IN ('confirmed','completed') ORDER BY u.first_name", [(int) $s['id'], (int) $s['cohort_id']]);
        $this->view('instructor/attendance', ['title' => 'Attendance', 'session' => $s, 'roster' => $roster], 'instructor');
    }

    public function saveAttendance(string $id): void
    {
        $s = Database::one('SELECT id, cohort_id FROM class_sessions WHERE id = ?', [(int) $id]) ?? $this->abort(404);
        if (!$this->assigned((int) $s['cohort_id'])) { $this->abort(403); }
        $status = (array) ($_POST['status'] ?? []); $notes = (array) ($_POST['notes'] ?? []);
        $n = 0;
        foreach (Database::all("SELECT id FROM enrolments WHERE cohort_id = ? AND status IN ('confirmed','completed')", [(int) $s['cohort_id']]) as $v) {
            $eid = (int) $v['id']; $st = $status[$eid] ?? '';
            if (!in_array($st, ['present', 'late', 'absent', 'excused'], true)) { continue; }
            Database::execute('INSERT INTO session_attendance (session_id, enrolment_id, attendance_status, notes, marked_by_instructor_id) VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE attendance_status = VALUES(attendance_status), notes = VALUES(notes), marked_by_instructor_id = VALUES(marked_by_instructor_id)',
                [(int) $s['id'], $eid, $st, mb_substr(trim((string) ($notes[$eid] ?? '')), 0, 500) ?: null, (int) Auth::id('instructor')]);
            $n++;
        }
        Session::flash('success', "Attendance saved for {$n} student(s).");
        $this->redirect('/instructor/cohorts/' . $s['cohort_id']);
    }
}
