<?php
namespace App\Controllers\Public;

use App\Core\{Controller, Database, Session};
use App\Models\CareerLaunchRegistration;

/** Career Launch programme page + founding-price registration (CV revamp, interview coaching, references). Copy lives in the view. */
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
        $this->view('public/opportunities', ['title' => 'Career Launch', 'seatsLeft' => $this->seatsLeft(), 'seatsTotal' => self::FOUNDING_SEATS]);
    }

    public function register(): void
    {
        if ($this->request->post('website')) { $this->redirect('/career-accelerator'); } // honeypot
        $d = $this->validate([
            'full_name' => 'required|max:150', 'email' => 'required|email|max:191', 'phone' => 'required|phone',
            'target_role' => 'required|max:150', 'linkedin_url' => 'url|max:300', 'referral_code' => 'max:40',
        ], '/career-accelerator#register');

        $email = strtolower($d['email']);
        if (CareerLaunchRegistration::findBy('email', $email)) {
            Session::flash('success', 'You are already on our list. We will send your payment link and next steps to ' . $email . '.');
            $this->redirect('/career-accelerator#register');
        }
        CareerLaunchRegistration::create([
            'full_name' => $d['full_name'], 'email' => $email, 'phone' => $d['phone'], 'target_role' => $d['target_role'],
            'linkedin_url' => $d['linkedin_url'], 'referral_code' => $d['referral_code'] ? strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $d['referral_code'])) : null,
            'ip_address' => $this->request->ip(),
        ]);
        Session::flash('success', 'Thank you. We have your details and will send your payment link and next steps to ' . $email . '.');
        $this->redirect('/career-accelerator#register');
    }
}
