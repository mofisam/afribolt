<?php
namespace App\Core;

final class Request
{
    private static string $basePath = '';

    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $post,
        private array $files,
        private array $server
    ) {}

    public static function capture(): self
    {
        $script = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        self::$basePath = $script;

        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $uri  = rawurldecode($uri);
        if ($script !== '' && str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script));
        }
        $uri = '/' . trim($uri, '/');

        return new self(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'), $uri, $_GET, $_POST, $_FILES, $_SERVER);
    }

    public static function basePath(): string { return self::$basePath; }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }
    public function isPost(): bool { return $this->method === 'POST'; }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }
    public function post(string $key, mixed $default = null): mixed { return $this->post[$key] ?? $default; }
    public function query(string $key, mixed $default = null): mixed { return $this->query[$key] ?? $default; }
    public function all(): array { return $this->post + $this->query; }
    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) { $out[$k] = $this->post[$k] ?? null; }
        return $out;
    }
    public function file(string $key): ?array { return $this->files[$key] ?? null; }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest'
            || str_contains($this->header('Accept') ?? '', 'application/json');
    }

    public function ip(): string { return $this->server['REMOTE_ADDR'] ?? ''; }
    public function userAgent(): string { return substr($this->server['HTTP_USER_AGENT'] ?? '', 0, 500); }
    public function rawBody(): string { return file_get_contents('php://input') ?: ''; }
}
