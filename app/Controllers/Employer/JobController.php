<?php
namespace App\Controllers\Employer;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\{Job, PlatformSetting};
use App\Services\{NotificationService, PrivateUploadService};
use App\Services\Jobs\JobCleaner;

/** A verified company's own jobs and the people who applied to them. */
class JobController extends Controller
{
    private const RULES = [
        'title' => 'required|max:250', 'category_id' => 'integer', 'state' => 'max:60', 'city' => 'max:100',
        'work_mode' => 'required|in:onsite,remote,hybrid,unspecified', 'employment_type' => 'required|in:full_time,part_time,contract,internship,temporary,volunteer',
        'experience_level' => 'required|in:entry,mid,senior,executive,unspecified', 'salary_min' => 'numeric', 'salary_max' => 'numeric', 'salary_currency' => 'in:NGN,USD,GBP,EUR',
        'summary' => 'required|max:600', 'description' => 'required|max:8000', 'skills_text' => 'max:300', 'deadline' => 'date',
    ];
    public const APP_STATUSES = ['submitted' => 'New', 'viewed' => 'Viewed', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'rejected' => 'Not selected', 'hired' => 'Hired'];

    private function emp(): array { return Auth::user('employer'); }

    private function verified(): array
    {
        $e = $this->emp();
        if ($e['verification_status'] !== 'verified') {
            Session::flash('error', 'Your company must be verified before you can post jobs.');
            $this->redirect('/employer/verification');
        }
        return $e;
    }

    private function needsApproval(): bool
    {
        try { return PlatformSetting::value('employer_jobs_need_approval', '0') === '1'; } catch (\Throwable $e) { return false; }
    }

    private function tz(): string
    {
        try { return (string) (PlatformSetting::value('default_timezone') ?: config('app.timezone', 'Africa/Lagos')); } catch (\Throwable $e) { return 'Africa/Lagos'; }
    }

    private function own(string $id): array
    {
        $j = Job::find((int) $id);
        if (!$j || (int) $j['employer_id'] !== (int) $this->emp()['id']) { $this->abort(404); }
        return $j;
    }

    private function formData(): array
    {
        return ['categories' => Database::all('SELECT id, name FROM job_categories ORDER BY display_order, name'), 'states' => JobCleaner::STATES];
    }

