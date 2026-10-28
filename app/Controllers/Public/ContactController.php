<?php
namespace App\Controllers\Public;

use App\Core\{Controller, Session};
use App\Models\Enquiry;

class ContactController extends Controller
{
    public function show(): void
    {
        $this->view('public/contact', ['title' => 'Contact us', 'type' => $this->request->query('type', 'general')]);
    }

    public function submit(): void
    {
        // Honeypot: real people never fill the hidden "website" field
        if ($this->request->post('website')) {
            $this->redirect('/contact');
        }
        $data = $this->validate([
            'full_name' => 'required|max:150',
            'email'     => 'required|email|max:191',
            'phone'     => 'phone',
            'subject'   => 'max:200',
            'message'   => 'required|min:10|max:3000',
            'enquiry_type' => 'in:general,course,career_consultation',
        ], '/contact');

        Enquiry::create([
            'enquiry_type' => $data['enquiry_type'] ?: 'general',
            'full_name' => $data['full_name'], 'email' => strtolower($data['email']), 'phone' => $data['phone'],
            'subject' => $data['subject'], 'message' => $data['message'], 'ip_address' => $this->request->ip(),
        ]);
        Session::flash('success', 'Thanks! We received your message and will reply soon.');
        $this->redirect('/contact');
    }
}
