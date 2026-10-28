<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body class="bg-white antialiased">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:p-3 focus:bg-volt">Skip to content</a>
<header class="border-b border-slate-200 bg-white sticky top-0 z-30">
  <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
    <a href="<?= e(url('/')) ?>" aria-label="AfriBolt Technologies home"><?= App\Core\View::partial('partials/logo', ['width' => 150]) ?></a>
    <button type="button" data-menu-toggle aria-expanded="false" aria-label="Menu" class="md:hidden p-2 border rounded">Menu</button>
    <nav data-menu class="hidden md:flex absolute md:static top-16 left-0 right-0 bg-white md:bg-transparent border-b md:border-0 p-4 md:p-0 flex-col md:flex-row gap-4 md:gap-6 items-start md:items-center font-semibold">
      <a href="<?= e(url('/courses')) ?>">Courses</a>
      <a href="<?= e(url('/career-accelerator')) ?>">Career Accelerator</a>
      <a href="<?= e(url('/about')) ?>">About</a>
      <a href="<?= e(url('/contact')) ?>">Contact</a>
      <?php if (App\Core\Auth::check('student')): ?>
        <a class="btn btn-primary" href="<?= e(url('/student/dashboard')) ?>">My dashboard</a>
      <?php else: ?>
        <a href="<?= e(url('/login')) ?>">Log in</a>
        <a class="btn btn-accent" href="<?= e(url('/register')) ?>">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main">
  <div class="max-w-6xl mx-auto px-4 pt-4"><?= App\Core\View::partial('partials/flash') ?></div>
  <?= $content ?>
</main>
<footer class="bg-ink text-slate-200 mt-20">
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
