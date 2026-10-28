<?php
/** @var array $c cohort row joined with course info */
$badge = ['open' => ['Open for registration', 'badge-open'], 'published' => ['Open for registration', 'badge-open'], 'closed' => ['Registration closed', 'badge-closed'], 'in_progress' => ['In progress', 'badge-warn']][$c['status']] ?? [ucfirst($c['status']), ''];
?>
<a href="<?= e(url('/cohorts/' . $c['cohort_code'])) ?>" class="block border border-slate-300 rounded-lg p-5 hover:border-bolt bg-white">
  <span class="badge <?= $badge[1] ?>"><?= e($badge[0]) ?></span>
  <h3 class="mt-3 text-xl font-bold"><?= e($c['course_title']) ?></h3>
  <p class="text-slate-600"><?= e($c['cohort_name']) ?></p>
  <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
    <div><dt class="text-slate-500">Starts</dt><dd class="font-semibold"><?= e(local_date($c['start_at'], $c['timezone'], 'j M Y, g:i A')) ?></dd></div>
    <div><dt class="text-slate-500">Fee</dt><dd class="font-semibold"><?= e(money($c['fee_amount'], $c['currency'])) ?></dd></div>
  </dl>
</a>
