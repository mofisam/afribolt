<?php
namespace App\Controllers\Admin;

use App\Core\{Database, Session};
use App\Models\JobSource;
use App\Services\AiService;
use App\Services\Jobs\{JobAiProcessor, JobIngestService};

/** Where jobs come from. Only add sources whose terms allow automated access (APIs, feeds, or pages that permit it). */
class JobSourceController extends BaseAdminController
{
    protected array $roles = ['admin'];

    private const TYPES = ['rss' => 'RSS / Atom feed', 'jooble' => 'Jooble API', 'json_api' => 'Generic JSON API', 'jsonld' => 'Pages with JobPosting data (JSON-LD)'];

    private function fields(): array
    {
        return [
            ['name' => 'name', 'label' => 'Name shown on jobs ("via ...")', 'required' => true, 'half' => true],
            ['name' => 'source_type', 'label' => 'Type', 'type' => 'select', 'options' => self::TYPES, 'half' => true],
            ['name' => 'endpoint_url', 'label' => 'Feed / API / page URL', 'type' => 'url', 'help' => 'RSS: the feed URL. JSON API: the endpoint. JSON-LD: a job page or listing page. Jooble: leave blank.'],
            ['name' => 'config', 'label' => 'Settings (JSON)', 'type' => 'textarea', 'rows' => 7, 'help' => 'See the examples at the top of this page. Keep API keys in .env and refer to them as {{env:NAME}}.'],
            ['name' => 'fetch_interval_minutes', 'label' => 'Check every (minutes)', 'type' => 'number', 'min' => 30, 'default' => 180, 'half' => true],
            ['name' => 'is_enabled', 'label' => 'Enabled (collect jobs automatically)', 'type' => 'checkbox', 'half' => true],
            ['name' => 'permission_note', 'label' => 'Permission / terms note', 'type' => 'textarea', 'rows' => 3,
                'help' => 'Record why you may collect from this source (API terms, written permission, public feed). Required to enable a JSON-LD page source.'],
        ];
    }

    private const EXAMPLES = <<<'TXT'
RSS / Atom feed:      {"respect_robots": true}
Jooble API:           {"keywords": ["developer", "accountant", "customer service"], "locations": ["Nigeria", "Lagos", "Abuja"], "pages": 2}   (needs JOOBLE_API_KEY; the system probes which endpoint/location works)
JSearch (RapidAPI), covers Nigeria. URL: https://jsearch.p.rapidapi.com/search?query=jobs%20in%20Nigeria&country=ng&num_pages=2&date_posted=month
                      {"items_path": "data", "headers": {"X-RapidAPI-Key": "{{env:RAPIDAPI_KEY}}", "X-RapidAPI-Host": "jsearch.p.rapidapi.com"},
                       "map": {"id": "job_id", "title": "job_title", "company": "employer_name", "location": ["job_city", "job_state", "job_country"], "url": "job_apply_link", "description": "job_description", "posted_at": "job_posted_at_datetime_utc"}}
                      (field names come from the provider documentation; use the Test button to confirm)
Generic JSON API:     {"items_path": "data.jobs", "headers": {"Authorization": "Bearer {{env:MY_SOURCE_KEY}}"},
                       "map": {"id": "id", "title": "title", "company": "company.name", "location": "location", "url": "apply_url", "description": "description", "posted_at": "date"}}
JSON-LD pages:        {"urls": ["https://example.com/jobs/123"]}   or a listing page:   {"link_pattern": "/jobs/[0-9]+", "max_detail_pages": 20}
TXT;

    private function rules(): array
    {
        return ['name' => 'required|max:150', 'source_type' => 'required|in:' . implode(',', array_keys(self::TYPES)), 'endpoint_url' => 'url|max:500',
            'config' => 'max:5000', 'fetch_interval_minutes' => 'required|integer', 'permission_note' => 'max:2000'];
    }

