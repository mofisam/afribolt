<?php /** @var string $title */ ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e(($title ?? '') !== '' ? $title . ' | ' : '') ?><?= e(config('app.name')) ?></title>
<link rel="icon" href="<?= e(asset('img/logo.jpeg')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700&family=Raleway:ital,wght@0,500;0,700;0,800;1,500&display=swap" rel="stylesheet">
<?php if (config('app.env') === 'production'): ?>
<link rel="stylesheet" href="<?= e(asset('css/tailwind.css')) ?>">
<?php else: ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: {
  colors: { bolt: { DEFAULT: '#1B8CCB', dark: '#0F6BA0', tint: '#EAF5FC' }, ink: '#0A2540', spark: '#F26B21', volt: '#FFC800' },
  fontFamily: { display: ['Raleway', 'system-ui', 'sans-serif'], sans: ['"Nunito Sans"', 'system-ui', 'sans-serif'] } } } };
</script>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
