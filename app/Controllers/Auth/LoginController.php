<?php
namespace App\Controllers\Auth;

use App\Core\{Auth, Controller, Session};
use App\Models\User;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900;

    public function show(): void
    {
        $this->view('auth/login', ['title' => 'Log in'], 'auth');
    }

    public function submit(): void
    {
        if ((int) Session::get('login_locked_until', 0) > time()) {
            Session::flash('error', 'Too many attempts. Please wait a few minutes and try again.');
            $this->redirect('/login');
        }
        $data = $this->validate(['email' => 'required|email', 'password' => 'required'], '/login');

        $user = User::findByEmail($data['email']);
        // Always run password_verify so response time doesn't reveal whether the email exists
        $hash = $user['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // equalise timing
        $ok = password_verify($data['password'], $hash) && $user && !in_array($user['account_status'], ['suspended', 'closed'], true);

        if (!$ok) {
            $n = (int) Session::get('login_attempts', 0) + 1;
            Session::put('login_attempts', $n);
            if ($n >= self::MAX_ATTEMPTS) {
                Session::put('login_locked_until', time() + self::LOCK_SECONDS);
                Session::put('login_attempts', 0);
            }
            Session::flashInput(['email' => $data['email']]);
            Session::flash('error', 'Incorrect email or password.');
            $this->redirect('/login');
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)]);
        }
        Session::forget('login_attempts');
        Auth::login('student', (int) $user['id']);
        User::touchLogin((int) $user['id']);
        $this->redirect((string) Session::pull('intended', '/student/dashboard'));
    }

    public function logout(): void
    {
        Auth::logout('student');
        Session::flash('success', 'You have been logged out.');
        $this->redirect('/');
    }
}
