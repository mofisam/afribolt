<?php
namespace App\Controllers\Admin;

use App\Models\CareerLaunchRegistration;
use App\Services\CareerLaunchService;

/** Career Launch founding-price registrations. The team sends the payment link manually, then marks the person paid. */
class CareerLaunchController extends BaseAdminController
{
    protected array $roles = ['admin', 'finance_manager'];

    private const STATUSES = ['new' => 'New', 'payment_sent' => 'Payment link sent', 'paid' => 'Paid', 'cancelled' => 'Cancelled'];

    public function index(): void
    {
        $where = ['1=1']; $p = [];
        if (($s = (string) $this->request->query('status', '')) !== '' && isset(self::STATUSES[$s])) { $where[] = 'status = ?'; $p[] = $s; }
        if ($q = trim((string) $this->request->query('q', ''))) { $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)'; array_push($p, "%{$q}%", "%{$q}%", "%{$q}%"); }
        [$rows, $pager] = $this->paged('career_launch_registrations WHERE ' . implode(' AND ', $where), $p, '*', 'created_at DESC');
        $paid = (int) \App\Core\Database::value("SELECT COUNT(*) FROM career_launch_registrations WHERE status = 'paid'");
        $seats = \App\Controllers\Public\OpportunitiesController::FOUNDING_SEATS;

        $this->page('admin/crud/index', [
            'title' => 'Career Launch registrations', 'rows' => $rows, 'pager' => $pager,
            'intro' => '<p><strong>' . $paid . ' of ' . $seats . '</strong> founding places paid. Send each person their payment link, then mark them <em>Paid</em> here. The public page counts only <em>Paid</em> places.</p>',
            'filters' => [['name' => 'q', 'label' => 'Search name, email or phone'], ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::STATUSES]],
            'columns' => [
                ['label' => 'Person', 'html' => true, 'render' => fn($r) => '<strong>' . e($r['full_name']) . '</strong><br><a class="underline" href="mailto:' . e($r['email']) . '">' . e($r['email']) . '</a><br><span class="text-slate-500">' . e($r['phone']) . '</span>'],
                ['label' => 'Target role', 'render' => fn($r) => $r['target_role']],
                ['label' => 'Stage', 'html' => true, 'render' => fn($r) => $r['status'] !== 'paid' ? '<span class="text-slate-400">Locked until paid</span>'
                    : ((int) $r['stage'] >= 7 ? '<strong>All done</strong>' : '<strong>' . (int) $r['stage'] . '/6</strong> ' . e(CareerLaunchService::STAGES[(int) $r['stage'] - 1][0]))],
                ['label' => 'LinkedIn', 'html' => true, 'render' => fn($r) => $r['linkedin_url'] ? '<a class="underline" href="' . e($r['linkedin_url']) . '" target="_blank" rel="noopener noreferrer">Open</a>' : '<span class="text-slate-400">None</span>'],
                ['label' => 'Referral', 'render' => fn($r) => $r['referral_code'] ?? ''],
                ['label' => 'Status', 'html' => true, 'render' => fn($r) => $this->badge(self::STATUSES[$r['status']], $r['status'] === 'paid' ? 'badge-open' : ($r['status'] === 'cancelled' ? 'badge-closed' : ''))],
                ['label' => 'Registered', 'render' => fn($r) => local_date($r['created_at'], null, 'j M Y, g:i A')],
            ],
            'actions' => function ($r) {
                $a = [];
                foreach (['payment_sent' => 'Mark link sent', 'paid' => 'Mark paid', 'cancelled' => 'Cancel', 'new' => 'Reopen'] as $st => $label) {
                    if ($st === $r['status'] || ($st === 'payment_sent' && $r['status'] === 'paid')) { continue; }
                    $a[] = ['label' => $label, 'url' => url('/admin/career-launch/' . $r['id'] . '/status'), 'post' => true, 'fields' => ['status' => $st],
                        'confirm' => $st === 'cancelled' ? 'Cancel this registration?' : null];
                }
                if ($r['status'] === 'paid') {
                    if ((int) $r['stage'] < 7) { $a[] = ['label' => 'Stage done: next stage', 'url' => url('/admin/career-launch/' . $r['id'] . '/stage'), 'post' => true, 'fields' => ['dir' => 'next'], 'confirm' => 'Mark the current stage as done and move this student to the next one? They will be notified.']; }
                    if ((int) $r['stage'] > 2) { $a[] = ['label' => 'Back one stage', 'url' => url('/admin/career-launch/' . $r['id'] . '/stage'), 'post' => true, 'fields' => ['dir' => 'back'], 'confirm' => 'Move this student back one stage?']; }
                }
                $a[] = ['label' => 'Delete', 'url' => url('/admin/career-launch/' . $r['id'] . '/delete'), 'post' => true, 'confirm' => 'Delete this registration permanently?'];
                return $a;
            },
        ]);
    }

    public function status(string $id): void
    {
        $r = CareerLaunchRegistration::find((int) $id) ?? $this->abort(404);
        $new = (string) $this->request->post('status');
        if (!isset(self::STATUSES[$new])) { $this->abort(422); }
        $upd = ['status' => $new];
        if ($new === 'paid' && (int) $r['stage'] < 2) { $upd['stage'] = 2; } // Stage 1 (register) is done; the course unlocks
        CareerLaunchRegistration::update((int) $r['id'], $upd);
        $msgs = [
            'payment_sent' => ['Career Launch: payment link sent', 'We have sent your payment link. Check your email or WhatsApp.'],
            'paid' => ['Career Launch: payment confirmed', 'Your place is confirmed. Stage 2, Enrol in the course, is now unlocked.'],
            'cancelled' => ['Career Launch: registration cancelled', 'Your Career Launch registration was cancelled. Contact us if this is a mistake.'],
        ];
        if (isset($msgs[$new])) { CareerLaunchService::notify($r, $msgs[$new][0], $msgs[$new][1]); }
        $this->audit('career_launch.status_changed', 'career_launch_registration', (int) $r['id'], ['status' => $r['status']], ['status' => $new]);
        $this->done($r['full_name'] . ': ' . strtolower(self::STATUSES[$new]) . '.', '/admin/career-launch');
    }

    /** Move a paid student forward (a stage was completed) or back. 7 means all six stages are complete. */
    public function stage(string $id): void
    {
        $r = CareerLaunchRegistration::find((int) $id) ?? $this->abort(404);
        if ($r['status'] !== 'paid') { $this->done('Only paid students can move between stages.', '/admin/career-launch', 'error'); }
        $cur = (int) $r['stage'];
        $new = $this->request->post('dir') === 'back' ? max(2, $cur - 1) : min(7, $cur + 1);
        if ($new === $cur) { $this->redirect('/admin/career-launch'); }
        CareerLaunchRegistration::update((int) $r['id'], ['stage' => $new]);
        $this->audit('career_launch.stage_changed', 'career_launch_registration', (int) $r['id'], ['stage' => $cur], ['stage' => $new]);
        if ($new > $cur) {
            $msg = $new >= 7 ? 'You have completed all six stages of Career Launch. Well done!'
                : 'You completed Stage ' . $cur . ' and moved to Stage ' . $new . ': ' . CareerLaunchService::STAGES[$new - 1][0] . '.';
            CareerLaunchService::notify($r, $new >= 7 ? 'Career Launch complete' : 'Career Launch: next stage unlocked', $msg);
        }
        $this->done($r['full_name'] . ' is now ' . ($new >= 7 ? 'finished with all stages.' : 'on stage ' . $new . '.'), '/admin/career-launch');
    }

    public function delete(string $id): void
    {
        $r = CareerLaunchRegistration::find((int) $id) ?? $this->abort(404);
        CareerLaunchRegistration::delete((int) $r['id']);
        $this->audit('career_launch.deleted', 'career_launch_registration', (int) $r['id'], null, ['email' => $r['email']]);
        $this->done('Registration deleted.', '/admin/career-launch');
    }
}
