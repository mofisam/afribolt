<?php
namespace App\Controllers\Student;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\Job;
use App\Services\{MailService, NotificationService, PrivateUploadService};

/** Applying to jobs posted by verified companies on Afribolt, and following those applications. */
class ApplicationController extends Controller
{
    /** The job must be live, not past its deadline, and belong to an active, verified company. */
    private function job(string $slug): array
    {
        $job = Job::findPublishedBySlug($slug) ?? $this->abort(404);
        if (empty($job['employer_id'])) { $this->redirect('/jobs/' . $job['slug'] . '/apply'); } // collected jobs apply on the employer's own site
        $emp = Database::one("SELECT id, email, company_name FROM employers WHERE id = ? AND account_status = 'active' AND verification_status = 'verified'", [(int) $job['employer_id']]);
        if (!$emp) { $this->abort(404); }
        if (!empty($job['deadline_at']) && strtotime($job['deadline_at'] . ' UTC') <= time()) {
            Session::flash('error', 'Applications for this job have closed.');
            $this->redirect('/jobs/' . $job['slug']);
        }
        $job['_employer'] = $emp;
        return $job;
    }

    private function existing(int $jobId, int $uid): ?array
    {
        return Database::one('SELECT id FROM job_applications WHERE job_id = ? AND user_id = ?', [$jobId, $uid]);
    }

    public function form(string $slug): void
    {
        $job = $this->job($slug);
        if ($this->existing((int) $job['id'], (int) Auth::id('student'))) {
            Session::flash('success', 'You have already applied for this job. You can follow it here.');
            $this->redirect('/student/applications');
        }
        $this->view('student/apply', ['title' => 'Apply: ' . $job['title'], 'job' => $job, 'student' => Auth::user('student')], 'student');
    }

    public function submit(string $slug): void
    {
        $job = $this->job($slug);
        $uid = (int) Auth::id('student');
        $back = '/student/jobs/' . $job['slug'] . '/apply';
        if ($this->existing((int) $job['id'], $uid)) { $this->redirect('/student/applications'); }
        $d = $this->validate(['cover_note' => 'max:2000'], $back);

        $cv = null; $file = $this->request->file('cv');
        if ($file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $cv = (new PrivateUploadService())->save($file, 'applications', PrivateUploadService::CV_TYPES, 3 * 1048576);
            if (is_string($cv)) { Session::flashInput($d); Session::flash('error', 'Your CV: ' . $cv); $this->redirect($back); }
        }
        if (trim((string) $d['cover_note']) === '' && !$cv) {
            Session::flash('error', 'Please add a short note or upload your CV so the employer knows who you are.');
            $this->redirect($back);
        }
        Database::execute('INSERT INTO job_applications (job_id, user_id, cover_note, cv_original_name, cv_stored_name) VALUES (?,?,?,?,?)',
            [(int) $job['id'], $uid, trim((string) $d['cover_note']) ?: null, $cv['original'] ?? null, $cv['stored'] ?? null]);
        $appId = Database::insertId();
        $student = Auth::user('student');

        (new NotificationService())->toStudent($uid, 'application_sent', 'Application sent',
            'You applied for "' . $job['title'] . '" at ' . $job['company'] . '. We will notify you when the employer responds.', 'job_application', $appId, '/student/applications', false);
        try {
            (new MailService())->queue(null, $job['_employer']['email'], 'employer-new-application', 'New applicant for ' . $job['title'],
                ['company' => $job['_employer']['company_name'], 'jobTitle' => $job['title'], 'applicant' => trim($student['first_name'] . ' ' . $student['last_name']), 'link' => url('/employer/jobs/' . (int) $job['id'] . '/applicants')]);
        } catch (\Throwable $e) { error_log('Employer application email not queued: ' . $e->getMessage()); }

        Session::flash('success', 'Application sent! Good luck. We will let you know when the employer responds.');
        $this->redirect('/student/applications');
    }

    public function index(): void
    {
        $apps = Database::all('SELECT a.*, j.title, j.slug, j.company, j.location_text, j.status AS job_status FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE a.user_id = ? ORDER BY a.created_at DESC', [(int) Auth::id('student')]);
        $this->view('student/applications', ['title' => 'My applications', 'apps' => $apps], 'student');
    }
}
