<?php
namespace App\Controllers\Admin;

use App\Core\{Auth, Controller, Database, Request, Response, Session};
use App\Services\AuditService;

/**
 * Shared helpers for admin modules.
 * Set $roles on a child controller to restrict the whole module (super_admin always passes).
 */
abstract class BaseAdminController extends Controller
{
    /** @var string[] admin roles allowed in this module; [] = any logged-in admin */
    protected array $roles = [];

    public function __construct(Request $request)
    {
        parent::__construct($request);
        if ($this->roles) {
            $this->allow(...$this->roles);
        }
    }

    protected function allow(string ...$roles): void
    {
        if (!Auth::adminHasRole('super_admin', ...$roles)) {
            Response::abort(403, 'Your admin role does not have access to this area.');
        }
    }

    protected function adminId(): int { return (int) Auth::id('admin'); }

    protected function page(string $view, array $data = []): void
    {
        $this->view($view, $data, 'admin');
    }

    protected function done(string $message, string $to, string $type = 'success'): never
    {
        Session::flash($type, $message);
        $this->redirect($to);
    }

    /** @return array{0:array,1:array} [rows, pager] */
    protected function paged(string $from, array $params, string $select, string $order, int $perPage = 20): array
    {
        $page = max(1, (int) $this->request->query('page', 1));
        $total = (int) Database::value("SELECT COUNT(*) FROM {$from}", $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $rows = Database::all("SELECT {$select} FROM {$from} ORDER BY {$order} LIMIT ? OFFSET ?", [...$params, $perPage, ($page - 1) * $perPage]);
        return [$rows, ['page' => $page, 'pages' => $pages, 'total' => $total]];
    }

    /** Record only the fields that actually changed. */
    protected function audit(string $action, string $entity, ?int $id, ?array $old, array $new): void
    {
        if ($old === null) {
            AuditService::log($action, $entity, $id, null, $new);
            return;
        }
        $o = $n = [];
        foreach ($new as $k => $v) {
            if ((string) ($old[$k] ?? '') !== (string) ($v ?? '')) { $o[$k] = $old[$k] ?? null; $n[$k] = $v; }
        }
        if ($n) { AuditService::log($action, $entity, $id, $o, $n); }
    }

    protected function uniqueSlug(string $table, string $base, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $slug = $base = slugify($base);
        $i = 2;
        while (Database::value("SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = ? AND id <> ?", [$slug, $ignoreId ?? 0])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** UTC DB value -> value for <input type="datetime-local"> in $tz */
    protected function toInput(?string $utc, string $tz): string
    {
        return $utc ? local_date($utc, $tz, 'Y-m-d\TH:i') : '';
    }

    /** datetime-local value typed in $tz -> UTC DB value */
    protected function fromInput(?string $value, string $tz): ?string
    {
        return ($value !== null && $value !== '') ? local_to_utc(str_replace('T', ' ', $value), $tz) : null;
    }

    protected function badge(string $text, string $tone = ''): string
    {
        $good = ['active', 'open', 'published', 'confirmed', 'successful', 'sent', 'processed', 'present'];
        $bad = ['suspended', 'closed', 'cancelled', 'failed', 'refunded', 'absent'];
        $cls = $tone ?: (in_array($text, $good, true) ? 'badge-open' : (in_array($text, $bad, true) ? 'badge-closed' : ''));
        return '<span class="badge ' . $cls . '">' . e(str_replace('_', ' ', $text)) . '</span>';
    }

    protected function checkbox(string $name): int { return $this->request->post($name) === '1' ? 1 : 0; }

    protected function defaultTz(): string
    {
        return (string) (\App\Models\PlatformSetting::value('default_timezone') ?: config('app.timezone', 'Africa/Lagos'));
    }

    protected function defaultCurrency(): string
    {
        return (string) (\App\Models\PlatformSetting::value('default_currency') ?: 'NGN');
    }

    protected function timezones(): array
    {
        $list = ['Africa/Lagos', 'Africa/Accra', 'Africa/Nairobi', 'Africa/Johannesburg', 'UTC', 'Europe/London', 'America/New_York', 'America/Toronto', 'Asia/Dubai'];
        return array_combine($list, $list);
    }
}
