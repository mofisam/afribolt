<?php
namespace App\Core;

/** Maps App\Foo\Bar to app/Foo/Bar.php - no Composer needed. */
final class Autoloader
{
    public static function register(string $appPath): void
    {
        spl_autoload_register(static function (string $class) use ($appPath): void {
            $prefix = 'App\\';
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                return;
            }
            $file = $appPath . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }
}
