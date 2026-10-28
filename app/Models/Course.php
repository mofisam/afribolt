<?php
namespace App\Models;

use App\Core\{Database, Model};

class Course extends Model
{
    protected static string $table = 'courses';
    protected static bool $softDeletes = true;

    public static function published(?string $categorySlug = null): array
    {
        $sql = "SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
                FROM courses c LEFT JOIN course_categories cat ON cat.id = c.category_id
                WHERE c.status = 'published' AND c.deleted_at IS NULL";
        $params = [];
        if ($categorySlug) {
            $sql .= ' AND cat.slug = ?';
            $params[] = $categorySlug;
        }
        return Database::all($sql . ' ORDER BY c.title ASC', $params);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one(
            "SELECT c.*, cat.name AS category_name FROM courses c
             LEFT JOIN course_categories cat ON cat.id = c.category_id
             WHERE c.slug = ? AND c.status = 'published' AND c.deleted_at IS NULL LIMIT 1",
            [$slug]
        );
    }
}
