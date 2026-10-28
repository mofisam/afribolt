<?php
namespace App\Controllers\Auth;

use App\Core\{Auth, Controller, Session};
use App\Models\{EmailVerificationToken, User};
use App\Services\MailService;

class RegisterController extends Controller
{
    public function show(): void
    {
        $this->view('auth/register', ['title' => 'Create your account'], 'auth');
    }

    public function submit(): void
    {
        $min = (int) config('security.password_min_length', 8);
        $data = $this->validate([
            'first_name' => 'required|max:100',
            'last_name'  => 'required|max:100',
            'email'      => 'required|email|max:191',
            'phone'      => 'phone',
            'password'   => "required|min:{$min}|max:200",
            'password_confirmation' => 'required|same:password',
            'terms'      => 'required',
        ], '/register');

        $email = strtolower($data['email']);
        if (User::findByEmail($email)) {
            Session::flashInput($this->request->all(), ['email' => 'An account with this email already exists. Try logging in.']);
            $this->redirect('/register');
        }

        $needsVerify = (bool) config('security.require_email_verification', false);
        $id = User::create([
            'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'email' => $email, 'phone' => $data['phone'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'account_status' => $needsVerify ? 'pending_verification' : 'active',
            'email_verified_at' => $needsVerify ? null : utc_now(),
        ]);

        if ($needsVerify) {
            $raw = bin2hex(random_bytes(32));
            EmailVerificationToken::create(['user_id' => $id, 'token_hash' => hash('sha256', $raw), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 86400)]);
            (new MailService())->queue($id, $email, 'verify-email', 'Verify your email', ['name' => $data['first_name'], 'link' => url('/verify-email/' . $raw)]);
        }
        (new MailService())->queue($id, $email, 'welcome', 'Welcome to AfriBolt', ['name' => $data['first_name']]);

        Auth::login('student', $id);
        Session::flash('success', $needsVerify ? 'Account created. Please check your email to verify it.' : 'Welcome aboard!');
        $this->redirect((string) Session::pull('intended', '/student/dashboard'));
    }
}
