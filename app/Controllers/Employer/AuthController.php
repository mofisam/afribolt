<?php
namespace App\Controllers\Employer;

use App\Core\{Auth, Controller, Session};
use App\Models\Employer;

/** Company sign-up and login. A company can log in straight away, but cannot post jobs until an admin verifies it. */
class AuthController extends Controller
{
    public function landing(): void { $this->view('public/employers', ['title' => 'Hire with Afribolt']); }

    public function showLogin(): void
    {
        if (Auth::check('employer')) { $this->redirect('/employer'); }
        $this->view('employer/login', ['title' => 'Employer login'], 'auth');
    }

    public function login(): void
    {
        if ((int) Session::get('emp_locked_until', 0) > time()) {
            Session::flash('error', 'Too many attempts. Try again in a few minutes.');
            $this->redirect('/employer/login');
        }
        $d = $this->validate(['email' => 'required|email', 'password' => 'required'], '/employer/login');
        $e = Employer::findBy('email', strtolower($d['email']));
        $hash = ($e['password_hash'] ?? null) ?: password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT); // keeps timing the same
        if (!password_verify($d['password'], $hash) || !$e) {
            $n = (int) Session::get('emp_attempts', 0) + 1;
            Session::put('emp_attempts', $n);
            if ($n >= 5) { Session::put('emp_locked_until', time() + 900); Session::put('emp_attempts', 0); }
            Session::flashInput(['email' => $d['email']]);
            Session::flash('error', 'Incorrect email or password.');
            $this->redirect('/employer/login');
        }
        if ($e['account_status'] !== 'active') {
            Session::flash('error', 'This company account is suspended. Please contact us.');
            $this->redirect('/employer/login');
        }
        Session::forget('emp_attempts');
        Auth::login('employer', (int) $e['id']);
        $this->redirect('/employer');
    }

    public function showRegister(): void
    {
        if (Auth::check('employer')) { $this->redirect('/employer'); }
        $this->view('employer/register', ['title' => 'Register your company'], 'auth');
    }

    public function register(): void
    {
        if ($this->request->post('website_url')) { $this->redirect('/'); } // honeypot
        $min = (int) config('security.password_min_length', 8);
        $d = $this->validate([
            'company_name' => 'required|max:200', 'contact_first_name' => 'required|max:100', 'contact_last_name' => 'required|max:100',
            'email' => 'required|email|max:191', 'phone' => 'required|phone',
            'password' => "required|min:{$min}|max:200", 'password_confirmation' => 'required|same:password',
        ], '/employer/register');
        if ($this->request->post('agree') !== '1') {
            Session::flashInput(array_diff_key($d, ['password' => 1, 'password_confirmation' => 1]));
            Session::flash('error', 'Please confirm that you are authorised to post jobs for this company.');
            $this->redirect('/employer/register');
        }
        $email = strtolower($d['email']);
        if (Employer::findBy('email', $email)) {
            Session::flashInput(array_diff_key($d, ['password' => 1, 'password_confirmation' => 1]));
            Session::flash('error', 'A company account with this email already exists. Please log in instead.');
            $this->redirect('/employer/register');
        }
        $id = Employer::create([
            'company_name' => $d['company_name'], 'contact_first_name' => $d['contact_first_name'], 'contact_last_name' => $d['contact_last_name'],
            'email' => $email, 'phone' => $d['phone'], 'password_hash' => password_hash($d['password'], PASSWORD_DEFAULT),
        ]);
        Auth::login('employer', (int) $id);
        Session::flash('success', 'Your company account is created. Next, complete your company profile and upload your documents so we can verify you.');
        $this->redirect('/employer/verification');
    }

    public function logout(): void
    {
        Auth::logout('employer');
        $this->redirect('/employer/login');
    }
}
