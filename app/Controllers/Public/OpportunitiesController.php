<?php
namespace App\Controllers\Public;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\CareerLaunchRegistration;
use App\Services\CareerLaunchService;

/** Career Launch programme page. Joining requires a student account; name, email and phone come from it. */
class OpportunitiesController extends Controller
{
    public const FOUNDING_SEATS = 100;

    /** Founding places still open. Null if the table is not installed yet (page still works). */
    private function seatsLeft(): ?int
    {
        try {
            $taken = (int) Database::value("SELECT COUNT(*) FROM career_launch_registrations WHERE status = 'paid'");
            return max(0, self::FOUNDING_SEATS - $taken);
        } catch (\Throwable $e) { return null; }
    }

    public function index(): void
    {
        $student = Auth::user('student');
        $this->view('public/opportunities', [
            'title' => 'Career Launch', 'seatsLeft' => $this->seatsLeft(), 'seatsTotal' => self::FOUNDING_SEATS,
            'student' => $student, 'registration' => $student ? CareerLaunchService::forUser((int) $student['id']) : null,
        ]);
    }

    /** Visitors without an account: send them to sign up / log in, then bring them back to the join form. */
    public function join(): void
    {
        if (Auth::check('student')) { $this->redirect('/career-accelerator#register'); }
        Session::put('intended', '/career-accelerator#register');
        $this->redirect($this->request->query('to') === 'login' ? '/login' : '/register');
    }

    public function register(): void
    {
        $user = Auth::user('student');
        if (!$user) {
            Session::put('intended', '/career-accelerator#register');
            Session::flash('error', 'Please log in or create a free account to join Career Launch.');
            $this->redirect('/login');
        }
        if ($this->request->post('website')) { $this->redirect('/career-accelerator'); } // honeypot

        $existing = CareerLaunchService::forUser((int) $user['id']);
        if ($existing) {
            Session::flash('success', 'You have already joined Career Launch. Follow your progress on your dashboard.');
            $this->redirect('/career-accelerator#register');
        }

        $rules = ['target_role' => 'required|max:150', 'linkedin_url' => 'url|max:300', 'referral_code' => 'max:40'];
        if (empty($user['phone'])) { $rules['phone'] = 'required|phone'; } // only ask for what the account does not have
        $d = $this->validate($rules, '/career-accelerator#register');

        $phone = $user['phone'] ?: $d['phone'];
        if (empty($user['phone'])) { Database::execute('UPDATE users SET phone = ? WHERE id = ?', [$phone, (int) $user['id']]); }

        $email = strtolower((string) $user['email']);
        $row = [
            'user_id' => (int) $user['id'], 'full_name' => trim($user['first_name'] . ' ' . $user['last_name']), 'email' => $email, 'phone' => $phone,
            'target_role' => $d['target_role'], 'linkedin_url' => $d['linkedin_url'],
            'referral_code' => $d['referral_code'] ? strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $d['referral_code'])) : null,
        ];
        $legacy = CareerLaunchRegistration::findBy('email', $email); // someone who filled the old public form with this email
        if ($legacy && empty($legacy['user_id'])) { CareerLaunchRegistration::update((int) $legacy['id'], $row); }
        else { CareerLaunchRegistration::create($row + ['ip_address' => $this->request->ip()]); }

        Session::flash('success', 'You are in. We will send your payment link and next steps to ' . $email . '. You can follow your progress on your dashboard.');
        $this->redirect('/career-accelerator#register');
    }
}
