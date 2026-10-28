<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response};

class GuestOnly implements Middleware
{
    public function handle(Request $request): void
    {
        if (Auth::check('student')) {
            Response::redirect('/student/dashboard');
        }
    }
}
