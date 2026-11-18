<?php
namespace App\Services;

use App\Core\Database;
use App\Models\PlatformSetting;

/**
 * Career Launch price + founding-offer timer, editable by admins (Admin > Career Launch > Price & offer timer).
 * The offer is ACTIVE while: the timer has not run out (or no timer is set) AND founding places are left.
 * Otherwise the regular price applies. The price is worked out when the student presses Pay.
 */
class CareerLaunchPricing
{
    private const PREFIX = 'career_launch.';

    /** Raw saved settings with defaults. ends_at is a UTC datetime string or null. */
    public static function settings(): array
    {
        $get = function (string $k, $d) { try { $v = PlatformSetting::value(self::PREFIX . $k, $d); } catch (\Throwable $e) { $v = $d; } return ($v === '' || $v === null) ? $d : $v; };
        return [
            'offer_price'   => (float) $get('offer_price', 50000),
            'regular_price' => (float) $get('regular_price', 500000),
            'seats'         => (int) $get('seats', 100),
            'ends_at'       => $get('offer_ends_at', null),
        ];
    }

    /** Everything the pages need to show the right price, timer and places left. */
    public static function current(): array
    {
        $s = self::settings();
        try { $paid = (int) Database::value("SELECT COUNT(*) FROM career_launch_registrations WHERE status = 'paid'"); } catch (\Throwable $e) { $paid = 0; }
        $left = max(0, $s['seats'] - $paid);
        $endTs = $s['ends_at'] ? strtotime($s['ends_at'] . ' UTC') : null;
        $timeOk = $endTs === null || $endTs > time();
        $active = $timeOk && $left > 0 && $s['offer_price'] > 0;
        return $s + [
            'paid' => $paid, 'seats_left' => $left, 'active' => $active,
            'price' => $active ? $s['offer_price'] : $s['regular_price'],
            'ends_iso' => ($active && $endTs) ? gmdate('c', $endTs) : null,
            'ends_label' => ($active && $endTs) ? local_date($s['ends_at'], self::tz(), 'D j M Y, g:i A') : null,
            'ended_by' => $active ? null : ($left <= 0 ? 'seats' : 'time'),
        ];
    }

    private static function tz(): string
    {
        try { return (string) (PlatformSetting::value('default_timezone') ?: config('app.timezone', 'Africa/Lagos')); } catch (\Throwable $e) { return 'Africa/Lagos'; }
    }

    public static function naira(float $n): string { return '₦' . number_format($n, $n == floor($n) ? 0 : 2); }

    public static function set(string $key, ?string $value, ?int $adminId): void
    {
        $full = self::PREFIX . $key;
        $row = PlatformSetting::findBy('setting_key', $full);
        if ($row) { PlatformSetting::update((int) $row['id'], ['setting_value' => $value, 'updated_by_admin_id' => $adminId]); return; }
        PlatformSetting::create(['setting_key' => $full, 'setting_value' => $value, 'value_type' => $key === 'offer_ends_at' ? 'string' : 'number',
            'description' => 'Career Launch pricing (edit on the Career Launch admin page)', 'is_public' => 0, 'updated_by_admin_id' => $adminId]);
    }
}
