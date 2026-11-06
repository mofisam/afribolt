<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\{Job, JobReport, JobCategory};
use App\Services\Jobs\{JobCleaner, JobRecommendationService};

class JobController extends Controller
{
    public function index(): void
    {
        $uid = (int) Auth::id('student');
        $svc = new JobRecommendationService();
        $tab = $this->request->query('tab') === 'saved' ? 'saved' : 'foryou';
        $saved = Database::all("SELECT j.*, c.name AS category_name, c.slug AS category_slug FROM saved_jobs s JOIN jobs j ON j.id = s.job_id LEFT JOIN job_categories c ON c.id = j.category_id
            WHERE s.user_id = ? ORDER BY s.created_at DESC", [$uid]);
        $this->view('student/jobs', [
            'title' => 'Jobs for you', 'tab' => $tab, 'saved' => $saved, 'savedIds' => array_map('intval', array_column($saved, 'id')),
            'recs' => $tab === 'foryou' ? $svc->forUser($uid, 12, true) : [], 'profile' => $svc->profile($uid),
        ], 'student');
    }

    public function preferences(): void
    {
        $uid = (int) Auth::id('student');
        $this->view('student/job-preferences', [
            'title' => 'Job preferences', 'p' => (new JobRecommendationService())->prefs($uid),
            'categories' => array_column(Database::all('SELECT slug, name FROM job_categories ORDER BY display_order'), 'name', 'slug'), 'states' => JobCleaner::STATES,
        ], 'student');
    }

    public function savePreferences(): void
    {
        $in = fn(string $k, array $ok) => array_values(array_intersect((array) ($_POST[$k] ?? []), $ok));
        $cats = array_column(Database::all('SELECT slug FROM job_categories'), 'slug');
        (new JobRecommendationService())->savePrefs((int) Auth::id('student'), [
            'categories' => $in('categories', $cats), 'states' => $in('states', JobCleaner::STATES), 'work_modes' => $in('work_modes', ['onsite', 'remote', 'hybrid']),
            'levels' => $in('levels', ['entry', 'mid', 'senior', 'executive']), 'skills' => trim((string) $this->request->post('skills', '')),
            'alerts_enabled' => $this->request->post('alerts_enabled') === '1' ? 1 : 0,
        ]);
        Session::flash('success', 'Saved. Your recommendations will use these.');
        $this->redirect('/student/jobs');
    }

    public function toggleSave(string $id): void
    {
        $uid = (int) Auth::id('student');
        $job = Database::one("SELECT id, slug FROM jobs WHERE id = ? AND status = 'published'", [(int) $id]) ?? $this->abort(404);
        if (Database::value('SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?', [$uid, (int) $job['id']])) {
            Database::execute('DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?', [$uid, (int) $job['id']]); $saved = false;
        } else {
            Database::execute('INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (?, ?)', [$uid, (int) $job['id']]); $saved = true;
        }
        if ($this->request->isAjax()) { $this->json(['ok' => true, 'saved' => $saved]); }
        Session::flash('success', $saved ? 'Job saved.' : 'Removed from saved jobs.');
        $back = (string) $this->request->post('back', '/jobs/' . $job['slug']);
        $this->redirect(str_starts_with($back, '/') && !str_starts_with($back, '//') ? $back : '/jobs/' . $job['slug']);
    }

    public function report(string $id): void
    {
        $uid = (int) Auth::id('student');
        $job = Database::one('SELECT id, slug FROM jobs WHERE id = ?', [(int) $id]) ?? $this->abort(404);
        $reason = in_array($this->request->post('reason'), ['scam', 'expired', 'duplicate', 'inappropriate', 'other'], true) ? $this->request->post('reason') : 'scam';
        if (!Database::value("SELECT COUNT(*) FROM job_reports WHERE job_id = ? AND user_id = ? AND status = 'open'", [(int) $job['id'], $uid])) {
            JobReport::create(['job_id' => (int) $job['id'], 'user_id' => $uid, 'reason' => $reason, 'details' => mb_substr(trim((string) $this->request->post('details', '')), 0, 500) ?: null]);
        }
        Session::flash('success', 'Thanks. We will review this job.');
        $this->redirect('/jobs/' . $job['slug']);
    }
}
