<?php
namespace App\Controllers\Instructor;

use App\Core\{Auth, Controller, Session};
use App\Models\Instructor;

class ProfileController extends Controller
{
    public function show(): void
    {
        $this->view('instructor/profile', ['title' => 'My profile', 'me' => Auth::user('instructor')], 'instructor');
    }

    public function update(): void
    {
        $d = $this->validate(['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'phone' => 'phone', 'biography' => 'max:2000'], '/instructor/profile');
        Instructor::update((int) Auth::id('instructor'), $d);
        Session::flash('success', 'Profile updated.');
        $this->redirect('/instructor/profile');
    }

    public function changePassword(): void
    {
        $min = (int) config('security.password_min_length', 8);
        $d = $this->validate(['current_password' => 'required', 'password' => "required|min:{$min}", 'password_confirmation' => 'required|same:password'], '/instructor/profile');
        $me = Auth::user('instructor');
        if (!password_verify($d['current_password'], (string) $me['password_hash'])) {
            Session::flashInput([], ['current_password' => 'Current password is incorrect.']);
            $this->redirect('/instructor/profile');
        }
        Instructor::update((int) $me['id'], ['password_hash' => password_hash($d['password'], PASSWORD_DEFAULT)]);
        Session::flash('success', 'Password changed.');
        $this->redirect('/instructor/profile');
    }
}
