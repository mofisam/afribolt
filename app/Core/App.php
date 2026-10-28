<?php
namespace App\Core;

final class App
{
    public function __construct(private string $basePath) {}

    public function run(): void
    {
        Config::boot($this->basePath);
        date_default_timezone_set('UTC'); // storage + runtime are UTC; display converts (see Helpers/date.php)
        $this->registerErrorHandling();
        Session::start();

        $request = Request::capture();
        $router  = new Router($request);

        foreach (['web', 'student', 'admin', 'webhooks'] as $file) {
            require $this->basePath . '/routes/' . $file . '.php'; // each file uses $router
        }

        $router->dispatch();
    }

    private function registerErrorHandling(): void
    {
        $debug = (bool) Config::get('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', $this->basePath . '/storage/logs/php_errors.log');

        set_exception_handler(function (\Throwable $e) use ($debug): void {
            $line = sprintf("[%s] %s: %s in %s:%d\n%s\n", gmdate('c'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
            @file_put_contents($this->basePath . '/storage/logs/app.log', $line, FILE_APPEND);
            if ($debug) {
                http_response_code(500);
                echo '<pre style="padding:1rem;white-space:pre-wrap">' . htmlspecialchars($line) . '</pre>';
                return;
            }
            Response::abort(500);
        });
    }
}
