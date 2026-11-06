<?php
namespace App\Services;

use App\Core\Database;

class CourseCatalogService
{
    /** Recalculate the cached numbers shown on cards (lessons, hours, rating, learners). Call after content/review/access changes. */
    public static function refresh(int $courseId): void
    {
        $l = Database::one("SELECT COUNT(*) AS n, COALESCE(SUM(duration_seconds), 0) AS s FROM lessons WHERE course_id = ? AND status = 'published'", [$courseId]);
        $r = Database::one("SELECT COALESCE(AVG(rating), 0) AS a, COUNT(*) AS n FROM course_reviews WHERE course_id = ? AND status = 'published'", [$courseId]);
        $n = (int) Database::value("SELECT COUNT(*) FROM course_access WHERE course_id = ? AND status = 'active'", [$courseId]);
        Database::execute('UPDATE courses SET lessons_count = ?, total_seconds = ?, rating_avg = ?, rating_count = ?, learners_count = ? WHERE id = ?',
            [(int) $l['n'], (int) $l['s'], round((float) $r['a'], 2), (int) $r['n'], $n, $courseId]);
    }
}
