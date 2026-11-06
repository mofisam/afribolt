<?php
namespace App\Services\Jobs;

use App\Core\Database;
use App\Models\{Job, JobRun, JobSource, PlatformSetting};

/** Collect raw postings from a source, clean them with rules, skip duplicates, and store them as "pending" for AI. */
class JobIngestService
{
    public function dueSources(): array
    {
        return Database::all('SELECT * FROM job_sources WHERE is_enabled = 1 AND (last_run_at IS NULL OR last_run_at <= UTC_TIMESTAMP() - INTERVAL fetch_interval_minutes MINUTE) ORDER BY last_run_at');
    }

    /** @return array{status:string, fetched:int, inserted:int, duplicates:int, skipped:int, message:string} */
    public function run(array $source): array
    {
        $runId = JobRun::create(['source_id' => (int) $source['id'], 'started_at' => utc_now()]);
        $st = ['fetched' => 0, 'inserted' => 0, 'duplicates' => 0, 'skipped' => 0];
        $status = 'ok'; $msg = '';
        try {
            $raws = Sources::fetch($source);
            $st['fetched'] = count($raws);
            $errors = 0;
            foreach ($raws as $raw) {
                try { $st[$this->store($source, $raw)]++; } catch (\Throwable $e) { $errors++; $st['skipped']++; error_log('Job store failed: ' . $e->getMessage()); }
            }
            if ($errors) { $status = 'partial'; $msg = "{$errors} posting(s) could not be saved."; }
            if ($st['fetched'] === 0) { $msg = 'The source returned no postings.'; }
        } catch (\Throwable $e) {
            $status = 'failed'; $msg = mb_substr($e->getMessage(), 0, 450);
        }
        JobRun::update($runId, ['finished_at' => utc_now(), 'status' => $status, 'message' => $msg ?: null] + $st);
        JobSource::update((int) $source['id'], ['last_run_at' => utc_now(), 'last_status' => $status, 'last_message' => $msg ?: sprintf('%d new, %d duplicates', $st['inserted'], $st['duplicates']),
            'jobs_total' => (int) Database::value('SELECT COUNT(*) FROM jobs WHERE source_id = ?', [(int) $source['id']])]);
        return ['status' => $status, 'message' => $msg] + $st;
    }

    /** @return string inserted|duplicates|skipped */
    private function store(array $s, array $r): string
    {
        $title = JobCleaner::line($r['title'] ?? '', 250);
        $url = trim((string) ($r['url'] ?? ''));
        if ($title === '' || !preg_match('#^https?://#i', $url) || mb_strlen($url) > 1000) { return 'skipped'; }
        $ext = (string) ($r['external_id'] ?: $url);
        $ext = strlen($ext) > 190 ? sha1($ext) : $ext;

        $seen = Database::one('SELECT id FROM jobs WHERE source_id = ? AND external_id = ?', [(int) $s['id'], $ext]);
        if ($seen) { Database::execute('UPDATE jobs SET last_seen_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $seen['id']]); return 'skipped'; }

        $staleDays = (int) PlatformSetting::value('jobs_stale_days', 45);
        $posted = JobCleaner::date(is_string($r['posted_at'] ?? null) ? $r['posted_at'] : null);
        $deadline = JobCleaner::date(is_string($r['deadline'] ?? null) ? $r['deadline'] : null);
        if (($posted && $posted < gmdate('Y-m-d H:i:s', time() - $staleDays * 86400)) || ($deadline && $deadline < utc_now())) { return 'skipped'; }

        $job = ['title' => $title, 'company' => ($c = JobCleaner::line($r['company'] ?? '', 200)) !== '' ? $c : null,
            'location_text' => ($l = JobCleaner::line($r['location'] ?? '', 250)) !== '' ? $l : null, 'description' => JobCleaner::text($r['description'] ?? '', 6000),
            'salary_text' => ($t = JobCleaner::line($r['salary_text'] ?? '', 150)) !== '' ? $t : null];
        $x = JobClassifier::rules($job);
        $canonical = JobCleaner::canonical($title, $job['company'], $x['state']);
        if (Database::value("SELECT COUNT(*) FROM jobs WHERE canonical_hash = ? AND status <> 'rejected'", [$canonical])) { return 'duplicates'; }

        $cats = JobClassifier::slugs();
        Job::create([
            'source_id' => (int) $s['id'], 'external_id' => $ext, 'canonical_hash' => $canonical, 'slug' => Job::uniqueSlug($title, $job['company']),
            'title' => $title, 'company' => $job['company'], 'category_id' => $cats[$x['category']] ?? null, 'location_text' => $job['location_text'],
            'state' => $x['state'], 'city' => $x['city'], 'work_mode' => $x['work_mode'], 'employment_type' => $x['employment_type'], 'experience_level' => $x['experience_level'],
            'salary_min' => $x['salary_min'], 'salary_max' => $x['salary_max'], 'salary_currency' => $x['salary_currency'], 'salary_text' => $job['salary_text'],
            'summary' => $x['summary'], 'description' => $job['description'], 'skills' => '[]', 'source_url' => $url, 'source_name' => $s['name'],
            'posted_at' => $posted, 'deadline_at' => $deadline, 'last_seen_at' => utc_now(), 'status' => 'pending', 'ai_status' => 'none',
            'quality_score' => $x['quality'], 'scam_risk' => $x['scam_risk'], 'scam_flags' => json_encode($x['scam_flags']), 'raw_hash' => sha1($title . $job['description']),
        ]);
        return 'inserted';
    }
}
