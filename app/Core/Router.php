<?php
namespace App\Core;

use App\Middleware\CsrfProtect;

/**
 * Usage (see /routes):
 *   $router->get('/courses/{slug}', [CourseController::class, 'show']);
 *   $router->group(['prefix' => '/admin', 'middleware' => [AdminAuth::class]], function ($r) { ... });
 */
final class Router
{
    private array $routes = [];
    private array $groups = [];

    public function __construct(private Request $request) {}

    public function get(string $uri, array $action, array $middleware = []): void { $this->add('GET', $uri, $action, $middleware); }
    public function post(string $uri, array $action, array $middleware = []): void { $this->add('POST', $uri, $action, $middleware); }

    public function group(array $options, callable $callback): void
    {
        $this->groups[] = $options;
        $callback($this);
        array_pop($this->groups);
    }

    private function add(string $method, string $uri, array $action, array $middleware): void
    {
        $prefix = '';
        $stack = [];
        foreach ($this->groups as $g) {
            $prefix .= $g['prefix'] ?? '';
            $stack = array_merge($stack, $g['middleware'] ?? []);
        }
        $uri = '/' . trim($prefix . '/' . trim($uri, '/'), '/');
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $uri) . '$#';
        $this->routes[] = compact('method', 'regex', 'action') + ['middleware' => array_merge($stack, $middleware)];
    }

    public function dispatch(): void
    {
        $method = $this->request->method();
        $path = $this->request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method || !preg_match($route['regex'], $path, $m)) {
                continue;
            }
            $params = array_values(array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY));

            if ($method !== 'GET') {
                (new CsrfProtect())->handle($this->request); // global CSRF (webhooks exempt inside the middleware)
            }
            foreach ($route['middleware'] as $mw) {
                (new $mw())->handle($this->request);
            }
            [$class, $fn] = $route['action'];
            (new $class($this->request))->$fn(...$params);
            return;
        }
        Response::abort(404);
    }
}
