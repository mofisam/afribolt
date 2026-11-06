<?php
namespace App\Controllers\Admin;

use App\Core\{Database, Session};
use App\Models\{Job, JobReport};
use App\Services\AiService;
use App\Services\Jobs\{JobAiProcessor, JobCleaner};

/** Review what the collector + AI produced, publish/hide/reject, post jobs by hand, and handle reports. */
class JobController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const STATUSES = ['pending' => 'Needs review', 'published' => 'Published', 'hidden' => 'Hidden', 'rejected' => 'Rejected', 'expired' => 'Expired'];
    private const TYPES = ['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract', 'internship' => 'Internship', 'temporary' => 'Temporary', 'volunteer' => 'Volunteer', 'unspecified' => 'Not stated'];
    private const MODES = ['onsite' => 'On-site', 'remote' => 'Remote', 'hybrid' => 'Hybrid', 'unspecified' => 'Not stated'];
    private const LEVELS = ['entry' => 'Entry level', 'mid' => 'Mid level', 'senior' => 'Senior', 'executive' => 'Executive', 'unspecified' => 'Not stated'];

    private function cats(): array { return array_column(Database::all('SELECT id, name FROM job_categories ORDER BY display_order'), 'name', 'id'); }

    private function fields(): array
    {
        $states = ['' => 'Not stated'] + array_combine(JobCleaner::STATES, JobCleaner::STATES);
        return [
            ['name' => 'title', 'label' => 'Job title', 'required' => true], ['name' => 'company', 'label' => 'Company', 'half' => true],
            ['name' => 'category_id', 'label' => 'Category', 'type' => 'select', 'options' => ['' => 'Uncategorised'] + $this->cats(), 'half' => true],
            ['name' => 'location_text', 'label' => 'Location as written', 'half' => true], ['name' => 'state', 'label' => 'State', 'type' => 'select', 'options' => $states, 'half' => true],
            ['name' => 'city', 'label' => 'City / area', 'half' => true],
            ['name' => 'work_mode', 'label' => 'Work mode', 'type' => 'select', 'options' => self::MODES, 'half' => true],
            ['name' => 'employment_type', 'label' => 'Type', 'type' => 'select', 'options' => self::TYPES, 'half' => true],
            ['name' => 'experience_level', 'label' => 'Level', 'type' => 'select', 'options' => self::LEVELS, 'half' => true],
            ['name' => 'salary_min', 'label' => 'Salary from', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'half' => true], ['name' => 'salary_max', 'label' => 'Salary to', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'half' => true],
            ['name' => 'salary_currency', 'label' => 'Currency', 'type' => 'select', 'options' => ['' => 'n/a', 'NGN' => 'NGN', 'USD' => 'USD', 'GBP' => 'GBP', 'EUR' => 'EUR'], 'half' => true],
            ['name' => 'summary', 'label' => 'Summary shown to visitors (plain text, your own words)', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'skills_text', 'label' => 'Skills (comma separated)', 'help' => 'Used for search and to suggest courses.'],
            ['name' => 'source_url', 'label' => 'Apply / original posting link', 'type' => 'url', 'required' => true],
            ['name' => 'deadline_at', 'label' => 'Application deadline (optional)', 'type' => 'datetime', 'half' => true, 'help' => 'Times are in ' . $this->defaultTz() . '.'],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES, 'half' => true],
            ['name' => 'is_featured', 'label' => 'Feature at the top of the list', 'type' => 'checkbox'],
        ];
    }

    private const RULES = ['title' => 'required|max:250', 'company' => 'max:200', 'category_id' => 'integer', 'location_text' => 'max:250', 'state' => 'max:60', 'city' => 'max:100',
        'work_mode' => 'required|in:onsite,remote,hybrid,unspecified', 'employment_type' => 'required|in:full_time,part_time,contract,internship,temporary,volunteer,unspecified',
        'experience_level' => 'required|in:entry,mid,senior,executive,unspecified', 'salary_min' => 'numeric', 'salary_max' => 'numeric', 'salary_currency' => 'in:NGN,USD,GBP,EUR',
        'summary' => 'max:600', 'skills_text' => 'max:300', 'source_url' => 'required|url|max:1000', 'deadline_at' => 'date', 'status' => 'required|in:pending,published,hidden,rejected,expired'];

    private function rowFrom(array $d, ?array $existing): array
    {
        $skills = array_slice(array_values(array_filter(array_map(fn($s) => mb_substr(trim($s), 0, 40), explode(',', (string) $d['skills_text'])))), 0, 10);
        $row = ['title' => $d['title'], 'company' => $d['company'], 'category_id' => $d['category_id'] ? (int) $d['category_id'] : null, 'location_text' => $d['location_text'],
            'state' => in_array($d['state'], JobCleaner::STATES, true) ? $d['state'] : null, 'city' => $d['city'], 'work_mode' => $d['work_mode'], 'employment_type' => $d['employment_type'],
            'experience_level' => $d['experience_level'], 'salary_min' => $d['salary_min'], 'salary_max' => $d['salary_max'], 'salary_currency' => $d['salary_currency'],
            'summary' => $d['summary'], 'skills' => json_encode($skills), 'source_url' => $d['source_url'], 'deadline_at' => $this->fromInput($d['deadline_at'], $this->defaultTz()),
            'status' => $d['status'], 'is_featured' => $this->checkbox('is_featured'), 'canonical_hash' => JobCleaner::canonical($d['title'], $d['company'], $d['state'])];
        if ($d['status'] === 'published' && empty($existing['published_at'])) { $row['published_at'] = utc_now(); }
        return $row;
    }

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = '(j.title LIKE ? OR j.company LIKE ?)'; array_push($p, "%{$q}%", "%{$q}%"); }
        if ($s = $this->request->query('status')) { $where[] = 'j.status = ?'; $p[] = $s; }
        if ($c = $this->request->query('source')) { $where[] = 'j.source_id = ?'; $p[] = (int) $c; }
        if ($c = $this->request->query('category')) { $where[] = 'j.category_id = ?'; $p[] = (int) $c; }
        if ($this->request->query('risk') === 'high') { $where[] = 'j.scam_risk >= 40'; }
        if ($a = $this->request->query('ai')) { $where[] = 'j.ai_status = ?'; $p[] = $a; }
        $sources = array_column(Database::all('SELECT id, name FROM job_sources ORDER BY name'), 'name', 'id');
        [$rows, $pager] = $this->paged('jobs j LEFT JOIN job_categories c ON c.id = j.category_id WHERE ' . implode(' AND ', $where), $p, 'j.*, c.name AS category_name', "FIELD(j.status,'pending','published','hidden','expired','rejected'), j.id DESC", 25);

        $pending = (int) Database::value("SELECT COUNT(*) FROM jobs WHERE status = 'pending'");
        $unprocessed = (int) Database::value("SELECT COUNT(*) FROM jobs WHERE status = 'pending' AND ai_status IN ('none','failed')");
        $reports = (int) Database::value("SELECT COUNT(*) FROM job_reports WHERE status = 'open'");
        $intro = '<div class="flex flex-wrap gap-3 items-center bg-white border rounded-lg p-4 text-sm"><span><strong>' . $pending . '</strong> need review &middot; <strong>' . $unprocessed . '</strong> waiting for AI &middot; <a class="underline text-bolt-dark" href="' . e(url('/admin/jobs/reports')) . '"><strong>' . $reports . '</strong> open report(s)</a></span>'
            . '<form method="post" action="' . e(url('/admin/jobs/process-ai')) . '">' . csrf_field() . '<button class="btn btn-ghost !py-1.5" type="submit">' . (AiService::enabled() ? 'Process waiting jobs with AI now' : 'Process waiting jobs (rules only, no AI key)') . '</button></form></div>';

        $this->page('admin/crud/index', [
            'title' => 'Jobs', 'createUrl' => url('/admin/jobs/create'), 'createLabel' => 'Post a job', 'rows' => $rows, 'pager' => $pager, 'intro' => $intro,
            'filters' => [['name' => 'q', 'label' => 'Title or company'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES],
                ['name' => 'source', 'label' => 'Source', 'type' => 'select', 'options' => $sources], ['name' => 'category', 'label' => 'Category', 'type' => 'select', 'options' => $this->cats()],
                ['name' => 'risk', 'label' => 'Risk', 'type' => 'select', 'options' => ['high' => 'Flagged (40+)']], ['name' => 'ai', 'label' => 'AI', 'type' => 'select', 'options' => ['none' => 'Waiting', 'done' => 'Done', 'failed' => 'Failed', 'skipped' => 'Rules only']]],
            'columns' => [
                ['label' => 'Job', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['title']) . '</strong><br><span class="text-slate-500">' . e((string) $r['company']) . ($r['state'] ? ' &middot; ' . e($r['state']) : '') . '</span>'],
                ['label' => 'Category', 'render' => fn($r) => $r['category_name'] ?? 'None'],
                ['label' => 'Source', 'render' => fn($r) => $r['source_name'] ?? 'Posted by us'],
                ['label' => 'Risk / quality', 'html' => true, 'render' => fn($r) => '<span class="badge ' . ($r['scam_risk'] >= 60 ? 'badge-closed' : ($r['scam_risk'] >= 30 ? 'badge-warn' : 'badge-open')) . '">' . (int) $r['scam_risk'] . '</span> / ' . ($r['quality_score'] ?? '-')],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'] === 'pending' ? 'pending' : $r['status'], $r['status'] === 'pending' ? 'badge-warn' : '')],
                ['label' => 'Added', 'render' => fn($r) => local_date($r['created_at'], null, 'j M')],
            ],
            'actions' => function ($r) {
                $a = [['label' => 'Review', 'url' => url('/admin/jobs/' . $r['id'] . '/review')]];
                if ($r['status'] !== 'published') { $a[] = ['label' => 'Publish', 'url' => url('/admin/jobs/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'published']]; }
                else { $a[] = ['label' => 'Hide', 'url' => url('/admin/jobs/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'hidden']]; }
                if (!in_array($r['status'], ['rejected', 'published'], true)) { $a[] = ['label' => 'Reject', 'url' => url('/admin/jobs/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => 'rejected']]; }
                return $a;
            },
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'Post a job', 'action' => url('/admin/jobs'), 'fields' => $this->fields(),
            'values' => ['status' => 'published', 'work_mode' => 'unspecified', 'employment_type' => 'full_time', 'experience_level' => 'unspecified'], 'cancelUrl' => url('/admin/jobs'),
            'intro' => 'For jobs you receive directly (for example from employers who partner with you).']);
    }

    public function store(): void
    {
        $d = $this->validate(self::RULES, '/admin/jobs/create');
        $row = $this->rowFrom($d, null) + ['slug' => Job::uniqueSlug($d['title'], $d['company']), 'source_name' => 'Posted by AfriBolt', 'ai_status' => 'skipped', 'posted_at' => utc_now(),
            'last_seen_at' => utc_now(), 'scam_risk' => 0, 'created_by_admin_id' => $this->adminId()];
        $id = Job::create($row);
        $this->audit('job.created', 'job', $id, null, ['title' => $row['title'], 'status' => $row['status']]);
        $this->done('Job saved.', '/admin/jobs');
    }

    public function review(string $id): void
    {
        $j = Job::find((int) $id) ?? $this->abort(404);
        $flags = (array) json_decode((string) $j['scam_flags'], true);
        $j['skills_text'] = implode(', ', (array) json_decode((string) $j['skills'], true));
        $j['deadline_at'] = $this->toInput($j['deadline_at'], $this->defaultTz());
        $intro = '<div class="space-y-2 text-sm"><p>Source: <strong>' . e($j['source_name'] ?? 'Posted by us') . '</strong> &middot; AI: <strong>' . e($j['ai_status']) . '</strong>' . ($j['ai_model'] ? ' (' . e($j['ai_model']) . ')' : '')
            . ' &middot; Risk <strong>' . (int) $j['scam_risk'] . '</strong> &middot; Quality <strong>' . ($j['quality_score'] ?? '-') . '</strong> &middot; ' . (int) $j['views'] . ' views, ' . (int) $j['apply_clicks'] . ' apply clicks</p>'
            . ($flags ? '<p class="bg-orange-50 border-l-4 border-spark p-2 rounded"><strong>Flags:</strong> ' . e(implode('; ', $flags)) . '</p>' : '')
            . '<p><a class="underline text-bolt-dark" href="' . e($j['source_url']) . '" target="_blank" rel="noopener nofollow">Open the original posting</a></p>'
            . '<details><summary class="cursor-pointer underline text-bolt-dark">Collected text (visible only to admins)</summary><pre class="mt-2 bg-slate-100 p-3 rounded whitespace-pre-wrap text-xs max-h-72 overflow-auto">' . e((string) $j['description']) . '</pre></details></div>';
        $this->page('admin/crud/form', ['title' => 'Review job', 'action' => url('/admin/jobs/' . $j['id']), 'fields' => $this->fields(), 'values' => $j, 'cancelUrl' => url('/admin/jobs'), 'intro' => $intro]);
    }

    public function update(string $id): void
    {
        $j = Job::find((int) $id) ?? $this->abort(404);
        $d = $this->validate(self::RULES, '/admin/jobs/' . $j['id'] . '/review');
        $row = $this->rowFrom($d, $j);
        Job::update((int) $j['id'], $row);
        $this->audit('job.updated', 'job', (int) $j['id'], $j, array_diff_key($row, ['skills' => 1]));
        $this->done('Job updated.', '/admin/jobs');
    }

    public function status(string $id): void
    {
        $j = Job::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!isset(self::STATUSES[$new])) { $this->abort(422); }
        $row = ['status' => $new];
        if ($new === 'published' && empty($j['published_at'])) { $row['published_at'] = utc_now(); }
        Job::update((int) $j['id'], $row);
        $this->audit('job.status', 'job', (int) $j['id'], ['status' => $j['status']], ['status' => $new]);
        $this->done('Job ' . $new . '.', '/admin/jobs');
    }

    public function processAi(): void
    {
        @set_time_limit(240);
        $r = (new JobAiProcessor())->process(30);
        $this->audit('jobs.process_ai', 'job', null, null, $r);
        $this->done(sprintf('%d processed: %d published, %d held for review, %d rejected, %d will be retried.%s', $r['processed'], $r['published'], $r['held'], $r['rejected'], $r['failed'], $r['ai'] ? '' : ' (No AI key: rules only.)'), '/admin/jobs');
    }

    // ---- reports from visitors ----
    public function reports(): void
    {
        [$rows, $pager] = $this->paged('job_reports r JOIN jobs j ON j.id = r.job_id LEFT JOIN users u ON u.id = r.user_id WHERE r.status = \'open\'', [],
            "r.*, j.title, j.company, j.status AS job_status, CONCAT(u.first_name, ' ', u.last_name) AS reporter", 'r.created_at DESC');
        $this->page('admin/crud/index', [
            'title' => 'Job reports', 'rows' => $rows, 'pager' => $pager, 'intro' => '<a class="underline text-bolt-dark" href="' . e(url('/admin/jobs')) . '">&larr; All jobs</a>',
            'columns' => [['label' => 'Job', 'html' => true, 'render' => fn($r) => '<a class="underline text-bolt-dark" href="' . e(url('/admin/jobs/' . $r['job_id'] . '/review')) . '">' . e($r['title']) . '</a><br><span class="text-slate-500">' . e((string) $r['company']) . '</span>'],
                ['label' => 'Reason', 'render' => fn($r) => $r['reason']], ['label' => 'Details', 'render' => fn($r) => (string) $r['details']],
                ['label' => 'Reported by', 'render' => fn($r) => $r['reporter'] ?? 'Unknown'], ['label' => 'When', 'render' => fn($r) => local_date($r['created_at'], null, 'j M, g:i A')]],
            'actions' => fn($r) => [['label' => 'Hide job', 'url' => url('/admin/job-reports/' . $r['id'] . '/resolve'), 'post' => true, 'fields' => ['action' => 'hide'], 'confirm' => 'Hide this job from the site?'],
                ['label' => 'Dismiss', 'url' => url('/admin/job-reports/' . $r['id'] . '/resolve'), 'post' => true, 'fields' => ['action' => 'dismiss']]],
        ]);
    }

    public function resolveReport(string $id): void
    {
        $r = JobReport::find((int) $id) ?? $this->abort(404);
        if ($this->request->post('action') === 'hide') { Job::update((int) $r['job_id'], ['status' => 'hidden']); }
        JobReport::update((int) $r['id'], ['status' => 'resolved']);
        $this->audit('job_report.resolved', 'job_report', (int) $r['id'], null, ['action' => $this->request->post('action')]);
        $this->done('Report resolved.', '/admin/jobs/reports');
    }
}
