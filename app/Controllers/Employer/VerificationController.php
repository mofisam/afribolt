<?php
namespace App\Controllers\Employer;

use App\Core\{Auth, Controller, Database, Session};
use App\Models\Employer;
use App\Services\Jobs\JobCleaner;
use App\Services\PrivateUploadService;

/** Company profile + verification documents. Documents are private: only the admin team can open them. */
class VerificationController extends Controller
{
    public const DOC_LABELS = [
        'cac_certificate' => 'CAC certificate or business registration (required)',
        'director_id' => 'ID of a director or the authorised person (required)',
        'tax_id' => 'Tax ID (TIN) certificate',
        'proof_of_address' => 'Proof of business address (utility bill, lease)',
        'other' => 'Other supporting document',
    ];
    public const SIZES = ['1-10', '11-50', '51-200', '201-1000', '1000+'];
    private const MAX_DOCS = 12;

    private function editable(array $emp): bool { return in_array($emp['verification_status'], ['unverified', 'rejected'], true); }

    public function show(): void
    {
        $emp = Auth::user('employer');
        $docs = Database::all('SELECT * FROM employer_documents WHERE employer_id = ? ORDER BY id', [(int) $emp['id']]);
        $have = array_column($docs, 'doc_type');
        $this->view('employer/verification', [
            'title' => 'Verification', 'emp' => $emp, 'docs' => $docs, 'editable' => $this->editable($emp),
            'missing' => array_values(array_diff(['cac_certificate', 'director_id'], $have)),
            'labels' => self::DOC_LABELS, 'sizes' => self::SIZES, 'states' => JobCleaner::STATES,
        ], 'employer');
    }

    public function saveProfile(): void
    {
        $emp = Auth::user('employer');
        $d = $this->validate([
            'company_name' => 'required|max:200', 'phone' => 'required|phone', 'rc_number' => 'max:40', 'website' => 'url|max:300', 'industry' => 'max:120',
            'company_size' => 'max:20', 'address' => 'max:300', 'state' => 'max:60', 'description' => 'max:1500',
        ], '/employer/verification');
        $row = [
            'phone' => $d['phone'], 'website' => $d['website'], 'industry' => $d['industry'], 'address' => $d['address'], 'description' => $d['description'],
            'company_size' => in_array($d['company_size'], self::SIZES, true) ? $d['company_size'] : null,
            'state' => in_array($d['state'], JobCleaner::STATES, true) ? $d['state'] : null,
        ];
        if ($this->editable($emp)) { $row['company_name'] = $d['company_name']; $row['rc_number'] = strtoupper($d['rc_number'] ?? ''); } // locked once submitted or verified
        Employer::update((int) $emp['id'], $row);
        Session::flash('success', 'Company profile saved.');
        $this->redirect('/employer/verification');
    }

    public function upload(): void
    {
        $emp = Auth::user('employer');
        if (!$this->editable($emp)) { Session::flash('error', 'Your documents are already with our team. You cannot change them right now.'); $this->redirect('/employer/verification'); }
        $type = (string) $this->request->post('doc_type');
        if (!isset(self::DOC_LABELS[$type])) { Session::flash('error', 'Choose what kind of document this is.'); $this->redirect('/employer/verification'); }
        if ((int) Database::value('SELECT COUNT(*) FROM employer_documents WHERE employer_id = ?', [(int) $emp['id']]) >= self::MAX_DOCS) {
            Session::flash('error', 'You have uploaded the maximum number of documents.'); $this->redirect('/employer/verification');
        }
        $r = (new PrivateUploadService())->save($this->request->file('document'), 'employer_docs', PrivateUploadService::DOC_TYPES, 5 * 1048576);
        if (is_string($r)) { Session::flash('error', $r); $this->redirect('/employer/verification'); }
        Database::execute('INSERT INTO employer_documents (employer_id, doc_type, original_name, stored_name, mime_type, size_bytes) VALUES (?,?,?,?,?,?)',
            [(int) $emp['id'], $type, $r['original'], $r['stored'], $r['mime'], $r['size']]);
        Session::flash('success', 'Document uploaded.');
        $this->redirect('/employer/verification');
    }

    public function deleteDocument(string $id): void
    {
        $emp = Auth::user('employer');
        $doc = Database::one('SELECT * FROM employer_documents WHERE id = ? AND employer_id = ?', [(int) $id, (int) $emp['id']]) ?? $this->abort(404);
        if (!$this->editable($emp)) { Session::flash('error', 'You cannot remove documents while they are being reviewed.'); $this->redirect('/employer/verification'); }
        (new PrivateUploadService())->delete('employer_docs', $doc['stored_name']);
        Database::execute('DELETE FROM employer_documents WHERE id = ?', [(int) $doc['id']]);
        Session::flash('success', 'Document removed.');
        $this->redirect('/employer/verification');
    }

    public function submit(): void
    {
        $emp = Auth::user('employer');
        if (!$this->editable($emp)) { $this->redirect('/employer/verification'); }
        $have = array_column(Database::all('SELECT doc_type FROM employer_documents WHERE employer_id = ?', [(int) $emp['id']]), 'doc_type');
        $problems = [];
        if (trim((string) $emp['rc_number']) === '') { $problems[] = 'your CAC / RC number'; }
        if (trim((string) $emp['address']) === '') { $problems[] = 'your business address'; }
        if (trim((string) $emp['description']) === '') { $problems[] = 'a short description of the company'; }
        if (!in_array('cac_certificate', $have, true)) { $problems[] = 'your CAC certificate or business registration'; }
        if (!in_array('director_id', $have, true)) { $problems[] = 'an ID of a director or authorised person'; }
        if ($problems) { Session::flash('error', 'Before you submit, please add: ' . implode(', ', $problems) . '.'); $this->redirect('/employer/verification'); }
        Employer::update((int) $emp['id'], ['verification_status' => 'pending_review', 'submitted_at' => utc_now(), 'review_note' => null]);
        Session::flash('success', 'Submitted. Our team will review your company and email you the result.');
        $this->redirect('/employer/verification');
    }
}
