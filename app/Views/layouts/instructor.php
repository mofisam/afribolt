<?php
$path = App\Core\Request::capture()->path();
$links = ['/instructor' => 'My classes', '/instructor/announcements' => 'Announcements', '/instructor/profile' => 'Profile'];
$active = fn(string $href) => $href === '/instructor' ? ($path === '/instructor' || str_starts_with($path, '/instructor/cohorts') || str_starts_with($path, '/instructor/sessions')) : str_starts_with($path, $href);
?>
<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => ($title ?? '') . ' | Instructor']) ?></head>
<body class="bg-slate-50 min-h-screen">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-5xl mx-auto px-4 min-h-16 py-2 flex flex-wrap items-center justify-between gap-3">
    <a href="<?= e(url('/instructor')) ?>"><?= App\Core\View::partial('partials/logo', ['width' => 130]) ?></a>
    <nav class="flex flex-wrap items-center gap-1 text-sm font-semibold" aria-label="Instructor">
      <?php foreach ($links as $href => $label): ?>
        <a class="px-3 py-2 rounded <?= $active($href) ? 'bg-bolt-tint text-bolt-dark' : 'hover:bg-slate-100' ?>" href="<?= e(url($href)) ?>" <?= $active($href) ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
      <form method="post" action="<?= e(url('/instructor/logout')) ?>" class="ml-2"><?= csrf_field() ?><button class="px-3 py-2 underline">Log out</button></form>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-8"><?= App\Core\View::partial('partials/flash') ?><?= $content ?></main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
