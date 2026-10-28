<?php
namespace App\Models;

use App\Core\{Database, Model};

class Cohort extends Model
{
    protected static string $table = 'cohorts';
    protected static bool $softDeletes = true;

    private const VISIBLE = "('published','open','closed','in_progress')";

    /** Cohorts the public may see (and their parent course info). */
    public static function publicList(?int $courseId = null): array
    {
        $sql = "SELECT ch.*, co.title AS course_title, co.slug AS course_slug, co.short_description
                FROM cohorts ch JOIN courses co ON co.id = ch.course_id
                WHERE ch.deleted_at IS NULL AND co.deleted_at IS NULL AND co.status = 'published'
                  AND ch.status IN " . self::VISIBLE;
        $params = [];
        if ($courseId) {
            $sql .= ' AND ch.course_id = ?';
            $params[] = $courseId;
        }
        return Database::all($sql . ' ORDER BY ch.start_at ASC', $params);
    }

    public static function findPublicByCode(string $code): ?array
    {
        return Database::one(
            "SELECT ch.*, co.title AS course_title, co.slug AS course_slug, co.short_description, co.description AS course_description,
                    co.learning_objectives, co.prerequisites
             FROM cohorts ch JOIN courses co ON co.id = ch.course_id
             WHERE ch.cohort_code = ? AND ch.deleted_at IS NULL AND co.status = 'published'
               AND ch.status IN " . self::VISIBLE . ' LIMIT 1',
            [$code]
        );
    }

    /** Seats currently held (pending + confirmed). Call inside a transaction with the cohort row locked. */
    public static function seatsTaken(int $cohortId): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM enrolments WHERE cohort_id = ? AND status IN ('pending_payment','pending_review','confirmed')",
            [$cohortId]
        );
    }

    public static function lockById(int $id): ?array
    {
        return Database::one('SELECT * FROM cohorts WHERE id = ? AND deleted_at IS NULL FOR UPDATE', [$id]);
    }
}
