<?php
$list = array_values(array_filter($courses, function ($c) use ($tab) {
    $pc = $c['progress']['percent'];
    return $tab === 'completed' ? $pc >= 100 : ($tab === 'progress' ? ($pc < 100) : true);
}));
$count = fn(callable $f) => count(array_filter($courses, $f));
?>
<div class="flex flex-wrap items-end justify-between gap-3"><h1 class="text-3xl font-extrabold">My learning</h1>
  <a class="btn btn-ghost !py-2" href="<?= e(url('/courses?mode=self_paced')) ?>">Browse more courses</a></div>
<nav class="mt-5 flex gap-1 border-b overflow-x-auto" aria-label="Filter">
  <?php foreach (['all' => ['All', count($courses)], 'progress' => ['In progress', $count(fn($c) => $c['progress']['percent'] < 100)], 'completed' => ['Completed', $count(fn($c) => $c['progress']['percent'] >= 100)]] as $k => [$label, $n]): ?>
    <a class="pl-tab" href="<?= e(url('/student/learning?tab=' . $k)) ?>" <?= $tab === $k || ($k === 'all' && !in_array($tab, ['progress', 'completed'], true)) ? 'aria-selected="true"' : '' ?>><?= e($label) ?> (<?= (int) $n ?>)</a>
  <?php endforeach; ?>
</nav>

<?php if (!$list): ?>
  <div class="mt-8 bg-white border rounded-lg p-10 text-center">
    <p class="text-xl font-bold"><?= $courses ? 'Nothing here yet.' : 'You are not enrolled in any self-paced courses yet.' ?></p>
    <p class="mt-1 text-slate-600">Pick a course and learn at your own pace, any time.</p>
    <a class="btn btn-primary mt-4" href="<?= e(url('/courses?mode=self_paced')) ?>">Find a course</a></div>
<?php else: ?>
<div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($list as $c): $p = $c['progress']; ?>
    <article class="ccard">
      <a href="<?= e(url('/learn/' . $c['slug'])) ?>" class="ccard-img" aria-label="Open <?= e($c['title']) ?>">
        <?php if (!empty($c['thumbnail_url'])): ?><img src="<?= e(asset($c['thumbnail_url'])) ?>" alt="" loading="lazy"><?php else: ?><span class="ph-letters"><?= e(strtoupper(substr($c['title'], 0, 2))) ?></span><?php endif; ?></a>
      <div class="ccard-body">
        <h2 class="ccard-title"><a href="<?= e(url('/learn/' . $c['slug'])) ?>"><?= e($c['title']) ?></a></h2>
        <?php if ($c['instructor_name']): ?><p class="ccard-meta"><?= e($c['instructor_name']) ?></p><?php endif; ?>
        <div class="mt-auto pt-3"><div class="bar"><span style="width:<?= (int) $p['percent'] ?>%"></span></div>
          <p class="text-sm text-slate-600 mt-1"><?= (int) $p['percent'] ?>% complete &middot; <?= (int) $p['done'] ?> of <?= (int) $p['total'] ?> lessons</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <a class="btn btn-primary !py-1.5 !px-3 text-sm" href="<?= e(url('/learn/' . $c['slug'])) ?>"><?= $p['percent'] >= 100 ? 'Review course' : ($p['done'] ? 'Continue' : 'Start course') ?></a>
            <?php if ($c['certificate_code']): ?><a class="btn btn-ghost !py-1.5 !px-3 text-sm" target="_blank" rel="noopener" href="<?= e(url('/certificate/' . $c['certificate_code'])) ?>">Certificate</a><?php endif; ?></div></div>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
