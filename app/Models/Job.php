<?php
namespace App\Models;

use App\Core\{Database, Model};

class Job extends Model
{
    protected static string $table = 'jobs';

    private const SELECT = 'j.*, c.name AS category_name, c.slug AS category_slug';
    private const FROM = 'FROM jobs j LEFT JOIN job_categories c ON c.id = j.category_id';

    private static function where(array $f, array &$p): string
    {
        $w = ["j.status = 'published'", '(j.deadline_at IS NULL OR j.deadline_at > UTC_TIMESTAMP())'];
        foreach (preg_split('/\s+/', trim((string) ($f['q'] ?? ''))) ?: [] as $word) {
            if ($word === '') { continue; }
            $w[] = '(j.title LIKE ? OR j.company LIKE ? OR j.summary LIKE ? OR j.skills LIKE ?)';
            array_push($p, "%{$word}%", "%{$word}%", "%{$word}%", "%{$word}%");
        }
        if (!empty($f['category'])) { $w[] = 'c.slug = ?'; $p[] = $f['category']; }
        if (!empty($f['state'])) { $w[] = '(j.state = ? OR j.work_mode = \'remote\')'; $p[] = $f['state']; }
        if (!empty($f['type'])) { $w[] = 'j.employment_type = ?'; $p[] = $f['type']; }
        if (!empty($f['mode'])) { $w[] = 'j.work_mode = ?'; $p[] = $f['mode']; }
        if (!empty($f['level'])) { $w[] = 'j.experience_level = ?'; $p[] = $f['level']; }
        if (!empty($f['posted'])) { $w[] = 'COALESCE(j.posted_at, j.published_at) >= UTC_TIMESTAMP() - INTERVAL ? DAY'; $p[] = (int) $f['posted']; }
        return implode(' AND ', $w);
    }

    public static function search(array $f = [], int $limit = 15, int $offset = 0): array
    {
        $p = [];
        $where = self::where($f, $p);
        $order = ($f['sort'] ?? 'newest') === 'deadline' ? 'j.deadline_at IS NULL, j.deadline_at ASC' : 'j.is_featured DESC, COALESCE(j.posted_at, j.published_at) DESC';
        return Database::all('SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE {$where} ORDER BY {$order} LIMIT ? OFFSET ?", [...$p, $limit, $offset]);
    }

    public static function searchCount(array $f = []): int
    {
        $p = [];
        return (int) Database::value('SELECT COUNT(*) ' . self::FROM . ' WHERE ' . self::where($f, $p), $p);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one('SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE j.slug = ? AND j.status = 'published' LIMIT 1", [$slug]);
    }

    public static function latest(int $n = 6): array { return self::search([], $n, 0); }

    public static function categoryCounts(): array
    {
        return Database::all("SELECT c.name, c.slug, COUNT(j.id) AS n FROM job_categories c JOIN jobs j ON j.category_id = c.id AND j.status = 'published'
            AND (j.deadline_at IS NULL OR j.deadline_at > UTC_TIMESTAMP()) GROUP BY c.id ORDER BY n DESC, c.name");
    }

    public static function uniqueSlug(string $title, ?string $company, ?int $ignoreId = null): string
    {
        $base = substr(slugify(trim($title . ' ' . ($company ?? ''))), 0, 180);
        $slug = $base; $i = 2;
        while (Database::value('SELECT COUNT(*) FROM jobs WHERE slug = ? AND id <> ?', [$slug, $ignoreId ?? 0])) { $slug = $base . '-' . $i++; }
        return $slug;
    }
}