    public function index(): void
    {
        $e = $this->emp();
        $jobs = Database::all('SELECT j.*, (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id) AS applicants,
            (SELECT COUNT(*) FROM job_applications a WHERE a.job_id = j.id AND a.status = \'submitted\') AS fresh
            FROM jobs j WHERE j.employer_id = ? ORDER BY j.id DESC', [(int) $e['id']]);
        $this->view('employer/jobs', ['title' => 'My jobs', 'jobs' => $jobs, 'emp' => $e, 'approval' => $this->needsApproval()], 'employer');
    }

    public function create(): void
    {
        $this->verified();
        $this->view('employer/job-form', ['title' => 'Post a job', 'job' => [], 'action' => url('/employer/jobs'), 'approval' => $this->needsApproval()] + $this->formData(), 'employer');
    }

    private function row(array $d, array $emp): array
    {
        $skills = array_slice(array_values(array_filter(array_map(fn($s) => mb_substr(trim($s), 0, 40), explode(',', (string) $d['skills_text'])))), 0, 10);
        $deadline = null;
        if (!empty($d['deadline'])) {
            $deadline = (new \DateTimeImmutable($d['deadline'] . ' 23:59:59', new \DateTimeZone($this->tz())))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
        $state = in_array($d['state'], JobCleaner::STATES, true) ? $d['state'] : null;
        return [
            'title' => $d['title'], 'company' => $emp['company_name'], 'category_id' => $d['category_id'] ? (int) $d['category_id'] : null,
            'state' => $state, 'city' => $d['city'], 'location_text' => trim(($d['city'] ? $d['city'] . ', ' : '') . ($state ?? '')),
            'work_mode' => $d['work_mode'], 'employment_type' => $d['employment_type'], 'experience_level' => $d['experience_level'],
            'salary_min' => ($d['salary_min'] ?? '') !== '' ? $d['salary_min'] : null, 'salary_max' => ($d['salary_max'] ?? '') !== '' ? $d['salary_max'] : null,
            'salary_currency' => $d['salary_currency'] ?: 'NGN', 'summary' => $d['summary'], 'description' => $d['description'],
            'skills' => json_encode($skills), 'deadline_at' => $deadline, 'canonical_hash' => JobCleaner::canonical($d['title'], $emp['company_name'], $state),
        ];
    }

    private function checkSalary(array $d, string $back): void
    {
        if (($d['salary_min'] ?? '') !== '' && ($d['salary_max'] ?? '') !== '' && (float) $d['salary_min'] > (float) $d['salary_max']) {
            Session::flashInput($d); Session::flash('error', 'The minimum pay cannot be higher than the maximum.'); $this->redirect($back);
        }
    }

    public function store(): void
    {
        $emp = $this->verified();
        $d = $this->validate(self::RULES, '/employer/jobs/create');
        $this->checkSalary($d, '/employer/jobs/create');
        $slug = Job::uniqueSlug($d['title'], $emp['company_name']);
        $status = $this->needsApproval() ? 'pending' : 'published';
        $id = Job::create($this->row($d, $emp) + [
            'employer_id' => (int) $emp['id'], 'slug' => $slug, 'source_url' => url('/jobs/' . $slug), 'source_name' => $emp['company_name'],
            'status' => $status, 'published_at' => $status === 'published' ? utc_now() : null, 'posted_at' => utc_now(), 'last_seen_at' => utc_now(),
            'ai_status' => 'skipped', 'scam_risk' => 0,
        ]);
        Session::flash('success', $status === 'published' ? 'Your job is live. Students can now apply.' : 'Your job was saved and is waiting for a quick review by our team. It goes live once approved.');
        $this->redirect('/employer/jobs');
    }

    public function edit(string $id): void
    {
        $this->verified();
        $j = $this->own($id);
        $j['skills_text'] = implode(', ', (array) json_decode((string) $j['skills'], true));
        $j['deadline'] = $j['deadline_at'] ? local_date($j['deadline_at'], $this->tz(), 'Y-m-d') : '';
        $this->view('employer/job-form', ['title' => 'Edit job', 'job' => $j, 'action' => url('/employer/jobs/' . (int) $j['id']), 'approval' => $this->needsApproval()] + $this->formData(), 'employer');
    }

    public function update(string $id): void
    {
        $emp = $this->verified();
        $j = $this->own($id);
        $d = $this->validate(self::RULES, '/employer/jobs/' . (int) $j['id'] . '/edit');
        $this->checkSalary($d, '/employer/jobs/' . (int) $j['id'] . '/edit');
        $row = $this->row($d, $emp);
        if ($j['status'] === 'published' && $this->needsApproval()) { $row['status'] = 'pending'; } // edits are re-checked when approval is on
        Job::update((int) $j['id'], $row);
        Session::flash('success', ($row['status'] ?? '') === 'pending' ? 'Saved. Because of your changes the job is being reviewed again.' : 'Job updated.');
        $this->redirect('/employer/jobs');
    }

    /** Close a job (hide it from students) or open it again. */
    public function toggle(string $id): void
    {
        $emp = $this->verified();
        $j = $this->own($id);
        if ($j['status'] === 'published') { Job::update((int) $j['id'], ['status' => 'hidden']); Session::flash('success', 'Job closed. Students can no longer see or apply to it.'); }
        elseif ($j['status'] === 'hidden') {
            $new = $this->needsApproval() ? 'pending' : 'published';
            Job::update((int) $j['id'], ['status' => $new, 'published_at' => $j['published_at'] ?: utc_now()]);
            Session::flash('success', $new === 'published' ? 'Job is live again.' : 'Job sent for review.');
        } else { Session::flash('error', 'This job cannot be changed right now.'); }
        $this->redirect('/employer/jobs');
    }

    public function applicants(string $id): void
    {
        $j = $this->own($id);
        $apps = Database::all('SELECT a.*, u.first_name, u.last_name, u.email, u.phone FROM job_applications a JOIN users u ON u.id = a.user_id WHERE a.job_id = ? ORDER BY a.created_at DESC', [(int) $j['id']]);
        $this->view('employer/applicants', ['title' => 'Applicants', 'job' => $j, 'apps' => $apps, 'statuses' => self::APP_STATUSES], 'employer');
    }

    private function ownApplication(string $id): array
    {
        $a = Database::one('SELECT a.*, j.title AS job_title, j.employer_id, j.company FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ?', [(int) $id]);
        if (!$a || (int) $a['employer_id'] !== (int) $this->emp()['id']) { $this->abort(404); }
        return $a;
    }

    public function applicationStatus(string $id): void
    {
        $a = $this->ownApplication($id);
        $new = (string) $this->request->post('status');
        if (!isset(self::APP_STATUSES[$new])) { $this->abort(422); }
        $note = mb_substr(trim((string) $this->request->post('employer_note', '')), 0, 1000);
        Database::execute('UPDATE job_applications SET status = ?, employer_note = ? WHERE id = ?', [$new, $note !== '' ? $note : null, (int) $a['id']]);
        $msgs = [
            'shortlisted' => ['You were shortlisted', 'Good news: ' . $a['company'] . ' shortlisted you for "' . $a['job_title'] . '".'],
            'interview' => ['You have an interview invitation', $a['company'] . ' would like to interview you for "' . $a['job_title'] . '".'],
            'rejected' => ['Update on your application', $a['company'] . ' has decided not to move forward with your application for "' . $a['job_title'] . '" this time. Keep going. More jobs are waiting for you.'],
            'hired' => ['Congratulations, you got the job!', $a['company'] . ' selected you for "' . $a['job_title'] . '". Well done!'],
        ];
        if (isset($msgs[$new]) && $new !== $a['status']) {
            (new NotificationService())->toStudent((int) $a['user_id'], 'application_update', $msgs[$new][0], $msgs[$new][1] . ($note !== '' ? ' Message from the employer: ' . $note : ''), 'job_application', (int) $a['id'], '/student/applications');
        }
        Session::flash('success', 'Application updated.');
        $this->redirect('/employer/jobs/' . (int) $a['job_id'] . '/applicants');
    }

    public function cv(string $id): void
    {
        $a = $this->ownApplication($id);
        if (empty($a['cv_stored_name'])) { $this->abort(404); }
        $mime = str_ends_with($a['cv_stored_name'], '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        (new PrivateUploadService())->stream('applications', $a['cv_stored_name'], $a['cv_original_name'] ?: 'cv', $mime);
    }
}
