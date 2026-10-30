<?php
use App\Core\{Auth, Request, View};
$path = Request::capture()->path();
$unread = 0;
try { $unread = (new App\Services\NotificationService())->unreadCount((int) Auth::id('student')); } catch (\Throwable $e) {}
$icons = [
  'dash' => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
  'courses' => '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>',
  'alerts' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
  'me' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
];
$tabs = [['/student/dashboard', 'Home', 'dash'], ['/courses', 'Courses', 'courses'], ['/student/notifications', 'Alerts', 'alerts'], ['/student/profile', 'Profile', 'me']];
$cur = fn(string $href) => $path === $href || ($href !== '/courses' && str_starts_with($path, $href)) || ($href === '/courses' && str_starts_with($path, '/courses'));
?>
<!doctype html>
<html lang="en">
<head><?= View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body class="bg-slate-50 min-h-screen pb-24 md:pb-0">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-3 focus:bg-volt focus:z-50">Skip to content</a>
<header class="site-header">
  <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
    <a href="<?= e(url('/student/dashboard')) ?>" aria-label="My dashboard"><?= View::partial('partials/logo', ['width' => 130]) ?></a>
    <nav class="hidden md:flex items-center gap-6" aria-label="Student">
      <?php foreach ($tabs as [$href, $label]): ?>
        <a class="nav-link" href="<?= e(url($href)) ?>" <?= $cur($href) ? 'aria-current="page"' : '' ?>><?= e($label === 'Home' ? 'Dashboard' : ($label === 'Alerts' ? 'Notifications' : $label)) ?><?php if ($href === '/student/notifications' && $unread): ?> <span class="adm-badge"><?= (int) $unread ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="btn btn-ghost !py-1.5 !px-3 text-sm" type="submit">Log out</button></form>
  </div>
</header>
<main id="main" class="max-w-5xl mx-auto px-4 py-6 md:py-8">
  <?= View::partial('partials/flash') ?>
  <?= $content ?>
</main>
<nav class="btabs" aria-label="Student">
  <?php foreach ($tabs as [$href, $label, $ic]): ?>
    <a class="btab" href="<?= e(url($href)) ?>" <?= $cur($href) ? 'aria-current="page"' : '' ?>>
      <svg viewBox="0 0 24 24" aria-hidden="true"><?= $icons[$ic] ?></svg><span><?= e($label) ?></span>
      <?php if ($href === '/student/notifications' && $unread): ?><span class="dot"><?= $unread > 9 ? '9+' : (int) $unread ?><span class="sr-only"> unread</span></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/countdown.js')) ?>" defer></script>
<script src="<?= e(asset('js/join-class.js')) ?>" defer></script>
</body>
</html>
