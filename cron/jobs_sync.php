<?php
// */30 * * * *  php /path/to/cron/jobs_sync.php
// 1) collect from every enabled source that is due  2) clean/categorise/score new jobs with AI and publish or hold them  3) expire old jobs
require __DIR__ . '/bootstrap.php';

use App\Services\Jobs\{JobAiProcessor, JobIngestService};

@set_time_limit(0);
$ing = new JobIngestService();
foreach ($ing->dueSources() as $s) {
    $r = $ing->run($s);
    echo gmdate('c') . " {$s['name']}: {$r['status']} fetched={$r['fetched']} new={$r['inserted']} dup={$r['duplicates']} skipped={$r['skipped']} {$r['message']}\n";
}
$ai = new JobAiProcessor();
$p = $ai->process();
echo gmdate('c') . " AI: processed={$p['processed']} published={$p['published']} held={$p['held']} rejected={$p['rejected']} retry={$p['failed']} ai=" . ($p['ai'] ? 'yes' : 'no (rules only)') . "\n";
echo gmdate('c') . ' expired=' . $ai->expire() . "\n";
