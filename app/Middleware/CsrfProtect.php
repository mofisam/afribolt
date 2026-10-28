<?php
namespace App\Middleware;

use App\Core\{Csrf, Request, Response};

class CsrfProtect implements Middleware
{
    public function handle(Request $request): void
    {
        // Payment-provider webhooks can't send a CSRF token; they are verified by signature instead.
        if (str_starts_with($request->path(), '/webhooks/')) {
            return;
        }
        if (!Csrf::verify($request)) {
            if ($request->isAjax()) {
                Response::json(['ok' => false, 'message' => 'Session expired. Refresh the page.'], 419);
            }
            Response::abort(403, 'Your session expired. Please go back, refresh and try again.');
        }
    }
}
