<!doctype html>
<html lang="en">
<head><?= App\Core\View::partial('partials/head', ['title' => 'Certificate: ' . $c['course_title']]) ?><meta name="robots" content="noindex"></head>
<body>
<div class="cert-page">
  <div class="w-full max-w-4xl">
    <div class="cert">
      <div><span class="inline-block"><?= App\Core\View::partial('partials/logo', ['width' => 190]) ?></span>
        <p class="mt-4 uppercase tracking-[.3em] text-sm font-bold text-slate-500">Certificate of completion</p></div>
      <div>
        <p class="text-slate-600">This certifies that</p>
        <p class="font-display font-extrabold text-4xl md:text-5xl text-ink mt-2"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></p>
        <p class="mt-4 text-slate-600">has successfully completed the online course</p>
        <p class="font-display font-bold text-2xl md:text-3xl text-bolt-dark mt-2"><?= e($c['course_title']) ?></p>
        <?php if ($c['total_seconds'] > 0): ?><p class="mt-2 text-slate-600">Total learning time: <?= e(fmt_duration((int) $c['total_seconds'], true)) ?></p><?php endif; ?>
      </div>
      <div class="flex flex-wrap items-end justify-between gap-4 text-sm text-left">
        <div><p class="text-slate-500">Completed on</p><p class="font-bold"><?= e(local_date($c['completed_at'], null, 'j F Y')) ?></p></div>
        <?php if ($c['instructor_name']): ?><div class="text-center"><p class="font-display font-bold border-t-2 border-ink pt-1 px-6"><?= e($c['instructor_name']) ?></p><p class="text-slate-500">Instructor</p></div><?php endif; ?>
        <div class="text-right"><p class="text-slate-500">Certificate ID</p><p class="font-bold tracking-wide"><?= e($c['certificate_code']) ?></p>
          <p class="text-xs text-slate-500">Verify at <?= e(preg_replace('#^https?://#', '', url('/certificate/' . $c['certificate_code']))) ?></p></div>
      </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-3 justify-center no-print">
      <button class="btn btn-primary" type="button" onclick="window.print()">Print or save as PDF</button>
      <button class="btn btn-ghost" type="button" data-copy="<?= e(url('/certificate/' . $c['certificate_code'])) ?>">Copy verification link</button>
      <a class="btn btn-ghost" href="<?= e(url('/')) ?>">Visit AfriBolt</a>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/course.js')) ?>" defer></script>
</body>
</html>
