<?php
namespace App\Core;

use App\Models\AdminUser;
use App\Models\Instructor;
use App\Models\User;

/** Three separate guards: student (users), admin (admin_users), instructor (instructors). */
final class Auth
{
    private const MODELS = [
        'student'    => User::class,
        'admin'      => AdminUser::class,
        'instructor' => Instructor::class,
    ];
    private static array $cache = [];

    public static function login(string $guard, int $id): void
    {
        Session::regenerate(); // prevent session fixation
        Session::put('auth_' . $guard, $id);
        unset(self::$cache[$guard]);
    }

    public static function id(string $guard = 'student'): ?int
    {
        $id = Session::get('auth_' . $guard);
        return $id ? (int) $id : null;
    }

    public static function check(string $guard = 'student'): bool
    {
        return self::user($guard) !== null;
    }

    public static function user(string $guard = 'student'): ?array
    {
        $id = self::id($guard);
        if (!$id) {
            return null;
        }
        if (!array_key_exists($guard, self::$cache)) {
            $model = self::MODELS[$guard];
            $user = $model::find($id);
            $status = $user['account_status'] ?? null;
            $blocked = $status === null || in_array($status, ['suspended', 'closed', 'invited'], true);
            if ($blocked) {
                self::logout($guard);
                $user = null;
            }
            self::$cache[$guard] = $user;
        }
        return self::$cache[$guard];
    }

    public static function logout(string $guard): void
    {
        Session::forget('auth_' . $guard);
        unset(self::$cache[$guard]);
    }

    public static function adminHasRole(string ...$roles): bool
    {
        $u = self::user('admin');
        return $u !== null && in_array($u['admin_role'], $roles, true);
    }
}
