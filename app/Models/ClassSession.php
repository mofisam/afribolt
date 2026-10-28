<?php
namespace App\Models;

use App\Core\{Database, Model};

class ClassSession extends Model
{
    protected static string $table = 'class_sessions';

    /** Public-safe columns only: meeting_url / passcode are deliberately NOT selected here. */
    private const SAFE = 'id, cohort_id, title, description, session_number, starts_at, ends_at, timezone, meeting_provider, join_early_minutes, status';

    public static function forCohort(int $cohortId): array
    {
        return Database::all('SELECT ' . self::SAFE . ' FROM class_sessions WHERE cohort_id = ? ORDER BY starts_at ASC', [$cohortId]);
    }

    public static function nextForCohort(int $cohortId): ?array
    {
        return Database::one(
            "SELECT " . self::SAFE . " FROM class_sessions
             WHERE cohort_id = ? AND status IN ('scheduled','live') AND ends_at > UTC_TIMESTAMP()
             ORDER BY starts_at ASC LIMIT 1",
            [$cohortId]
        );
    }
}
