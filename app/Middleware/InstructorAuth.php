<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class InstructorAuth implements Middleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check('instructor')) {
            Response::redirect('/instructor/login');
        }
    }
}
