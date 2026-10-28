<h1 class="text-3xl font-extrabold">Hello <?= e($me['first_name']) ?></h1>
<div class="mt-5 grid sm:grid-cols-3 gap-4">
  <?php foreach ([['Active cohorts', $stats['cohorts']], ['Confirmed students', $stats['students']], ['Classes in the next 7 days', $stats['week']]] as [$l, $v]): ?>
    <div class="bg-white border rounded-lg p-4"><p class="text-slate-500 text-sm"><?= e($l) ?></p><p class="font-display font-extrabold text-3xl"><?= (int) $v ?></p></div>
  <?php endforeach; ?>
</div>

<?php if ($noLink): ?>
<div class="mt-6 border-l-4 border-spark bg-orange-50 rounded p-4" role="status">
  <p class="font-bold">These classes have no meeting link yet</p>
  <ul class="mt-1 text-sm space-y-1">
    <?php foreach ($noLink as $s): ?><li><?= e($s['cohort_name'] . ' | ' . $s['title'] . ' | ' . local_date($s['starts_at'], $s['timezone'], 'D j M, g:i A')) ?>
      &middot; <a class="underline font-semibold" href="<?= e(url('/instructor/sessions/' . $s['id'] . '/edit')) ?>">Add link</a></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<h2 class="mt-8 text-xl font-bold">Upcoming classes</h2>
<ul class="mt-3 divide-y bg-white border rounded-lg">
  <?php foreach ($upcoming as $s): $live = $s['starts_at'] <= utc_now(); ?>
    <li class="p-4 flex flex-wrap items-center justify-between gap-3">
      <div><p class="font-semibold"><?= e($s['title']) ?> <span class="text-slate-500 font-normal">&middot; <?= e($s['cohort_name']) ?></span> <?= $live ? '<span class="badge badge-open">In progress</span>' : '' ?></p>
        <p class="text-sm text-slate-600"><?= e(local_date($s['starts_at'], $s['timezone'])) ?> to <?= e(local_date($s['ends_at'], $s['timezone'], 'g:i A')) ?></p></div>
      <div class="flex gap-2">
        <?php if ($s['meeting_url']): ?><a class="btn btn-primary !py-1.5" target="_blank" rel="noopener" href="<?= e($s['meeting_url']) ?>">Open meeting</a>
        <?php else: ?><a class="btn btn-ghost !py-1.5" href="<?= e(url('/instructor/sessions/' . $s['id'] . '/edit')) ?>">Add link</a><?php endif; ?>
      </div>
    </li>
  <?php endforeach; if (!$upcoming): ?><li class="p-4 text-slate-500">No upcoming classes.</li><?php endif; ?>
</ul>
<h2 class="mt-8 text-xl font-bold">My cohorts</h2>
<div class="mt-3 grid md:grid-cols-2 gap-4">
  <?php foreach ($cohorts as $c): ?>
    <a class="block bg-white border rounded-lg p-5 hover:border-bolt" href="<?= e(url('/instructor/cohorts/' . $c['id'])) ?>">
      <span class="badge"><?= e($c['assignment_role']) ?></span> <span class="badge"><?= e(str_replace('_', ' ', $c['status'])) ?></span>
      <h3 class="mt-2 text-lg font-bold"><?= e($c['cohort_name']) ?></h3>
      <p class="text-slate-600"><?= e($c['course_title']) ?></p>
      <p class="text-sm mt-2"><?= (int) $c['students'] ?> confirmed student<?= $c['students'] == 1 ? '' : 's' ?> &middot; starts <?= e(local_date($c['start_at'], $c['timezone'], 'j M Y')) ?></p></a>
  <?php endforeach; if (!$cohorts): ?><p class="text-slate-500">You haven't been assigned to a cohort yet.</p><?php endif; ?>
</div>
