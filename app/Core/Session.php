<?php
namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
        session_name('afribolt_sid');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_start();
    }

    public static function regenerate(): void { session_regenerate_id(true); }
    public static function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public static function put(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $v;
    }

    /** Flash messages: Session::flash('success', 'Saved'). Read with Session::flashes(). */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type][] = $message;
    }
    public static function flashes(): array { return self::pull('_flash', []); }

    /** Remember form input + errors across a redirect. */
    public static function flashInput(array $input, array $errors = []): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token']);
        $_SESSION['_old'] = $input;
        $_SESSION['_errors'] = $errors;
    }
    public static function old(string $key, mixed $default = ''): mixed { return $_SESSION['_old'][$key] ?? $default; }
    public static function error(string $key): ?string { return $_SESSION['_errors'][$key] ?? null; }
    public static function clearInput(): void { unset($_SESSION['_old'], $_SESSION['_errors']); }
}
