<?php
use App\Core\{Auth, Request, View};
$path = Request::capture()->path();
$isIn = Auth::check('student');
$links = ['/courses' => 'Courses', '/career-accelerator' => 'Career Accelerator', '/about' => 'About', '/contact' => 'Contact'];
$cur = fn(string $href) => $path === $href || str_starts_with($path, $href . '/');
?>
<!doctype html>
<html lang="en">
<head><?= View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body class="bg-white antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-3 focus:bg-volt focus:z-50">Skip to content</a>
<header class="site-header">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between gap-3">
    <a href="<?= e(url('/')) ?>" aria-label="AfriBolt Technologies home"><?= View::partial('partials/logo', ['width' => 140]) ?></a>

    <nav class="hidden md:flex items-center gap-7" aria-label="Main">
      <?php foreach ($links as $href => $label): ?>
        <a class="nav-link" href="<?= e(url($href)) ?>" <?= $cur($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <?php if ($isIn): ?><a class="btn btn-primary !py-2" href="<?= e(url('/student/dashboard')) ?>">My dashboard</a>
      <?php else: ?><a class="nav-link" href="<?= e(url('/login')) ?>">Log in</a><a class="btn btn-accent !py-2" href="<?= e(url('/register')) ?>">Register</a><?php endif; ?>
    </nav>

    <div class="flex items-center gap-2 md:hidden">
      <?php if ($isIn): ?><a class="btn btn-primary !py-2 !px-3 text-sm" href="<?= e(url('/student/dashboard')) ?>">Dashboard</a>
      <?php else: ?><a class="btn btn-accent !py-2 !px-3 text-sm" href="<?= e(url('/register')) ?>">Register</a><?php endif; ?>
      <button type="button" class="hamb" data-menu-toggle aria-controls="mobile-menu" aria-expanded="false" aria-label="Open menu">
        <svg data-icon-open viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
        <svg data-icon-close viewBox="0 0 24 24" aria-hidden="true" style="display:none"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>
  </div>

  <div id="mobile-menu" class="mmenu md:hidden" data-menu hidden>
    <nav aria-label="Mobile">
      <?php foreach ($links + ['/faq' => 'FAQs'] as $href => $label): ?>
        <a class="mnav-link" href="<?= e(url($href)) ?>" <?= $cur($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="mt-4 grid gap-2">
      <?php if ($isIn): ?>
        <a class="btn btn-primary" href="<?= e(url('/student/dashboard')) ?>">Go to my dashboard</a>
        <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="btn btn-ghost w-full" type="submit">Log out</button></form>
      <?php else: ?>
        <a class="btn btn-accent" href="<?= e(url('/register')) ?>">Create a free account</a>
        <a class="btn btn-ghost" href="<?= e(url('/login')) ?>">Log in</a>
      <?php endif; ?>
    </div>
  </div>
</header>
<main id="main">
  <div class="max-w-6xl mx-auto px-4 pt-4"><?= View::partial('partials/flash') ?></div>
  <?= $content ?>
</main>
<footer class="site-footer bg-ink text-slate-200 mt-20">
  <div class="max-w-6xl mx-auto px-4 py-10 grid gap-8 md:grid-cols-3">
    <div><p class="font-display font-extrabold text-white text-xl">AfriBolt Technologies</p><p class="italic mt-1">&hellip;Dream big, Achieve big</p></div>
    <div><p class="font-bold text-white mb-2">Explore</p><ul class="space-y-1">
      <li><a href="<?= e(url('/courses')) ?>">Courses</a></li><li><a href="<?= e(url('/career-accelerator')) ?>">Career Accelerator</a></li>
      <li><a href="<?= e(url('/faq')) ?>">FAQs</a></li><li><a href="<?= e(url('/contact')) ?>">Contact</a></li></ul></div>
    <div><p class="font-bold text-white mb-2">Policies</p><ul class="space-y-1">
      <li><a href="<?= e(url('/privacy')) ?>">Privacy</a></li><li><a href="<?= e(url('/terms')) ?>">Terms</a></li><li><a href="<?= e(url('/refund-policy')) ?>">Refunds &amp; cancellation</a></li></ul></div>
  </div>
  <p class="text-center text-sm text-slate-400 pb-6">&copy; <?= gmdate('Y') ?> AfriBolt Technologies. All rights reserved.</p>
</footer>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/countdown.js')) ?>" defer></script>
</body>
</html>
