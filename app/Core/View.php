<?php
namespace App\Core;

final class View
{
    /** Render a view inside a layout (pass null for no layout) and echo it. */
    public static function render(string $view, array $data = [], ?string $layout = 'public'): void
    {
        echo self::capture($view, $data, $layout);
    }

    public static function capture(string $view, array $data = [], ?string $layout = 'public'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    /** Render a view/partial and return the HTML. Also used from inside views: View::partial('partials/x', [...]) */
    public static function partial(string $view, array $data = []): string
    {
        $__file = Config::basePath() . '/app/Views/' . $view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        include $__file;
        return (string) ob_get_clean();
    }
}
