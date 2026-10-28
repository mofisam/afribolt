<?php
namespace App\Controllers\Auth;

use App\Core\{Controller, Database, Session};
use App\Models\{PasswordResetToken, User};
use App\Services\MailService;

class PasswordResetController extends Controller
{
    public function forgot(): void { $this->view('auth/forgot', ['title' => 'Reset password'], 'auth'); }

    public function sendLink(): void
    {
        $data = $this->validate(['email' => 'required|email'], '/forgot-password');
        $user = User::findByEmail($data['email']);
        if ($user) {
            $raw = bin2hex(random_bytes(32));
            PasswordResetToken::create([
                'user_id' => (int) $user['id'], 'token_hash' => hash('sha256', $raw),
                'expires_at' => gmdate('Y-m-d H:i:s', time() + 60 * 60),
            ]);
            (new MailService())->queue((int) $user['id'], $user['email'], 'password-reset', 'Reset your password',
                ['name' => $user['first_name'], 'link' => url('/reset-password/' . $raw)]);
        }
        // Same message either way: don't reveal which emails have accounts
        Session::flash('success', 'If that email has an account, a reset link is on its way.');
        $this->redirect('/forgot-password');
    }

    public function form(string $token): void
    {
        $this->findToken($token) ?? $this->invalid();
        $this->view('auth/reset', ['title' => 'Choose a new password', 'token' => $token], 'auth');
    }

    public function reset(string $token): void
    {
        $row = $this->findToken($token) ?? $this->invalid();
        $min = (int) config('security.password_min_length', 8);
        $data = $this->validate(['password' => "required|min:{$min}", 'password_confirmation' => 'required|same:password'], '/reset-password/' . $token);

        Database::transaction(function () use ($row, $data) {
            User::update((int) $row['user_id'], ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)]);
            Database::execute('UPDATE password_reset_tokens SET used_at = UTC_TIMESTAMP() WHERE id = ?', [(int) $row['id']]);
        });
        Session::flash('success', 'Password updated. Please log in.');
        $this->redirect('/login');
    }

    private function findToken(string $raw): ?array
    {
        return Database::one('SELECT * FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > UTC_TIMESTAMP() LIMIT 1', [hash('sha256', $raw)]);
    }

    private function invalid(): never
    {
        Session::flash('error', 'This reset link is invalid or has expired.');
        $this->redirect('/forgot-password');
    }
}
