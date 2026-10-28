<?php
namespace App\Models;

use App\Core\{Database, Model};

class Enrolment extends Model
{
    protected static string $table = 'enrolments';

    public static function forStudentAndCohort(int $studentId, int $cohortId): ?array
    {
        return Database::one('SELECT * FROM enrolments WHERE student_id = ? AND cohort_id = ? LIMIT 1', [$studentId, $cohortId]);
    }

    /** Student's enrolments with cohort + course info (dashboard). */
    public static function forStudent(int $studentId): array
    {
        return Database::all(
            "SELECT e.*, ch.cohort_name, ch.cohort_code, ch.start_at, ch.end_at, ch.timezone, ch.status AS cohort_status,
                    co.title AS course_title
             FROM enrolments e
             JOIN cohorts ch ON ch.id = e.cohort_id
             JOIN courses co ON co.id = ch.course_id
             WHERE e.student_id = ? ORDER BY ch.start_at DESC",
            [$studentId]
        );
    }

    public static function findOwned(int $id, int $studentId): ?array
    {
        return Database::one('SELECT * FROM enrolments WHERE id = ? AND student_id = ? LIMIT 1', [$id, $studentId]);
    }
}