    /** @return array validated row (redirects back on bad input) */
    private function row(array $d, string $back): array
    {
        $err = [];
        $cfg = trim((string) $d['config']);
        if ($cfg !== '' && json_decode($cfg, true) === null) { $err['config'] = 'This is not valid JSON.'; }
        if (in_array($d['source_type'], ['rss', 'json_api', 'jsonld'], true) && empty($d['endpoint_url']) && !str_contains($cfg, '"urls"')) { $err['endpoint_url'] = 'A URL is required for this source type.'; }
        $enabled = $this->checkbox('is_enabled');
        if ($enabled && $d['source_type'] === 'jsonld' && trim((string) $d['permission_note']) === '') { $err['permission_note'] = 'Record the permission or terms that allow collecting from this site before enabling it.'; }
        if ((int) $d['fetch_interval_minutes'] < 30) { $err['fetch_interval_minutes'] = 'At least 30 minutes, to be polite to the source.'; }
        if ($err) { Session::flashInput($this->request->all(), $err); Session::flash('error', 'Please fix the highlighted fields.'); $this->redirect($back); }
        return ['name' => $d['name'], 'source_type' => $d['source_type'], 'endpoint_url' => $d['endpoint_url'], 'config' => $cfg !== '' ? $cfg : null,
            'fetch_interval_minutes' => (int) $d['fetch_interval_minutes'], 'is_enabled' => $enabled, 'permission_note' => $d['permission_note']];
    }

