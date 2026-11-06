<?php
use App\Core\{Request, View};
$path = Request::capture()->path();
$split = in_array($path, ['/login', '/register', '/forgot-password'], true) || str_starts_with($path, '/reset-password');
$wide = $path === '/register';
?>
<!doctype html>
<html lang="en">
<head><?= View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body>
<div class="auth-shell <?= $split ? 'auth-split' : '' ?>">
  <?php if ($split): ?>
  <aside class="auth-brand" aria-hidden="false">
    <svg class="bolt" viewBox="0 0 24 24" aria-hidden="true"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
    <a href="<?= e(url('/')) ?>" class="inline-block bg-white rounded-lg p-2 self-start relative" aria-label="AfriBolt home"><?= View::partial('partials/logo', ['width' => 170]) ?></a>
    <div class="relative">
      <h2 class="font-display font-extrabold text-white text-4xl leading-tight">Learn at your pace.<br>Get hired.</h2>
      <ul class="mt-6 space-y-4 text-lg">
        <?php foreach (['Learn any time, on any device, and pick up where you left off', 'Earn a certificate you can share with employers', 'Get job matches based on what you are learning'] as $b): ?>
          <li class="flex gap-3"><span class="mt-1 grid place-items-center w-6 h-6 rounded-full bg-volt text-ink font-bold text-sm shrink-0" aria-hidden="true">&#10003;</span><span><?= e($b) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <p class="relative italic text-slate-300">&hellip;Dream big, Achieve big</p>
  </aside>
  <?php endif; ?>

  <main class="auth-main">
    <a href="<?= e(url('/')) ?>" class="auth-mobile-logo mb-6" aria-label="AfriBolt home"><?= View::partial('partials/logo', ['width' => $split ? 170 : 200]) ?></a>
    <div class="auth-card <?= $wide ? 'wide' : '' ?> <?= $split ? '' : 'boxed' ?>">
      <?= View::partial('partials/flash') ?>
      <?= $content ?>
    </div>
    <p class="mt-8 text-sm text-slate-500"><a class="underline" href="<?= e(url('/')) ?>">&larr; Back to home</a><?php if ($split): ?> &middot; <a class="underline" href="<?= e(url('/privacy')) ?>">Privacy</a> &middot; <a class="underline" href="<?= e(url('/terms')) ?>">Terms</a><?php endif; ?></p>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/auth.js')) ?>" defer></script>
</body>
</html>
