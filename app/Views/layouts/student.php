<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body class="bg-slate-50 min-h-screen">
<header class="bg-white border-b border-slate-200">
  <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between gap-4">
    <a href="<?= e(url('/student/dashboard')) ?>"><?= App\Core\View::partial('partials/logo', ['width' => 130]) ?></a>
    <nav class="flex items-center gap-4 text-sm font-semibold">
      <a href="<?= e(url('/student/dashboard')) ?>">Dashboard</a>
      <a href="<?= e(url('/courses')) ?>">Courses</a>
      <a href="<?= e(url('/student/notifications')) ?>">Notifications</a>
      <a href="<?= e(url('/student/profile')) ?>">Profile</a>
      <form method="post" action="<?= e(url('/logout')) ?>"><?= csrf_field() ?><button class="underline">Log out</button></form>
    </nav>
  </div>
</header>
<main class="max-w-5xl mx-auto px-4 py-8">
  <?= App\Core\View::partial('partials/flash') ?>
  <?= $content ?>
</main>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/countdown.js')) ?>" defer></script>
<script src="<?= e(asset('js/join-class.js')) ?>" defer></script>
</body>
</html>
