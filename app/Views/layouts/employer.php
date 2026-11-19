<?php
$path = App\Core\Request::capture()->path();
$emp = App\Core\Auth::user('employer');
$links = ['/employer' => 'Dashboard', '/employer/jobs' => 'My jobs', '/employer/verification' => 'Verification'];
$active = fn(string $href) => $href === '/employer' ? $path === '/employer' : str_starts_with($path, $href);
?>
<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => ($title ?? '') . ' | Employer']) ?></head>
<body class="bg-slate-50 min-h-screen">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-5xl mx-auto px-4 min-h-16 py-2 flex flex-wrap items-center justify-between gap-3">
    <a href="<?= e(url('/employer')) ?>" class="flex items-center gap-2"><?= App\Core\View::partial('partials/logo', ['width' => 130]) ?><span class="text-xs font-bold uppercase tracking-wide text-slate-500">Employers</span></a>
    <nav class="flex flex-wrap items-center gap-1 text-sm font-semibold" aria-label="Employer">
      <?php foreach ($links as $href => $label): ?>
        <a class="px-3 py-2 rounded <?= $active($href) ? 'bg-bolt-tint text-bolt-dark' : 'hover:bg-slate-100' ?>" href="<?= e(url($href)) ?>" <?= $active($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <span class="hidden md:inline text-slate-500 px-2"><?= e($emp['company_name'] ?? '') ?></span>
      <form method="post" action="<?= e(url('/employer/logout')) ?>" class="ml-2"><?= csrf_field() ?><button class="px-3 py-2 underline">Log out</button></form>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-8"><?= App\Core\View::partial('partials/flash') ?><?= $content ?></main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
