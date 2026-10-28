<?php
namespace App\Models;

use App\Core\{Database, Model};

class PlatformSetting extends Model
{
    protected static string $table = 'platform_settings';

    public static function value(string $key, mixed $default = null): mixed
    {
        $row = Database::one('SELECT setting_value FROM platform_settings WHERE setting_key = ?', [$key]);
        return $row['setting_value'] ?? $default;
    }
}
