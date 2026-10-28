<?php
namespace App\Core;

/** Loads .env once, then lazily loads config/*.php files. Usage: Config::get('app.name') */
final class Config
{
    private static array $env = [];
    private static array $files = [];
    private static string $base = '';

    public static function boot(string $basePath): void
    {
        self::$base = $basePath;
        $file = $basePath . '/.env';
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $value = trim($value);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $q = $value[0];
                $end = strrpos($value, $q);
                $value = $end > 0 ? substr($value, 1, $end - 1) : substr($value, 1);
            } else {
                $value = trim(preg_replace('/\s+#.*$/', '', $value)); // strip inline comments
            }
            self::$env[trim($key)] = $value;
        }
    }

    public static function basePath(): string
    {
        return self::$base;
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        $value = self::$env[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }
        switch (strtolower((string) $value)) {
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
        }
        return $value;
    }

    public static function get(string $path, mixed $default = null): mixed
    {
        $parts = explode('.', $path);
        $file = array_shift($parts);
        if (!isset(self::$files[$file])) {
            $f = self::$base . '/config/' . $file . '.php';
            self::$files[$file] = is_file($f) ? require $f : [];
        }
        $value = self::$files[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
