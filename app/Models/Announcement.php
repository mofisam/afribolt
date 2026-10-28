<?php
namespace App\Models;

use App\Core\{Database, Model};

class Announcement extends Model
{
    protected static string $table = 'announcements';

    /** Published announcements a student may see: public ones + those for cohorts they are confirmed in. */
    public static function forStudent(int $studentId, int $limit = 5): array
    {
        return Database::all(
            "SELECT a.*, ch.cohort_name
             FROM announcements a LEFT JOIN cohorts ch ON ch.id = a.cohort_id
             WHERE a.status = 'published' AND a.visibility IN ('public','enrolled_students')
               AND (a.publish_at IS NULL OR a.publish_at <= UTC_TIMESTAMP())
               AND (a.expires_at IS NULL OR a.expires_at > UTC_TIMESTAMP())
               AND (a.cohort_id IS NULL OR a.cohort_id IN
                    (SELECT cohort_id FROM enrolments WHERE student_id = ? AND status IN ('confirmed','completed')))
             ORDER BY COALESCE(a.publish_at, a.created_at) DESC LIMIT ?",
            [$studentId, $limit]
        );
    }
}
