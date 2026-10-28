<?php
use App\Core\{Config, Csrf, Request, Session};

function env(string $key, mixed $default = null): mixed { return Config::env($key, $default); }
function config(string $key, mixed $default = null): mixed { return Config::get($key, $default); }
function base_path(string $path = ''): string { return Config::basePath() . ($path ? '/' . ltrim($path, '/') : ''); }

/** HTML-escape. Use on EVERY value printed in a view. */
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function url(string $path = ''): string
{
    $base = rtrim((string) config('app.url', ''), '/');
    if ($base === '') {
        $base = Request::basePath();
    }
    return $base . '/' . ltrim($path, '/');
}
function asset(string $path): string { return url('assets/' . ltrim($path, '/')); }
function csrf_field(): string { return Csrf::field(); }
function csrf_token(): string { return Csrf::token(); }
function old(string $key, mixed $default = ''): mixed { return Session::old($key, $default); }
function field_error(string $key): string
{
    $m = Session::error($key);
    return $m ? '<p class="mt-1 text-sm text-red-600">' . e($m) . '</p>' : '';
}

function slugify(string $text): string
{
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $t = strtolower(trim(preg_replace('/[^A-Za-z0-9]+/', '-', $t !== false ? $t : $text), '-'));
    return $t !== '' ? $t : 'item';
}
