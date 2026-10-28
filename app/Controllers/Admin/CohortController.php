<?php
namespace App\Controllers\Admin;

use App\Core\{Database, Session};
use App\Models\{Cohort, CohortInstructor, Course, Instructor};
use App\Services\NotificationService;

class CohortController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const STATUSES = ['draft' => 'Draft (hidden)', 'published' => 'Published', 'open' => 'Open for registration', 'closed' => 'Closed',
        'in_progress' => 'In progress', 'postponed' => 'Postponed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];

    private function courseOptions(): array
    {
        $o = [];
        foreach (Database::all("SELECT id, title, status FROM courses WHERE deleted_at IS NULL AND status <> 'archived' ORDER BY title") as $c) {
            $o[$c['id']] = $c['title'] . ($c['status'] === 'draft' ? ' (draft)' : '');
        }
        return $o;
    }

    private function fields(): array
    {
        return [
            ['type' => 'heading', 'label' => 'Basics'],
            ['name' => 'course_id', 'label' => 'Course', 'type' => 'select', 'options' => $this->courseOptions(), 'required' => true],
            ['name' => 'cohort_name', 'label' => 'Cohort name', 'required' => true, 'half' => true, 'help' => 'e.g. November 2026 Cohort'],
            ['name' => 'cohort_code', 'label' => 'Cohort code', 'half' => true, 'help' => 'Used in the public link. Blank = generated.'],
            ['name' => 'summary', 'label' => 'Summary shown to students', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES, 'default' => 'draft',
                'help' => 'Students can register while the status is Published or Open (and the registration rules below allow it).'],
            ['type' => 'heading', 'label' => 'Schedule'],
            ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'options' => $this->timezones(), 'default' => $this->defaultTz(), 'help' => 'The dates below are entered in this timezone.'],
            ['name' => 'start_at', 'label' => 'Programme starts', 'type' => 'datetime', 'required' => true, 'half' => true],
            ['name' => 'end_at', 'label' => 'Programme ends', 'type' => 'datetime', 'half' => true],
            ['type' => 'heading', 'label' => 'Registration'],
            ['name' => 'registration_enabled', 'label' => 'Registration enabled', 'type' => 'checkbox', 'default' => 1, 'help' => 'Master switch. Turn off to pause sign-ups instantly.'],
            ['name' => 'registration_opens_at', 'label' => 'Registration opens', 'type' => 'datetime', 'half' => true],
            ['name' => 'registration_closes_at', 'label' => 'Registration closes', 'type' => 'datetime', 'half' => true],
            ['name' => 'allow_registration_after_start', 'label' => 'Allow late registration after the start date', 'type' => 'checkbox'],
            ['name' => 'capacity', 'label' => 'Capacity (seats)', 'type' => 'number', 'min' => 0, 'half' => true, 'help' => 'Blank = unlimited.'],
            ['name' => 'waitlist_enabled', 'label' => 'Enable waitlist when full', 'type' => 'checkbox'],
            ['name' => 'registration_instructions', 'label' => 'Instructions shown to registrants', 'type' => 'textarea', 'rows' => 3],
            ['type' => 'heading', 'label' => 'Fees'],
            ['name' => 'fee_amount', 'label' => 'Fee', 'type' => 'number', 'step' => '0.01', 'min' => 0, 'required' => true, 'half' => true, 'default' => '0'],
            ['name' => 'currency', 'label' => 'Currency (3 letters)', 'half' => true, 'default' => $this->defaultCurrency()],
            ['name' => 'require_payment', 'label' => 'Payment required to confirm a seat', 'type' => 'checkbox', 'default' => 1],
            ['type' => 'heading', 'label' => 'Meetings'],
            ['name' => 'meeting_provider_default', 'label' => 'Default meeting platform', 'type' => 'select', 'default' => 'none',
                'options' => ['none' => 'Not set', 'google_meet' => 'Google Meet', 'zoom' => 'Zoom', 'microsoft_teams' => 'Microsoft Teams', 'other' => 'Other']],
        ];
    }

    private function rules(): array
    {
        return [
            'course_id' => 'required|integer', 'cohort_name' => 'required|max:180', 'cohort_code' => 'max:80', 'summary' => 'max:3000',
            'status' => 'required|in:' . implode(',', array_keys(self::STATUSES)), 'timezone' => 'required|in:' . implode(',', array_keys($this->timezones())),
            'start_at' => 'required|date', 'end_at' => 'date', 'registration_opens_at' => 'date', 'registration_closes_at' => 'date',
            'capacity' => 'integer', 'fee_amount' => 'required|numeric', 'currency' => 'required|min:3|max:3', 'registration_instructions' => 'max:3000',
            'meeting_provider_default' => 'required|in:none,google_meet,zoom,microsoft_teams,other',
        ];
    }

    /** Build the DB row, checking cross-field rules. Redirects back with errors on failure. */
    private function rowFrom(array $d, ?array $existing, string $back): array
    {
        $tz = $d['timezone'];
        $start = $this->fromInput($d['start_at'], $tz);
        $end = $this->fromInput($d['end_at'], $tz);
        $opens = $this->fromInput($d['registration_opens_at'], $tz);
        $closes = $this->fromInput($d['registration_closes_at'], $tz);

        $errors = [];
        if ($end && $end <= $start) { $errors['end_at'] = 'End must be after the start.'; }
        if ($opens && $closes && $closes < $opens) { $errors['registration_closes_at'] = 'Closing date must be after the opening date.'; }
        if ((float) $d['fee_amount'] < 0) { $errors['fee_amount'] = 'Fee cannot be negative.'; }
        if ($existing && $d['capacity'] !== null && (int) $d['capacity'] < Cohort::seatsTaken((int) $existing['id'])) {
            $errors['capacity'] = 'Capacity is below the ' . Cohort::seatsTaken((int) $existing['id']) . ' seats already taken.';
        }
        if (!Course::find((int) $d['course_id'])) { $errors['course_id'] = 'Choose a valid course.'; }
        if ($errors) {
            Session::flashInput($this->request->all(), $errors);
            Session::flash('error', 'Please fix the highlighted fields.');
            $this->redirect($back);
        }

        $code = trim((string) $d['cohort_code']);
        if ($code === '') {
            $course = Course::find((int) $d['course_id']);
            $code = substr($course['slug'], 0, 24) . '-' . local_date($start, $tz, 'ym');
        }
        $row = [
            'course_id' => (int) $d['course_id'], 'cohort_name' => $d['cohort_name'],
            'cohort_code' => strtoupper($this->uniqueSlug('cohorts', $code, $existing ? (int) $existing['id'] : null, 'cohort_code')),
            'summary' => $d['summary'], 'start_at' => $start, 'end_at' => $end, 'timezone' => $tz,
            'registration_opens_at' => $opens, 'registration_closes_at' => $closes,
            'allow_registration_after_start' => $this->checkbox('allow_registration_after_start'),
            'registration_enabled' => $this->checkbox('registration_enabled'),
            'capacity' => $d['capacity'] !== null ? (int) $d['capacity'] : null, 'waitlist_enabled' => $this->checkbox('waitlist_enabled'),
            'fee_amount' => round((float) $d['fee_amount'], 2), 'currency' => strtoupper($d['currency']), 'require_payment' => $this->checkbox('require_payment'),
            'status' => $d['status'], 'meeting_provider_default' => $d['meeting_provider_default'], 'registration_instructions' => $d['registration_instructions'],
        ];
        if (in_array($d['status'], ['published', 'open'], true) && empty($existing['published_at'])) { $row['published_at'] = utc_now(); }
        return $row;
    }

    public function index(): void
    {
        $where = ['h.deleted_at IS NULL']; $p = [];
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = '(h.cohort_name LIKE ? OR h.cohort_code LIKE ?)'; $p[] = "%{$q}%"; $p[] = "%{$q}%"; }
        if ($s = $this->request->query('status')) { $where[] = 'h.status = ?'; $p[] = $s; }
        if ($c = $this->request->query('course')) { $where[] = 'h.course_id = ?'; $p[] = (int) $c; }
        [$rows, $pager] = $this->paged(
            'cohorts h JOIN courses c ON c.id = h.course_id WHERE ' . implode(' AND ', $where), $p,
            "h.*, c.title AS course_title, (SELECT COUNT(*) FROM enrolments e WHERE e.cohort_id = h.id AND e.status IN ('pending_payment','pending_review','confirmed')) AS seats_taken",
            'h.start_at DESC'
        );
        $this->page('admin/crud/index', [
            'title' => 'Cohorts', 'createUrl' => url('/admin/cohorts/create'), 'createLabel' => 'New cohort', 'rows' => $rows, 'pager' => $pager,
            'filters' => [
                ['name' => 'q', 'label' => 'Search'],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES],
                ['name' => 'course', 'label' => 'Course', 'type' => 'select', 'options' => $this->courseOptions()],
            ],
            'columns' => [
                ['label' => 'Cohort', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['cohort_name']) . '</strong><br><span class="text-slate-500">' . e($r['cohort_code']) . '</span>'],
                ['label' => 'Course', 'render' => fn($r) => $r['course_title']],
                ['label' => 'Starts', 'render' => fn($r) => local_date($r['start_at'], $r['timezone'], 'j M Y, g:i A')],
                ['label' => 'Seats', 'render' => fn($r) => $r['seats_taken'] . ' / ' . ($r['capacity'] ?? '∞')],
                ['label' => 'Fee', 'render' => fn($r) => money($r['fee_amount'], $r['currency'])],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'])],
            ],
            'actions' => fn($r) => [
                ['label' => 'Manage', 'url' => url('/admin/cohorts/' . $r['id'])],
                ['label' => 'Edit', 'url' => url('/admin/cohorts/' . $r['id'] . '/edit')],
            ],
        ]);
    }

    public function create(): void
    {
        $this->page('admin/crud/form', ['title' => 'New cohort', 'action' => url('/admin/cohorts'), 'fields' => $this->fields(), 'values' => [], 'cancelUrl' => url('/admin/cohorts')]);
    }

    public function store(): void
    {
        $d = $this->validate($this->rules(), '/admin/cohorts/create');
        $row = $this->rowFrom($d, null, '/admin/cohorts/create') + ['created_by_admin_id' => $this->adminId()];
        $id = Cohort::create($row);
        $this->audit('cohort.created', 'cohort', $id, null, $row);
        $this->done('Cohort created. Now add its class sessions.', '/admin/cohorts/' . $id);
    }

    public function edit(string $id): void
    {
        $c = Cohort::find((int) $id) ?? $this->abort(404);
        foreach (['start_at', 'end_at', 'registration_opens_at', 'registration_closes_at'] as $k) { $c[$k] = $this->toInput($c[$k], $c['timezone']); }
        $this->page('admin/crud/form', [
            'title' => 'Edit cohort', 'action' => url('/admin/cohorts/' . $c['id']), 'fields' => $this->fields(), 'values' => $c, 'cancelUrl' => url('/admin/cohorts/' . $c['id']),
            'intro' => 'Changes to fees, dates, status and registration settings are recorded in the audit log. Enrolled students are notified if the start date changes.',
        ]);
    }

    public function update(string $id): void
    {
        $c = Cohort::find((int) $id) ?? $this->abort(404);
        $d = $this->validate($this->rules(), '/admin/cohorts/' . $c['id'] . '/edit');
        $row = $this->rowFrom($d, $c, '/admin/cohorts/' . $c['id'] . '/edit');
        Cohort::update((int) $c['id'], $row);
        $this->audit('cohort.updated', 'cohort', (int) $c['id'], $c, $row);

        $notify = new NotificationService();
        if ($row['start_at'] !== $c['start_at']) {
            $notify->toCohort((int) $c['id'], 'cohort_rescheduled', 'Start date changed', "{$row['cohort_name']} now starts " . local_date($row['start_at'], $row['timezone']) . '.', '/student/dashboard');
        }
        if ($row['status'] !== $c['status'] && in_array($row['status'], ['postponed', 'cancelled'], true)) {
            $notify->toCohort((int) $c['id'], 'cohort_' . $row['status'], 'Cohort ' . $row['status'], "{$row['cohort_name']} has been {$row['status']}. We'll contact you with next steps.", '/student/dashboard');
        }
        $this->done('Cohort updated.', '/admin/cohorts/' . $c['id']);
    }

    public function show(string $id): void
    {
        $c = Database::one('SELECT h.*, co.title AS course_title, co.slug AS course_slug FROM cohorts h JOIN courses co ON co.id = h.course_id WHERE h.id = ? AND h.deleted_at IS NULL', [(int) $id]) ?? $this->abort(404);
        $counts = [];
        foreach (Database::all('SELECT status, COUNT(*) n FROM enrolments WHERE cohort_id = ? GROUP BY status', [(int) $c['id']]) as $r) { $counts[$r['status']] = (int) $r['n']; }
        $instructors = Database::all('SELECT ci.*, i.first_name, i.last_name, i.email FROM cohort_instructors ci JOIN instructors i ON i.id = ci.instructor_id WHERE ci.cohort_id = ?', [(int) $c['id']]);
        $assigned = array_column($instructors, 'instructor_id');
        $available = array_filter(Database::all("SELECT id, first_name, last_name FROM instructors WHERE account_status <> 'suspended' ORDER BY first_name"), fn($i) => !in_array($i['id'], $assigned));
        $this->page('admin/cohorts/show', [
            'title' => $c['cohort_name'], 'c' => $c, 'counts' => $counts, 'seatsTaken' => Cohort::seatsTaken((int) $c['id']),
            'sessions' => Database::all('SELECT * FROM class_sessions WHERE cohort_id = ? ORDER BY starts_at', [(int) $c['id']]),
            'instructors' => $instructors, 'available' => $available, 'statuses' => self::STATUSES,
            'revenue' => (float) Database::value("SELECT COALESCE(SUM(p.amount - p.refunded_amount),0) FROM payments p JOIN enrolments e ON e.id = p.enrolment_id WHERE e.cohort_id = ? AND p.status IN ('successful','partially_refunded')", [(int) $c['id']]),
        ]);
    }

    public function status(string $id): void
    {
        $c = Cohort::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!isset(self::STATUSES[$new])) { $this->abort(422); }
        $row = ['status' => $new];
        if (in_array($new, ['published', 'open'], true) && empty($c['published_at'])) { $row['published_at'] = utc_now(); }
        Cohort::update((int) $c['id'], $row);
        $this->audit('cohort.status_changed', 'cohort', (int) $c['id'], ['status' => $c['status']], $row);
        if (in_array($new, ['postponed', 'cancelled'], true)) {
            (new NotificationService())->toCohort((int) $c['id'], 'cohort_' . $new, 'Cohort ' . $new, "{$c['cohort_name']} has been {$new}. We'll contact you with next steps.", '/student/dashboard');
        }
        $this->done('Status set to ' . str_replace('_', ' ', $new) . '.', '/admin/cohorts/' . $c['id']);
    }

    public function assignInstructor(string $id): void
    {
        $c = Cohort::find((int) $id) ?? $this->abort(404);
        $iid = (int) $this->request->post('instructor_id');
        $role = $this->request->post('assignment_role') === 'assistant' ? 'assistant' : 'lead';
        if (!Instructor::find($iid)) { $this->done('Choose an instructor.', '/admin/cohorts/' . $c['id'], 'error'); }
        if (!Database::value('SELECT COUNT(*) FROM cohort_instructors WHERE cohort_id = ? AND instructor_id = ?', [(int) $c['id'], $iid])) {
            CohortInstructor::create(['cohort_id' => (int) $c['id'], 'instructor_id' => $iid, 'assignment_role' => $role]);
            $this->audit('cohort.instructor_assigned', 'cohort', (int) $c['id'], null, ['instructor_id' => $iid, 'role' => $role]);
        }
        $this->done('Instructor assigned.', '/admin/cohorts/' . $c['id']);
    }

    public function removeInstructor(string $id, string $instructorId): void
    {
        $c = Cohort::find((int) $id) ?? $this->abort(404);
        Database::execute('DELETE FROM cohort_instructors WHERE cohort_id = ? AND instructor_id = ?', [(int) $c['id'], (int) $instructorId]);
        $this->audit('cohort.instructor_removed', 'cohort', (int) $c['id'], ['instructor_id' => (int) $instructorId], []);
        $this->done('Instructor removed.', '/admin/cohorts/' . $c['id']);
    }
}
