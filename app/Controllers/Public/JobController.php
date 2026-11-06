<?php
namespace App\Controllers\Public;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\Job;
use App\Services\Jobs\{JobCleaner, JobRecommendationService};

class JobController extends Controller
{
    private const PER_PAGE = 15;

    public function index(): void
    {
        $f = [];
        foreach (['q', 'category', 'state', 'type', 'mode', 'level', 'posted', 'sort'] as $k) {
            $v = trim((string) $this->request->query($k, ''));
            if ($v !== '') { $f[$k] = mb_substr($v, 0, 100); }
        }
        $page = max(1, (int) $this->request->query('page', 1));
        $total = Job::searchCount($f);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);
        $uid = Auth::id('student');
        $this->view('public/jobs/index', [
            'title' => 'Jobs in Nigeria', 'jobs' => Job::search($f, self::PER_PAGE, ($page - 1) * self::PER_PAGE), 'f' => $f, 'total' => $total,
            'categories' => Job::categoryCounts(), 'states' => JobCleaner::STATES, 'pager' => ['page' => $page, 'pages' => $pages, 'total' => $total],
            'saved' => $uid ? array_map('intval', array_column(Database::all('SELECT job_id FROM saved_jobs WHERE user_id = ?', [$uid]), 'job_id')) : [],
        ]);
    }

    public function show(string $slug): void
    {
        $job = Job::findPublishedBySlug($slug) ?? $this->abort(404);
        Database::execute('UPDATE jobs SET views = views + 1 WHERE id = ?', [(int) $job['id']]);
        $uid = Auth::id('student');
        if (!$uid) { Session::put('intended', '/jobs/' . $slug); }
        $similar = Database::all("SELECT j.*, c.name AS category_name, c.slug AS category_slug FROM jobs j LEFT JOIN job_categories c ON c.id = j.category_id
            WHERE j.status = 'published' AND j.id <> ? AND j.category_id <=> ? AND (j.deadline_at IS NULL OR j.deadline_at > UTC_TIMESTAMP()) ORDER BY j.published_at DESC LIMIT 4", [(int) $job['id'], $job['category_id']]);
        $this->view('public/jobs/show', [
            'title' => $job['title'] . ($job['company'] ? ' at ' . $job['company'] : ''), 'job' => $job, 'similar' => $similar,
            'courses' => (new JobRecommendationService())->coursesForJob($job),
            'isSaved' => $uid ? (bool) Database::value('SELECT COUNT(*) FROM saved_jobs WHERE user_id = ? AND job_id = ?', [$uid, (int) $job['id']]) : false,
            'skills' => (array) json_decode((string) $job['skills'], true), 'flags' => (array) json_decode((string) $job['scam_flags'], true),
        ]);
    }

    /** Count the click, then send the visitor to the employer's own posting. */
    public function apply(string $slug): void
    {
        $job = Job::findPublishedBySlug($slug) ?? $this->abort(404);
        if (!preg_match('#^https?://#i', (string) $job['source_url'])) { $this->abort(404); }
        Database::execute('UPDATE jobs SET apply_clicks = apply_clicks + 1 WHERE id = ?', [(int) $job['id']]);
        header('Referrer-Policy: no-referrer');
        $this->redirect($job['source_url']);
    }
}
