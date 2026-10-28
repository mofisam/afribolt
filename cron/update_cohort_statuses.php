<?php
// */15 * * * *  php /path/to/afribolt-platform/cron/update_cohort_statuses.php
// Moves cohorts through published/open -> closed -> in_progress -> completed based on stored timestamps.
require __DIR__ . '/bootstrap.php';

use App\Core\Database as DB;

$closed = DB::execute("UPDATE cohorts SET status = 'closed' WHERE deleted_at IS NULL AND status IN ('published','open') AND registration_closes_at IS NOT NULL AND registration_closes_at < UTC_TIMESTAMP() AND start_at > UTC_TIMESTAMP()");
$started = DB::execute("UPDATE cohorts SET status = 'in_progress' WHERE deleted_at IS NULL AND status IN ('published','open','closed') AND start_at <= UTC_TIMESTAMP() AND (end_at IS NULL OR end_at > UTC_TIMESTAMP())");
$done = DB::execute("UPDATE cohorts SET status = 'completed' WHERE deleted_at IS NULL AND status = 'in_progress' AND end_at IS NOT NULL AND end_at < UTC_TIMESTAMP()");
$enrol = DB::execute("UPDATE enrolments SET status = 'completed', completed_at = UTC_TIMESTAMP() WHERE status = 'confirmed' AND cohort_id IN (SELECT id FROM cohorts WHERE status = 'completed')");
echo gmdate('c') . " closed={$closed} started={$started} completed={$done} enrolments_completed={$enrol}\n";
