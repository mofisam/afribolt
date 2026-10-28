<?php
namespace App\Controllers\Instructor;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\Announcement;
use App\Services\{AuditService, NotificationService};

/** Instructors can message the students of cohorts they teach. Posts go live immediately and notify the cohort. */
class AnnouncementController extends Controller
{
    private function myCohorts(): array
    {
        $o = [];
        foreach (Database::all("SELECT h.id, h.cohort_name FROM cohort_instructors ci JOIN cohorts h ON h.id = ci.cohort_id
                                WHERE ci.instructor_id = ? AND h.deleted_at IS NULL AND h.status NOT IN ('draft','cancelled','completed') ORDER BY h.start_at DESC", [(int) Auth::id('instructor')]) as $c) {
            $o[$c['id']] = $c['cohort_name'];
        }
        return $o;
    }

    public function index(): void
    {
        $mine = Database::all('SELECT a.*, h.cohort_name FROM announcements a JOIN cohorts h ON h.id = a.cohort_id WHERE a.created_by_instructor_id = ? ORDER BY a.created_at DESC LIMIT 50', [(int) Auth::id('instructor')]);
        $this->view('instructor/announcements', ['title' => 'Announcements', 'cohorts' => $this->myCohorts(), 'items' => $mine], 'instructor');
    }

    public function store(): void
    {
        $d = $this->validate(['cohort_id' => 'required|integer', 'title' => 'required|max:200', 'body' => 'required|min:5|max:5000'], '/instructor/announcements');
        $cohorts = $this->myCohorts();
        if (!isset($cohorts[(int) $d['cohort_id']])) { $this->abort(403); }

        $id = Announcement::create([
            'cohort_id' => (int) $d['cohort_id'], 'title' => $d['title'], 'body' => $d['body'], 'visibility' => 'enrolled_students',
            'status' => 'published', 'publish_at' => utc_now(), 'created_by_instructor_id' => (int) Auth::id('instructor'),
        ]);
        AuditService::log('announcement.created_by_instructor', 'announcement', $id, null, ['cohort_id' => (int) $d['cohort_id'], 'title' => $d['title']]);
        $n = (new NotificationService())->toCohort((int) $d['cohort_id'], 'announcement', $d['title'], mb_strimwidth($d['body'], 0, 140, '...'), '/student/dashboard');
        Session::flash('success', "Posted. {$n} student(s) were notified.");
        $this->redirect('/instructor/announcements');
    }

    public function archive(string $id): void
    {
        $a = Announcement::find((int) $id) ?? $this->abort(404);
        if ((int) ($a['created_by_instructor_id'] ?? 0) !== (int) Auth::id('instructor')) { $this->abort(403); }
        Announcement::update((int) $a['id'], ['status' => 'archived']);
        AuditService::log('announcement.archived_by_instructor', 'announcement', (int) $a['id'], ['status' => $a['status']], ['status' => 'archived']);
        Session::flash('success', 'Announcement archived. Students no longer see it.');
        $this->redirect('/instructor/announcements');
    }
}
