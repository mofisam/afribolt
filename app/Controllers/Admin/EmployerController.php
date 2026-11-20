<?php
namespace App\Controllers\Admin;

use App\Core\Database;
use App\Models\{Employer, PlatformSetting};
use App\Services\{MailService, PrivateUploadService};
use App\Controllers\Employer\VerificationController;

/** Verify companies (read their documents, approve / ask for changes / reject) and control who can post jobs. */
class EmployerController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const VSTATUS = ['pending_review' => 'Waiting for review', 'unverified' => 'Not submitted', 'verified' => 'Verified', 'rejected' => 'Not approved'];

    private function needsApproval(): bool
    {
        try { return PlatformSetting::value('employer_jobs_need_approval', '0') === '1'; } catch (\Throwable $e) { return false; }
    }

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = '(e.company_name LIKE ? OR e.email LIKE ? OR e.rc_number LIKE ?)'; array_push($p, "%{$q}%", "%{$q}%", "%{$q}%"); }
        if (($s = (string) $this->request->query('status', '')) !== '' && isset(self::VSTATUS[$s])) { $where[] = 'e.verification_status = ?'; $p[] = $s; }
        [$rows, $pager] = $this->paged('employers e WHERE ' . implode(' AND ', $where), $p,
            'e.*, (SELECT COUNT(*) FROM employer_documents d WHERE d.employer_id = e.id) AS docs, (SELECT COUNT(*) FROM jobs j WHERE j.employer_id = e.id) AS jobs',
            "FIELD(e.verification_status,'pending_review','unverified','rejected','verified'), e.id DESC", 25);
        $waiting = (int) Database::value("SELECT COUNT(*) FROM employers WHERE verification_status = 'pending_review'");

        $intro = '<div class="flex flex-wrap items-center gap-4 bg-white border rounded-lg p-4 text-sm"><span><strong>' . $waiting . '</strong> company(ies) waiting for review</span>'
            . '<form method="post" action="' . e(url('/admin/employers/settings')) . '" class="flex items-center gap-2">' . csrf_field()
            . '<label class="flex items-center gap-2"><input type="checkbox" name="need_approval" value="1" ' . ($this->needsApproval() ? 'checked' : '') . '> Review every employer job before it goes live</label>'
            . '<button class="btn btn-ghost !py-1.5" type="submit">Save</button></form>'
            . '<span class="text-slate-500">Off = verified companies publish instantly (you can still hide any job).</span></div>';

        $this->page('admin/crud/index', [
            'title' => 'Employers', 'rows' => $rows, 'pager' => $pager, 'intro' => $intro,
            'filters' => [['name' => 'q', 'label' => 'Company, email or RC number'], ['name' => 'status', 'label' => 'Verification', 'type' => 'select', 'options' => self::VSTATUS]],
            'columns' => [
                ['label' => 'Company', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['company_name']) . '</strong><br><span class="text-slate-500">' . e($r['rc_number'] ?: 'No RC number') . '</span>'],
                ['label' => 'Contact', 'html' => true, 'render' => fn($r) => e($r['contact_first_name'] . ' ' . $r['contact_last_name']) . '<br><a class="underline" href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a>'],
                ['label' => 'Verification', 'html' => true, 'render' => fn($r) => $this->badge(self::VSTATUS[$r['verification_status']], $r['verification_status'] === 'verified' ? 'badge-open' : ($r['verification_status'] === 'rejected' ? 'badge-closed' : ''))],
                ['label' => 'Docs', 'render' => fn($r) => (string) $r['docs']],
                ['label' => 'Jobs', 'render' => fn($r) => (string) $r['jobs']],
                ['label' => 'Account', 'html' => true, 'render' => fn($r) => $this->badge($r['account_status'])],
                ['label' => 'Submitted', 'render' => fn($r) => $r['submitted_at'] ? local_date($r['submitted_at'], null, 'j M Y') : ''],
            ],
            'actions' => function ($r) {
                $a = [['label' => $r['verification_status'] === 'pending_review' ? 'Review' : 'View', 'url' => url('/admin/employers/' . $r['id'])]];
                $a[] = $r['account_status'] === 'active'
                    ? ['label' => 'Suspend', 'url' => url('/admin/employers/' . $r['id'] . '/suspend'), 'post' => true, 'confirm' => 'Suspend this company? They will be logged out and all their live jobs will be hidden.']
                    : ['label' => 'Reactivate', 'url' => url('/admin/employers/' . $r['id'] . '/reactivate'), 'post' => true];
                $a[] = ['label' => 'Delete', 'icon' => 'delete', 'danger' => true, 'url' => url('/admin/employers/' . $r['id'] . '/delete'), 'post' => true, 'confirm' => 'Delete this company, ALL its jobs, their applications, and its uploaded documents? This cannot be undone. Suspend it instead if you only want to stop it posting.'];
                return $a;
            },
        ]);
    }

    public function settings(): void
    {
        $val = $this->request->post('need_approval') === '1' ? '1' : '0';
        $row = PlatformSetting::findBy('setting_key', 'employer_jobs_need_approval');
        if ($row) { PlatformSetting::update((int) $row['id'], ['setting_value' => $val, 'updated_by_admin_id' => $this->adminId()]); }
        else { PlatformSetting::create(['setting_key' => 'employer_jobs_need_approval', 'setting_value' => $val, 'value_type' => 'string', 'description' => 'Review employer-posted jobs before they go live (1 = yes)', 'is_public' => 0, 'updated_by_admin_id' => $this->adminId()]); }
        $this->audit('employers.settings', 'platform_setting', null, null, ['need_approval' => $val]);
        $this->done($val === '1' ? 'Employer jobs now wait for your review before going live.' : 'Verified employers now publish jobs instantly.', '/admin/employers');
    }

    public function show(string $id): void
    {
        $e = Employer::find((int) $id) ?? $this->abort(404);
        $this->page('admin/employers/review', [
            'title' => $e['company_name'], 'e' => $e,
            'docs' => Database::all('SELECT * FROM employer_documents WHERE employer_id = ? ORDER BY id', [(int) $e['id']]),
            'jobs' => Database::all('SELECT id, title, status, published_at FROM jobs WHERE employer_id = ? ORDER BY id DESC LIMIT 10', [(int) $e['id']]),
            'labels' => VerificationController::DOC_LABELS, 'vlabel' => self::VSTATUS,
        ]);
    }

    /** Admins open private documents through here (never a public URL). */
    public function document(string $docId): void
    {
        $d = Database::one('SELECT * FROM employer_documents WHERE id = ?', [(int) $docId]) ?? $this->abort(404);
        $this->audit('employer.document_viewed', 'employer_document', (int) $d['id'], null, ['employer_id' => (int) $d['employer_id']]);
        (new PrivateUploadService())->stream('employer_docs', $d['stored_name'], $d['original_name'], $d['mime_type']);
    }

    public function decision(string $id): void
    {
        $e = Employer::find((int) $id) ?? $this->abort(404);
        $dec = (string) $this->request->post('decision');
        $note = mb_substr(trim((string) $this->request->post('note', '')), 0, 1000);
        $back = '/admin/employers/' . (int) $e['id'];
        if (!in_array($dec, ['approve', 'changes', 'reject'], true)) { $this->abort(422); }
        if ($dec !== 'approve' && $note === '') { $this->done('Please write a note so the company knows what to fix.', $back, 'error'); }

        $upd = ['reviewed_by_admin_id' => $this->adminId(), 'review_note' => $note !== '' ? $note : null];
        if ($dec === 'approve') { $upd += ['verification_status' => 'verified', 'verified_at' => utc_now()]; }
        elseif ($dec === 'changes') { $upd += ['verification_status' => 'unverified']; }
        else { $upd += ['verification_status' => 'rejected']; }
        Employer::update((int) $e['id'], $upd);
        $this->audit('employer.' . $dec, 'employer', (int) $e['id'], ['status' => $e['verification_status']], ['status' => $upd['verification_status']]);

        try {
            (new MailService())->queue(null, $e['email'], 'employer-verification-result',
                $dec === 'approve' ? 'Your company is verified on Afribolt' : 'Update on your Afribolt company verification',
                ['name' => $e['contact_first_name'], 'company' => $e['company_name'], 'decision' => $dec === 'approve' ? 'approved' : ($dec === 'changes' ? 'changes' : 'rejected'), 'note' => $note]);
        } catch (\Throwable $ex) { error_log('Employer decision email not queued: ' . $ex->getMessage()); }
        $this->done($e['company_name'] . ': ' . ['approve' => 'approved and emailed.', 'changes' => 'sent back for changes.', 'reject' => 'rejected and emailed.'][$dec], '/admin/employers');
    }

    public function suspend(string $id): void
    {
        $e = Employer::find((int) $id) ?? $this->abort(404);
        Employer::update((int) $e['id'], ['account_status' => 'suspended']);
        $n = Database::execute("UPDATE jobs SET status = 'hidden' WHERE employer_id = ? AND status IN ('published','pending')", [(int) $e['id']]);
        $this->audit('employer.suspended', 'employer', (int) $e['id'], null, ['jobs_hidden' => $n]);
        $this->done($e['company_name'] . ' suspended. ' . $n . ' job(s) hidden.', '/admin/employers');
    }

    public function reactivate(string $id): void
    {
        $e = Employer::find((int) $id) ?? $this->abort(404);
        Employer::update((int) $e['id'], ['account_status' => 'active']);
        $this->audit('employer.reactivated', 'employer', (int) $e['id'], null, []);
        $this->done($e['company_name'] . ' reactivated. Their hidden jobs stay hidden until they reopen them.', '/admin/employers');
    }

    public function delete(string $id): void
    {
        $e = Employer::find((int) $id) ?? $this->abort(404);
        $docs = array_column(Database::all('SELECT stored_name FROM employer_documents WHERE employer_id = ?', [(int) $e['id']]), 'stored_name');
        $cvs = array_column(Database::all('SELECT a.cv_stored_name FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE j.employer_id = ? AND a.cv_stored_name IS NOT NULL', [(int) $e['id']]), 'cv_stored_name');
        $this->audit('employer.deleted', 'employer', (int) $e['id'], null, ['company' => $e['company_name'], 'email' => $e['email']]);
        $this->tryDelete(function () use ($e, $docs, $cvs) {
            Database::transaction(function () use ($e) {
                Database::execute('DELETE FROM jobs WHERE employer_id = ?', [(int) $e['id']]); // applications go with the jobs
                Database::execute('DELETE FROM employers WHERE id = ?', [(int) $e['id']]);     // documents rows go with the company
            });
            $files = new PrivateUploadService();
            foreach ($docs as $f) { $files->delete('employer_docs', $f); }
            foreach ($cvs as $f) { $files->delete('applications', $f); }
        }, 'Company deleted.', '/admin/employers', 'Suspend it instead.');
    }
}
