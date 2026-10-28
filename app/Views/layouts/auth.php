<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => $title ?? '']) ?></head>
<body class="bg-bolt-tint min-h-screen flex flex-col items-center justify-center p-4">
  <a href="<?= e(url('/')) ?>" class="mb-6" aria-label="Home"><?= App\Core\View::partial('partials/logo', ['width' => 200]) ?></a>
  <main class="w-full max-w-md bg-white rounded-lg border border-slate-200 p-6 sm:p-8">
    <?= App\Core\View::partial('partials/flash') ?>
    <?= $content ?>
  </main>
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</body>
</html>
