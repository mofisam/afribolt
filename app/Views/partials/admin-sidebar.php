<?php
use App\Core\{Auth, Database, Request};

$admin = Auth::user('admin');
$me = fn(string ...$roles) => Auth::adminHasRole('super_admin', ...$roles);

// Counts that deserve attention (cheap COUNT queries; failures never break the page)
$alerts = [];
try {
    if ($me('admin', 'finance_manager')) {
        $alerts['enrolments'] = (int) Database::value("SELECT COUNT(*) FROM enrolments WHERE status = 'pending_review'");
    }
    if ($me('admin', 'content_manager')) {
        $alerts['sessions'] = (int) Database::value("SELECT COUNT(*) FROM class_sessions WHERE status = 'scheduled' AND starts_at BETWEEN UTC_TIMESTAMP() AND UTC_TIMESTAMP() + INTERVAL 7 DAY AND (meeting_url IS NULL OR meeting_url = '')");
    }
    if ($me('admin')) {
        $alerts['email-logs'] = (int) Database::value("SELECT COUNT(*) FROM email_logs WHERE status = 'failed'");
    }
} catch (\Throwable $e) { $alerts = []; }
$alertHint = ['enrolments' => 'need review', 'sessions' => 'classes this week have no meeting link', 'email-logs' => 'failed emails'];

$icons = [
  '' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
  'cohorts' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>',
  'sessions' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
  'courses' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
  'categories' => '<path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><circle cx="7" cy="7" r="1.5"/>',
  'announcements' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
  'attendance' => '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>',
  'students' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
  'instructors' => '<circle cx="12" cy="8" r="7"/><path d="M8.21 13.89L7 23l5-3 5 3-1.21-9.12"/>',
  'enrolments' => '<path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>',
  'payments' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
  'reports' => '<path d="M18 20V10M12 20V4M6 20v-6"/>',
  'gateways' => '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>',
  'audit-logs' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
  'email-logs' => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/>',
  'settings' => '<path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6"/>',
  'admin-users' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
];
$ico = fn(string $slug) => '<svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . ($icons[$slug] ?? '') . '</svg>';

// group => [ [slug, label, roles besides super_admin] ]
$groups = [
  'Overview' => [['', 'Dashboard', []]],
  'Teaching' => [['cohorts', 'Cohorts', ['admin', 'content_manager']], ['sessions', 'Class sessions', ['admin', 'content_manager']],
                 ['attendance', 'Attendance', ['admin', 'content_manager']], ['announcements', 'Announcements', ['admin', 'content_manager']],
                 ['courses', 'Courses', ['admin', 'content_manager']], ['categories', 'Categories', ['admin', 'content_manager']]],
  'People'   => [['students', 'Students', ['admin', 'finance_manager', 'content_manager']], ['enrolments', 'Enrolments', ['admin', 'finance_manager']],
                 ['instructors', 'Instructors', ['admin']]],
  'Money'    => [['payments', 'Payments', ['admin', 'finance_manager']], ['reports', 'Reports', ['admin', 'finance_manager']], ['gateways', 'Gateways', ['finance_manager']]],
  'System'   => [['audit-logs', 'Audit log', ['admin']], ['email-logs', 'Email log', ['admin']], ['settings', 'Settings', ['admin']], ['admin-users', 'Admin users', []]],
];
$current = explode('/', trim(substr(Request::capture()->path(), strlen('/admin')), '/'))[0];
$roleLabel = ['super_admin' => 'Super admin', 'admin' => 'Admin', 'content_manager' => 'Content manager', 'finance_manager' => 'Finance manager'][$admin['admin_role']] ?? $admin['admin_role'];
$initials = strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1));
?>
<div class="adm-brand">
  <a class="adm-logo" href="<?= e(url('/admin')) ?>" aria-label="Admin dashboard">
    <span class="adm-logo-full"><?= App\Core\View::partial('partials/logo', ['width' => 120]) ?></span>
    <span class="hidden adm-logo-mini font-display font-extrabold text-bolt">AB</span>
  </a>
  <button type="button" class="adm-iconbtn adm-close-btn" data-adm-close aria-label="Close menu"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
  <button type="button" class="adm-iconbtn adm-collapse-btn" data-adm-collapse aria-label="Collapse sidebar" aria-pressed="false"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M11 17l-5-5 5-5M18 17l-5-5 5-5"/></svg></button>
</div>

<nav class="adm-scroll" aria-label="Admin">
  <?php foreach ($groups as $groupName => $items):
    $visible = array_values(array_filter($items, fn($i) => $i[0] === 'admin-users' ? Auth::adminHasRole('super_admin') : (!$i[2] || $me(...$i[2]))));
    if (!$visible) continue; ?>
    <div class="adm-group">
      <?php if ($groupName !== 'Overview'): ?><p class="adm-group-label"><?= e($groupName) ?></p><?php endif; ?>
      <?php foreach ($visible as [$slug, $label]):
        $active = $current === $slug; $n = $alerts[$slug] ?? 0; ?>
        <a class="adm-link" href="<?= e(url('/admin' . ($slug ? '/' . $slug : ''))) ?>" title="<?= e($label) ?>" <?= $active ? 'aria-current="page"' : '' ?>>
          <?= $ico($slug) ?><span class="adm-label"><?= e($label) ?></span>
          <?php if ($n > 0): ?><span class="adm-badge" title="<?= (int) $n ?> <?= e($alertHint[$slug] ?? '') ?>"><?= $n > 99 ? '99+' : (int) $n ?><span class="sr-only"> <?= e($alertHint[$slug] ?? '') ?></span></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</nav>

<div class="adm-user">
  <div class="flex items-center gap-3">
    <span class="adm-avatar" aria-hidden="true"><?= e($initials) ?></span>
    <div class="adm-user-meta min-w-0"><p class="font-bold text-white truncate"><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></p><p class="text-xs text-slate-400"><?= e($roleLabel) ?></p></div>
  </div>
  <a class="adm-link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener" title="View public site"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14L21 3"/></svg><span class="adm-label">View site</span></a>
  <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?>
    <button class="adm-link w-full text-left" type="submit" title="Log out"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5M21 12H9"/></svg><span class="adm-label">Log out</span></button>
  </form>
</div>
