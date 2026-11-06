<?php
namespace App\Models;

use App\Core\{Database, Model};

class Course extends Model
{
    protected static string $table = 'courses';
    protected static bool $softDeletes = true;

    private const SELECT = "c.*, cat.name AS category_name, cat.slug AS category_slug,
        CONCAT(i.first_name, ' ', i.last_name) AS instructor_name, i.biography AS instructor_bio";
    private const FROM = "FROM courses c LEFT JOIN course_categories cat ON cat.id = c.category_id LEFT JOIN instructors i ON i.id = c.instructor_id";

    /** Effective price right now (promo applies until it ends). */
    public static function priceNow(array $c): float
    {
        $promo = $c['promo_price'] !== null && ($c['promo_ends_at'] === null || $c['promo_ends_at'] > utc_now());
        return (float) ($promo ? $c['promo_price'] : $c['price']);
    }

    public static function onPromo(array $c): bool
    {
        return $c['promo_price'] !== null && ($c['promo_ends_at'] === null || $c['promo_ends_at'] > utc_now()) && (float) $c['promo_price'] < (float) $c['price'];
    }

    private static function where(array $f, array &$p): string
    {
        $w = ["c.status = 'published'", 'c.deleted_at IS NULL'];
        if (!empty($f['q'])) { $w[] = '(c.title LIKE ? OR c.short_description LIKE ? OR cat.name LIKE ?)'; array_push($p, '%' . $f['q'] . '%', '%' . $f['q'] . '%', '%' . $f['q'] . '%'); }
        if (!empty($f['category'])) { $w[] = 'cat.slug = ?'; $p[] = $f['category']; }
        if (!empty($f['level'])) { $w[] = 'c.course_level = ?'; $p[] = $f['level']; }
        if (($f['mode'] ?? '') === 'self_paced') { $w[] = "c.delivery_mode IN ('self_paced','both')"; }
        if (($f['mode'] ?? '') === 'live') { $w[] = "c.delivery_mode IN ('live','both')"; }
        if (($f['price'] ?? '') === 'free') { $w[] = "c.delivery_mode IN ('self_paced','both') AND (CASE WHEN c.promo_price IS NOT NULL AND (c.promo_ends_at IS NULL OR c.promo_ends_at > UTC_TIMESTAMP()) THEN c.promo_price ELSE c.price END) = 0"; }
        if (($f['price'] ?? '') === 'paid') { $w[] = "c.delivery_mode IN ('self_paced','both') AND (CASE WHEN c.promo_price IS NOT NULL AND (c.promo_ends_at IS NULL OR c.promo_ends_at > UTC_TIMESTAMP()) THEN c.promo_price ELSE c.price END) > 0"; }
        if (!empty($f['rating'])) { $w[] = 'c.rating_avg >= ?'; $p[] = (float) $f['rating']; }
        return implode(' AND ', $w);
    }

    public static function search(array $f = [], int $limit = 12, int $offset = 0): array
    {
        $p = [];
        $where = self::where($f, $p);
        $order = match ($f['sort'] ?? 'popular') {
            'newest' => 'c.published_at DESC, c.id DESC',
            'rating' => 'c.rating_avg DESC, c.rating_count DESC',
            'price_low' => 'c.price ASC, c.title ASC',
            'price_high' => 'c.price DESC, c.title ASC',
            default => 'c.learners_count DESC, c.rating_count DESC, c.published_at DESC',
        };
        return Database::all('SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE {$where} ORDER BY {$order} LIMIT ? OFFSET ?", [...$p, $limit, $offset]);
    }

    public static function searchCount(array $f = []): int
    {
        $p = [];
        return (int) Database::value('SELECT COUNT(*) ' . self::FROM . ' WHERE ' . self::where($f, $p), $p);
    }

    public static function published(?string $categorySlug = null): array
    {
        return self::search(['category' => $categorySlug], 100);
    }

    public static function findPublishedBySlug(string $slug): ?array
    {
        return Database::one('SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE c.slug = ? AND c.status = 'published' AND c.deleted_at IS NULL LIMIT 1", [$slug]);
    }

    /** Any non-draft course (learners keep access to archived courses). */
    public static function findForLearning(string $slug): ?array
    {
        return Database::one('SELECT ' . self::SELECT . ' ' . self::FROM . " WHERE c.slug = ? AND c.status IN ('published','archived') AND c.deleted_at IS NULL LIMIT 1", [$slug]);
    }

    public static function categoryCounts(): array
    {
        return Database::all("SELECT cat.name, cat.slug, COUNT(c.id) AS n FROM course_categories cat
            JOIN courses c ON c.category_id = cat.id AND c.status = 'published' AND c.deleted_at IS NULL
            WHERE cat.status = 'active' GROUP BY cat.id ORDER BY cat.display_order, cat.name");
    }
}
