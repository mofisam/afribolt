<?php
namespace App\Controllers\Instructor;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\Instructor;

class AuthController extends Controller
{
    public function show(): void { $this->view('instructor/login', ['title' => 'Instructor login'], 'auth'); }

    public function submit(): void
    {
        if ((int) Session::get('instr_locked_until', 0) > time()) {
            Session::flash('error', 'Too many attempts. Try again later.');
            $this->redirect('/instructor/login');
        }
        $d = $this->validate(['email' => 'required|email', 'password' => 'required'], '/instructor/login');
        $i = Instructor::findBy('email', strtolower($d['email']));
        $hash = ($i['password_hash'] ?? null) ?: password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        if (!password_verify($d['password'], $hash) || !$i || $i['account_status'] !== 'active') {
            $n = (int) Session::get('instr_attempts', 0) + 1;
            Session::put('instr_attempts', $n);
            if ($n >= 5) { Session::put('instr_locked_until', time() + 900); Session::put('instr_attempts', 0); }
            Session::flash('error', 'Incorrect email or password.');
            $this->redirect('/instructor/login');
        }
        Session::forget('instr_attempts');
        Auth::login('instructor', (int) $i['id']);
        $this->redirect('/instructor');
    }

    public function logout(): void
    {
        Auth::logout('instructor');
        $this->redirect('/instructor/login');
    }
}
