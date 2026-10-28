<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Session};
use App\Models\User;

class ProfileController extends Controller
{
    public function show(): void
    {
        $this->view('student/profile', ['title' => 'My profile', 'student' => Auth::user('student')], 'student');
    }

    public function update(): void
    {
        $data = $this->validate(['first_name' => 'required|max:100', 'last_name' => 'required|max:100', 'phone' => 'phone'], '/student/profile');
        User::update((int) Auth::id('student'), $data);
        Session::flash('success', 'Profile updated.');
        $this->redirect('/student/profile');
    }

    public function changePassword(): void
    {
        $min = (int) config('security.password_min_length', 8);
        $data = $this->validate(['current_password' => 'required', 'password' => "required|min:{$min}", 'password_confirmation' => 'required|same:password'], '/student/profile');
        $user = Auth::user('student');
        if (!password_verify($data['current_password'], $user['password_hash'])) {
            Session::flashInput([], ['current_password' => 'Current password is incorrect.']);
            $this->redirect('/student/profile');
        }
        User::update((int) $user['id'], ['password_hash' => password_hash($data['password'], PASSWORD_DEFAULT)]);
        Session::flash('success', 'Password changed.');
        $this->redirect('/student/profile');
    }
}
