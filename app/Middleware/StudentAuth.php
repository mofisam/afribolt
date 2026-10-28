<?php
namespace App\Middleware;

use App\Core\{Auth, Request, Response, Session};

class StudentAuth implements Middleware
{
    public function handle(Request $request): void
    {
        if (Auth::check('student')) {
            return;
        }
        if ($request->isAjax()) {
            Response::json(['ok' => false, 'message' => 'Please log in.'], 401);
        }
        if ($request->method() === 'GET') {
            Session::put('intended', $request->path());
        }
        Session::flash('error', 'Please log in to continue.');
        Response::redirect('/login');
    }
}