    public function index(): void
    {
        $rows = Database::all('SELECT * FROM job_sources ORDER BY is_enabled DESC, name');
        $ai = AiService::enabled();
        $intro = '<div class="bg-white border rounded-lg p-4 text-sm space-y-2">'
            . '<p><strong>AI:</strong> ' . ($ai ? 'Claude is connected (' . e(config('jobs.model')) . '). Jobs are cleaned, categorised and risk-scored automatically.' : '<span class="text-red-700 font-bold">No ANTHROPIC_API_KEY set.</span> Rule-based cleaning is used instead and fewer jobs auto-publish.') . '</p>'
            . '<p><strong>Heads up:</strong> only collect from sources that allow it (official APIs, public feeds, or sites that give permission). We store a short AI-written summary and always link to the original posting.</p>'
            . '<details><summary class="cursor-pointer underline text-bolt-dark">Settings examples</summary><pre class="mt-2 bg-slate-100 p-3 rounded text-xs whitespace-pre-wrap">' . e(self::EXAMPLES) . '</pre></details>'
            . '<p>Cron: <code>*/30 * * * * php cron/jobs_sync.php</code> collects due sources and processes new jobs.</p></div>';
        $this->page('admin/crud/index', [
            'title' => 'Job sources', 'createUrl' => url('/admin/job-sources/create'), 'createLabel' => 'Add source', 'rows' => $rows, 'intro' => $intro,
            'columns' => [
                ['label' => 'Source', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['name']) . '</strong><br><span class="text-slate-500">' . e(self::TYPES[$r['source_type']]) . '</span>'],
                ['label' => 'Enabled', 'html' => true, 'render' => fn($r) => $this->badge($r['is_enabled'] ? 'active' : 'closed')],
                ['label' => 'Every', 'render' => fn($r) => $r['fetch_interval_minutes'] . ' min'],
                ['label' => 'Last run', 'render' => fn($r) => $r['last_run_at'] ? local_date($r['last_run_at'], null, 'j M, g:i A') : 'Never'],
                ['label' => 'Result', 'html' => true, 'render' => fn($r) => ($r['last_status'] ? $this->badge($r['last_status'] === 'ok' ? 'active' : ($r['last_status'] === 'partial' ? 'pending' : 'closed')) : '') . ' <span class="text-slate-600">' . e((string) $r['last_message']) . '</span>'],
                ['label' => 'Jobs', 'render' => fn($r) => $r['jobs_total']],
            ],
            'actions' => fn($r) => [
                ['label' => 'Test', 'url' => url('/admin/job-sources/' . $r['id'] . '/test'), 'post' => true],
                ['label' => 'Run now', 'url' => url('/admin/job-sources/' . $r['id'] . '/run'), 'post' => true],
                ['label' => $r['is_enabled'] ? 'Disable' : 'Enable', 'url' => url('/admin/job-sources/' . $r['id'] . '/toggle'), 'post' => true],
                ['label' => 'Edit', 'url' => url('/admin/job-sources/' . $r['id'] . '/edit')],
            ],
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'Add job source', 'action' => url('/admin/job-sources'), 'fields' => $this->fields(), 'values' => ['config' => "{\n}"], 'cancelUrl' => url('/admin/job-sources')]);
    }

    public function store(): void
    {
        $row = $this->row($this->validate($this->rules(), '/admin/job-sources/create'), '/admin/job-sources/create');
        $id = JobSource::create($row);
        $this->audit('job_source.created', 'job_source', $id, null, ['name' => $row['name'], 'type' => $row['source_type']]);
        $this->done('Source added. Use "Run now" to test it.', '/admin/job-sources');
    }

    public function edit(string $id): void
    {
        $s = JobSource::find((int) $id) ?? $this->abort(404);
        $this->page('admin/crud/form', ['title' => 'Edit job source', 'action' => url('/admin/job-sources/' . $s['id']), 'fields' => $this->fields(), 'values' => $s, 'cancelUrl' => url('/admin/job-sources')]);
    }

    public function update(string $id): void
    {
        $s = JobSource::find((int) $id) ?? $this->abort(404);
        $row = $this->row($this->validate($this->rules(), '/admin/job-sources/' . $s['id'] . '/edit'), '/admin/job-sources/' . $s['id'] . '/edit');
        JobSource::update((int) $s['id'], $row);
        $this->audit('job_source.updated', 'job_source', (int) $s['id'], $s, array_diff_key($row, ['config' => 1]));
        $this->done('Source updated.', '/admin/job-sources');
    }

    public function toggle(string $id): void
    {
        $s = JobSource::find((int) $id) ?? $this->abort(404);
        $new = $s['is_enabled'] ? 0 : 1;
        if ($new && $s['source_type'] === 'jsonld' && trim((string) $s['permission_note']) === '') {
            $this->done('Record the permission/terms note before enabling a page source.', '/admin/job-sources/' . $s['id'] . '/edit', 'error');
        }
        JobSource::update((int) $s['id'], ['is_enabled' => $new]);
        $this->audit('job_source.toggled', 'job_source', (int) $s['id'], ['is_enabled' => $s['is_enabled']], ['is_enabled' => $new]);
        $this->done('Source ' . ($new ? 'enabled' : 'disabled') . '.', '/admin/job-sources');
    }

    /** Show what this source really returns, without saving anything. */
    public function test(string $id): void
    {
        $s = JobSource::find((int) $id) ?? $this->abort(404);
        @set_time_limit(120);
        $msg = \App\Services\Jobs\Sources::diagnose($s);
        $bad = str_starts_with($msg, 'FAILED') || str_starts_with($msg, 'NO JOBS') || str_starts_with($msg, 'JOOBLE_API_KEY');
        $this->done($s['name'] . ': ' . $msg, '/admin/job-sources', $bad ? 'error' : 'success');
    }

    /** Collect now (and process up to 20 new jobs). Slow sources are better left to cron. */
    public function run(string $id): void
    {
        $s = JobSource::find((int) $id) ?? $this->abort(404);
        @set_time_limit(240);
        $r = (new JobIngestService())->run($s);
        $ai = (new JobAiProcessor())->process(20);
        $this->audit('job_source.run', 'job_source', (int) $s['id'], null, ['status' => $r['status'], 'inserted' => $r['inserted']]);
        $msg = sprintf('%s: %d found, %d new, %d duplicates. AI processed %d (%d published, %d held for review).%s', $r['status'] === 'failed' ? 'Failed' : 'Done',
            $r['fetched'], $r['inserted'], $r['duplicates'], $ai['processed'], $ai['published'], $ai['held'], $r['message'] ? ' ' . $r['message'] : '');
        $this->done($msg, '/admin/job-sources', $r['status'] === 'failed' ? 'error' : 'success');
    }
}
