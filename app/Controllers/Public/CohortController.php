<?php
namespace App\Controllers\Public;

use App\Core\{Auth, Controller};
use App\Models\{ClassSession, Cohort};
use App\Services\RegistrationPolicyService;

class CohortController extends Controller
{
    public function show(string $code): void
    {
        $cohort = Cohort::findPublicByCode($code) ?? $this->abort(404);
        $seats  = Cohort::seatsTaken((int) $cohort['id']);
        if (!Auth::check('student')) { \App\Core\Session::put('intended', '/cohorts/' . $code); }
        $policy = (new RegistrationPolicyService())->check($cohort, $seats, Auth::id('student'));

        $this->view('public/cohorts/show', [
            'title'     => $cohort['cohort_name'],
            'cohort'    => $cohort,
            'policy'    => $policy,
            'seatsLeft' => $cohort['capacity'] === null ? null : max(0, (int) $cohort['capacity'] - $seats),
            'sessions'  => ClassSession::forCohort((int) $cohort['id']), // meeting links are NOT included
        ]);
    }
}
