<?php
namespace App\Controllers\Admin;

use App\Core\{Crypto, Database, Session};
use App\Models\{ClassSession, Cohort};
use App\Services\ScheduleService;

class SessionController extends BaseAdminController
{
    protected array $roles = ['admin', 'content_manager'];

    private const PROVIDERS = ['none' => 'Not set', 'google_meet' => 'Google Meet', 'zoom' => 'Zoom', 'microsoft_teams' => 'Microsoft Teams', 'other' => 'Other'];
    private const STATUSES = ['scheduled' => 'Scheduled', 'live' => 'Live', 'completed' => 'Completed', 'postponed' => 'Postponed', 'cancelled' => 'Cancelled'];

    private function cohortOptions(): array
    {
        $o = [];
        foreach (Database::all("SELECT id, cohort_name, cohort_code FROM cohorts WHERE deleted_at IS NULL AND status NOT IN ('completed','cancelled') ORDER BY start_at DESC") as $c) {
            $o[$c['id']] = $c['cohort_name'] . ' (' . $c['cohort_code'] . ')';
        }
        return $o;
    }

    private function fields(bool $edit, string $tzDefault): array
    {
        $f = [
            ['name' => 'cohort_id', 'label' => 'Cohort', 'type' => 'select', 'options' => $this->cohortOptions(), 'required' => true],
            ['name' => 'title', 'label' => 'Title', 'required' => true, 'half' => true],
            ['name' => 'session_number', 'label' => 'Class number', 'type' => 'number', 'min' => 1, 'half' => true],
            ['name' => 'description', 'label' => 'Topic / notes for students', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'timezone', 'label' => 'Timezone', 'type' => 'select', 'options' => $this->timezones(), 'default' => $tzDefault, 'help' => 'Times below are entered in this timezone.'],
            ['name' => 'starts_at', 'label' => 'Starts', 'type' => 'datetime', 'required' => true, 'half' => true],
            ['name' => 'ends_at', 'label' => 'Ends', 'type' => 'datetime', 'required' => true, 'half' => true],
        ];
        if (!$edit) {
            $f[] = ['name' => 'repeat_weeks', 'label' => 'Repeat weekly for how many classes?', 'type' => 'number', 'min' => 1, 'default' => 1,
                'help' => '1 = just this class. 8 creates 8 classes, one per week at the same time, numbered automatically.'];
        }
        $f = array_merge($f, [
            ['type' => 'heading', 'label' => 'Meeting'],
            ['name' => 'meeting_provider', 'label' => 'Platform', 'type' => 'select', 'options' => self::PROVIDERS, 'half' => true],
            ['name' => 'join_early_minutes', 'label' => 'Join button opens (minutes before start)', 'type' => 'number', 'min' => 0, 'default' => (int) config('security.join_early_minutes_default', 10), 'half' => true],
            ['name' => 'meeting_url', 'label' => 'Meeting link', 'type' => 'url', 'help' => 'Only released to confirmed students inside the join window.'],
            ['name' => 'meeting_id', 'label' => 'Meeting ID', 'half' => true],
            ['name' => 'meeting_passcode', 'label' => 'Passcode', 'half' => true, 'help' => $edit ? 'Leave blank to keep the current one. Stored encrypted.' : 'Stored encrypted.'],
            ['name' => 'recording_url', 'label' => 'Recording link (after class)', 'type' => 'url'],
        ]);
        if ($edit) { $f[] = ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES]; }
        return $f;
    }

    private function rules(bool $edit): array
    {
        return ['cohort_id' => 'required|integer', 'title' => 'required|max:200', 'session_number' => 'integer', 'description' => 'max:2000',
            'timezone' => 'required|in:' . implode(',', array_keys($this->timezones())), 'starts_at' => 'required|date', 'ends_at' => 'required|date',
            'repeat_weeks' => 'integer', 'meeting_provider' => 'required|in:' . implode(',', array_keys(self::PROVIDERS)), 'join_early_minutes' => 'integer',
            'meeting_url' => 'url', 'meeting_id' => 'max:191', 'meeting_passcode' => 'max:100', 'recording_url' => 'url',
            'status' => $edit ? 'required|in:' . implode(',', array_keys(self::STATUSES)) : ''];
    }

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if ($c = $this->request->query('cohort_id')) { $where[] = 's.cohort_id = ?'; $p[] = (int) $c; }
        if ($st = $this->request->query('status')) { $where[] = 's.status = ?'; $p[] = $st; }
        if ($this->request->query('when') === 'upcoming') { $where[] = 's.ends_at > UTC_TIMESTAMP()'; }
        [$rows, $pager] = $this->paged('class_sessions s JOIN cohorts h ON h.id = s.cohort_id WHERE ' . implode(' AND ', $where), $p,
            's.*, h.cohort_name', 's.starts_at ' . ($this->request->query('when') === 'upcoming' ? 'ASC' : 'DESC'));
        $cid = (int) $this->request->query('cohort_id', 0);
        $this->page('admin/crud/index', [
            'title' => 'Class sessions', 'createUrl' => url('/admin/sessions/create' . ($cid ? '?cohort_id=' . $cid : '')), 'createLabel' => 'Add sessions',
            'rows' => $rows, 'pager' => $pager,
            'filters' => [
                ['name' => 'cohort_id', 'label' => 'Cohort', 'type' => 'select', 'options' => $this->cohortOptions()],
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES],
                ['name' => 'when', 'label' => 'Show', 'type' => 'select', 'options' => ['upcoming' => 'Upcoming only']],
            ],
            'columns' => [
                ['label' => '#', 'render' => fn($r) => $r['session_number']],
                ['label' => 'Class', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['title']) . '</strong><br><span class="text-slate-500">' . e($r['cohort_name']) . '</span>'],
                ['label' => 'When', 'render' => fn($r) => local_date($r['starts_at'], $r['timezone']) . ' to ' . local_date($r['ends_at'], $r['timezone'], 'g:i A')],
                ['label' => 'Link', 'html' => true, 'render' => fn($r) => $r['meeting_url'] ? 'Set' : '<span class="text-red-700">Missing</span>'],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge($r['status'], in_array($r['status'], ['postponed'], true) ? 'badge-warn' : '')],
            ],
            'actions' => function ($r) {
                $a = [['label' => 'Edit', 'url' => url('/admin/sessions/' . $r['id'] . '/edit')]];
                foreach (['completed' => 'Complete', 'postponed' => 'Postpone', 'cancelled' => 'Cancel'] as $st => $lab) {
                    if ($r['status'] !== $st) {
                        $a[] = ['label' => $lab, 'url' => url('/admin/sessions/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => $st],
                            'confirm' => in_array($st, ['postponed', 'cancelled'], true) ? 'This notifies enrolled students. Continue?' : null];
                    }
                }
                return $a;
            },
        ]);
    }

    public function create(): void
    {
        $cohort = Cohort::find((int) $this->request->query('cohort_id', 0));
        $this->page('admin/crud/form', [
            'title' => 'Add class session(s)', 'action' => url('/admin/sessions'), 'fields' => $this->fields(false, $cohort['timezone'] ?? $this->defaultTz()),
            'values' => ['cohort_id' => $cohort['id'] ?? '', 'timezone' => $cohort['timezone'] ?? $this->defaultTz(), 'meeting_provider' => $cohort['meeting_provider_default'] ?? 'none'],
            'cancelUrl' => url($cohort ? '/admin/cohorts/' . $cohort['id'] : '/admin/sessions'),
        ]);
    }

    private function encryptPasscode(?string $plain, string $back): ?string
    {
        if ($plain === null || $plain === '') { return null; }
        try { return Crypto::encrypt($plain); }
        catch (\RuntimeException $e) { $this->done($e->getMessage(), $back, 'error'); }
    }

    public function store(): void
    {
        $d = $this->validate($this->rules(false), '/admin/sessions/create');
        $cohort = Cohort::find((int) $d['cohort_id']) ?? $this->abort(404);
        $tz = $d['timezone'];
        $repeat = max(1, min(40, (int) ($d['repeat_weeks'] ?? 1)));
        $startLocal = new \DateTimeImmutable(str_replace('T', ' ', $d['starts_at']), new \DateTimeZone($tz));
        $endLocal = new \DateTimeImmutable(str_replace('T', ' ', $d['ends_at']), new \DateTimeZone($tz));
        if ($endLocal <= $startLocal) {
            Session::flashInput($this->request->all(), ['ends_at' => 'End must be after the start.']);
            $this->redirect('/admin/sessions/create?cohort_id=' . $cohort['id']);
        }
        $pass = $this->encryptPasscode($d['meeting_passcode'], '/admin/sessions/create?cohort_id=' . $cohort['id']);
        $n = (int) ($d['session_number'] ?? 0) ?: ((int) Database::value('SELECT COALESCE(MAX(session_number),0) FROM class_sessions WHERE cohort_id = ?', [(int) $cohort['id']]) + 1);

        for ($i = 0; $i < $repeat; $i++) {
            $row = [
                'cohort_id' => (int) $cohort['id'], 'title' => $repeat > 1 ? $d['title'] . ' ' . ($n + $i) : $d['title'], 'description' => $d['description'],
                'session_number' => $n + $i, 'starts_at' => local_to_utc($startLocal->modify("+{$i} weeks")->format('Y-m-d H:i'), $tz),
                'ends_at' => local_to_utc($endLocal->modify("+{$i} weeks")->format('Y-m-d H:i'), $tz), 'timezone' => $tz,
                'meeting_provider' => $d['meeting_provider'], 'meeting_url' => $d['meeting_url'], 'meeting_id' => $d['meeting_id'],
                'meeting_passcode_encrypted' => $pass, 'join_early_minutes' => (int) ($d['join_early_minutes'] ?? 10),
                'recording_url' => $d['recording_url'], 'created_by_admin_id' => $this->adminId(),
            ];
            $id = ClassSession::create($row);
            unset($row['meeting_passcode_encrypted'], $row['meeting_url']);
            $this->audit('session.created', 'class_session', $id, null, $row);
        }
        $this->done($repeat > 1 ? "{$repeat} sessions created." : 'Session created.', '/admin/cohorts/' . $cohort['id']);
    }

    public function edit(string $id): void
    {
        $s = ClassSession::find((int) $id) ?? $this->abort(404);
        $s['starts_at'] = $this->toInput($s['starts_at'], $s['timezone']);
        $s['ends_at'] = $this->toInput($s['ends_at'], $s['timezone']);
        $this->page('admin/crud/form', [
            'title' => 'Edit class session', 'action' => url('/admin/sessions/' . $s['id']), 'fields' => $this->fields(true, $s['timezone']), 'values' => $s,
            'cancelUrl' => url('/admin/cohorts/' . $s['cohort_id']),
            'intro' => 'Changing the date or time re-schedules the class: the countdown and join window update automatically and enrolled students are notified.',
        ]);
    }

    public function update(string $id): void
    {
        $s = ClassSession::find((int) $id) ?? $this->abort(404);
        $back = '/admin/sessions/' . $s['id'] . '/edit';
        $d = $this->validate($this->rules(true), $back);
        $tz = $d['timezone'];
        $start = $this->fromInput($d['starts_at'], $tz);
        $end = $this->fromInput($d['ends_at'], $tz);
        if ($end <= $start) {
            Session::flashInput($this->request->all(), ['ends_at' => 'End must be after the start.']);
            $this->redirect($back);
        }
        $general = [
            'title' => $d['title'], 'description' => $d['description'], 'session_number' => $d['session_number'] ? (int) $d['session_number'] : null,
            'timezone' => $tz, 'meeting_provider' => $d['meeting_provider'], 'meeting_url' => $d['meeting_url'], 'meeting_id' => $d['meeting_id'],
            'join_early_minutes' => (int) ($d['join_early_minutes'] ?? 10), 'recording_url' => $d['recording_url'],
        ];
        $pass = $this->encryptPasscode($d['meeting_passcode'], $back);
        if ($pass !== null) { $general['meeting_passcode_encrypted'] = $pass; }
        $this->audit('session.updated', 'class_session', (int) $s['id'], $s, array_diff_key($general, ['meeting_passcode_encrypted' => 1, 'meeting_url' => 1]));
        ClassSession::update((int) $s['id'], $general);

        $svc = new ScheduleService();
        $timesChanged = $start !== $s['starts_at'] || $end !== $s['ends_at'];
        $target = ($timesChanged && in_array($s['status'], ['postponed', 'cancelled'], true) && $d['status'] === $s['status']) ? 'scheduled' : $d['status'];
        if ($timesChanged) { $svc->reschedule((int) $s['id'], $start, $end); }
        if ($target !== ($timesChanged ? 'scheduled' : $s['status'])) { $svc->setStatus((int) $s['id'], $target); }
        $this->done('Session updated.', '/admin/cohorts/' . $s['cohort_id']);
    }

    public function status(string $id): void
    {
        $s = ClassSession::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!isset(self::STATUSES[$new])) { $this->abort(422); }
        (new ScheduleService())->setStatus((int) $s['id'], $new);
        $this->done('Session marked ' . $new . '.', '/admin/sessions?cohort_id=' . $s['cohort_id']);
    }
}
