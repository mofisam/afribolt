<?php
namespace App\Core;

final class Response
{
    public static function redirect(string $to, int $code = 302): never
    {
        if ($to !== '' && $to[0] === '/' && !str_starts_with($to, '//')) {
            $to = url($to);
        }
        header('Location: ' . $to, true, $code);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status, ?string $message = null): never
    {
        http_response_code($status);
        $view = in_array($status, [403, 404], true) ? (string) $status : '500';
        try {
            View::render('errors/' . $view, ['title' => 'Error ' . $status, 'message' => $message], 'public');
        } catch (\Throwable $e) {
            echo 'Error ' . $status;
        }
        exit;
    }
}
