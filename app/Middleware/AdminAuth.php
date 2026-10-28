<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class AdminAuth implements Middleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check('admin')) {
            Response::redirect('/admin/login');
        }
    }
}
