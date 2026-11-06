<?php
namespace App\Services\Jobs;

use App\Core\Database;
use App\Models\{Job, PlatformSetting};
use App\Services\AiService;

/** Take freshly collected ("pending", ai_status none) jobs, clean/categorise/score them with Claude, then publish or hold for review. */
class JobAiProcessor
{
    /** @return array{processed:int, published:int, held:int, rejected:int, failed:int, ai:bool} */
    public function process(int $limit = 0): array
    {
        $limit = $limit ?: (int) config('jobs.ai_max_per_run', 40);
        $max = (int) config('jobs.ai_max_attempts', 3);
        $jobs = Database::all("SELECT * FROM jobs WHERE status = 'pending' AND ai_status IN ('none','failed') AND ai_attempts < ? ORDER BY id LIMIT ?", [$max, $limit]);
        $out = ['processed' => 0, 'published' => 0, 'held' => 0, 'rejected' => 0, 'failed' => 0, 'ai' => AiService::enabled()];
        $cats = JobClassifier::slugs();

        foreach (array_chunk($jobs, (int) config('jobs.ai_batch', 8)) as $chunk) {
            $byIdx = [];
            foreach ($chunk as $j) { $byIdx[(int) $j['id']] = $j; }
            $results = [];
            if ($out['ai']) {
                try { $results = JobClassifier::ai($byIdx); }
                catch (\Throwable $e) { error_log('Job AI batch failed: ' . $e->getMessage()); }
            }
            foreach ($byIdx as $id => $j) {
                if ($out['ai'] && !isset($results[$id])) {
                    $attempts = (int) $j['ai_attempts'] + 1;
                    if ($attempts < $max) { Job::update($id, ['ai_status' => 'failed', 'ai_attempts' => $attempts]); $out['failed']++; continue; }
                    $r = JobClassifier::rules($j); $usedAi = false; // give up on AI for this one, use rules
                } else {
                    $r = $results[$id] ?? JobClassifier::rules($j); $usedAi = $out['ai'];
                }
                $status = $this->apply($j, $r, $usedAi, $cats);
                $out['processed']++;
                if ($status === 'published') { $out['published']++; } elseif ($status === 'rejected') { $out['rejected']++; } else { $out['held']++; }
            }
        }
        return $out;
    }

    private function apply(array $j, array $r, bool $usedAi, array $cats): string
    {
        $title = ($r['title'] ?? '') !== '' ? $r['title'] : $j['title'];
        $company = $r['company'] ?? $j['company'];
        $risk = (int) $r['scam_risk'];
        [$ruleRisk, $ruleFlags] = JobClassifier::ruleScam($j['title'] . ' ' . $j['description']);
        $risk = max($risk, $ruleRisk); // rules can only raise the risk, never lower it
        $flags = array_values(array_unique(array_merge($r['scam_flags'] ?? [], $ruleFlags)));
        $deadline = $r['deadline'] ?? $j['deadline_at'];

        $status = 'pending';
        if (isset($r['is_job']) && !$r['is_job']) { $status = 'rejected'; }
        elseif ($deadline && $deadline < utc_now()) { $status = 'expired'; }
        else {
            $auto = (string) PlatformSetting::value('jobs_auto_publish', '1') === '1';
            $hold = (int) PlatformSetting::value('jobs_scam_hold_threshold', 60);
            $minQ = (int) PlatformSetting::value('jobs_min_quality', 35);
            $ok = $risk < $hold && (int) $r['quality'] >= $minQ && $company;
            if (!$usedAi) { $ok = $ok && $risk === 0 && mb_strlen((string) $j['description']) > 150; } // stricter without AI
            if ($auto && $ok) { $status = 'published'; }
        }
        Job::update((int) $j['id'], [
            'title' => $title, 'company' => $company, 'slug' => $j['status'] === 'pending' ? Job::uniqueSlug($title, $company, (int) $j['id']) : $j['slug'],
            'category_id' => $cats[$r['category']] ?? $j['category_id'], 'state' => $r['state'] ?? $j['state'], 'city' => $r['city'] ?? $j['city'],
            'work_mode' => $r['work_mode'], 'employment_type' => $r['employment_type'], 'experience_level' => $r['experience_level'],
            'salary_min' => $r['salary_min'] ?? $j['salary_min'], 'salary_max' => $r['salary_max'] ?? $j['salary_max'], 'salary_currency' => $r['salary_currency'] ?? $j['salary_currency'],
            'summary' => $r['summary'] !== '' ? $r['summary'] : $j['summary'], 'skills' => json_encode($r['skills'] ?? []), 'deadline_at' => $deadline,
            'scam_risk' => $risk, 'scam_flags' => json_encode($flags), 'quality_score' => (int) $r['quality'],
            'ai_status' => $usedAi ? 'done' : 'skipped', 'ai_model' => $usedAi ? config('jobs.model') : null, 'ai_attempts' => (int) $j['ai_attempts'] + ($usedAi ? 0 : 1),
            'status' => $status, 'published_at' => $status === 'published' ? utc_now() : $j['published_at'],
            'canonical_hash' => JobCleaner::canonical($title, $company, $r['state'] ?? $j['state']),
        ]);
        return $status;
    }

    /** Housekeeping: expire jobs whose deadline passed or that vanished from their source. */
    public function expire(): int
    {
        $days = (int) PlatformSetting::value('jobs_stale_days', 45);
        return Database::execute("UPDATE jobs SET status = 'expired' WHERE status = 'published' AND (
                (deadline_at IS NOT NULL AND deadline_at < UTC_TIMESTAMP())
                OR (source_id IS NOT NULL AND last_seen_at IS NOT NULL AND last_seen_at < UTC_TIMESTAMP() - INTERVAL ? DAY))", [$days]);
    }
}
