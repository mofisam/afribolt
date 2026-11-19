<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class EmployerAuth implements Middleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check('employer')) {
            Response::redirect('/employer/login');
        }
    }
}
