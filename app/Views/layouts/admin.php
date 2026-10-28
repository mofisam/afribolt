<!doctype html>
<html lang="en">
<head>
<?= App\Core\View::partial('partials/head', ['title' => ($title ?? '') . ' | Admin']) ?>
<script>try { if (localStorage.getItem('adm-collapsed') === '1') document.documentElement.classList.add('adm-collapsed'); } catch (e) {}</script>
</head>
<body class="bg-slate-100">
<a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:p-3 focus:bg-volt">Skip to content</a>
<div class="adm-shell">
  <header class="adm-topbar">
    <button type="button" class="adm-iconbtn" data-adm-open aria-controls="adm-sidebar" aria-expanded="false" aria-label="Open menu"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12h18M3 6h18M3 18h18"/></svg></button>
    <span class="font-display font-bold truncate"><?= e($title ?? 'Admin') ?></span>
  </header>
  <div class="adm-overlay" data-adm-overlay hidden></div>
  <aside id="adm-sidebar" class="adm-sidebar" data-open="false"><?= App\Core\View::partial('partials/admin-sidebar') ?></aside>
  <main id="main" class="adm-main">
    <?= App\Core\View::partial('partials/flash') ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</body>
</html>
