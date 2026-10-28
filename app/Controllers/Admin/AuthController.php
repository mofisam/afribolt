<?php
namespace App\Controllers\Admin;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\AdminUser;

class AuthController extends Controller
{
    public function show(): void { $this->view('admin/login', ['title' => 'Admin login'], 'auth'); }

    public function submit(): void
    {
        if ((int) Session::get('admin_locked_until', 0) > time()) {
            Session::flash('error', 'Too many attempts. Try again later.');
            $this->redirect('/admin/login');
        }
        $data = $this->validate(['email' => 'required|email', 'password' => 'required'], '/admin/login');
        $admin = AdminUser::findBy('email', strtolower($data['email']));
        $hash = $admin['password_hash'] ?? password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

        if (!password_verify($data['password'], $hash) || !$admin || $admin['account_status'] !== 'active') {
            $n = (int) Session::get('admin_attempts', 0) + 1;
            Session::put('admin_attempts', $n);
            if ($n >= 5) { Session::put('admin_locked_until', time() + 900); Session::put('admin_attempts', 0); }
            Session::flash('error', 'Incorrect email or password.');
            $this->redirect('/admin/login');
        }
        Session::forget('admin_attempts');
        Auth::login('admin', (int) $admin['id']);
        Database::execute('UPDATE admin_users SET last_login_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $admin['id']]);
        $this->redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout('admin');
        $this->redirect('/admin/login');
    }
}
