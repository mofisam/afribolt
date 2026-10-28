<?php
namespace App\Models;

use App\Core\{Database, Model};

class User extends Model
{
    protected static string $table = 'users';
    protected static bool $softDeletes = true;

    public static function findByEmail(string $email): ?array
    {
        return self::findBy('email', strtolower(trim($email)));
    }

    public static function touchLogin(int $id): void
    {
        Database::execute('UPDATE users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [$id]);
    }
}
