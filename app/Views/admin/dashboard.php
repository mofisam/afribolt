<?php
$hour = (int) local_date(utc_now(), null, 'G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$fmt = fn(float $v) => $cur . ' ' . number_format($v, $v == floor($v) ? 0 : 2);
$pct = $revLast > 0 ? (int) round(100 * ($revThis - $revLast) / $revLast) : null;
$maxMonth = $months ? max(max($months), 1) : 1;
$tone = ['warn' => 'border-spark bg-orange-50', 'bad' => 'border-red-600 bg-red-50', 'good' => 'border-green-600 bg-green-50'];
?>
<div class="flex flex-wrap items-end justify-between gap-4">
  <div>
    <p class="text-sm text-slate-500"><?= e(local_date(utc_now(), null, 'l, j F Y')) ?></p>
    <h1 class="text-3xl font-extrabold"><?= e($greet) ?>, <?= e($admin['first_name']) ?></h1>
  </div>
  <div class="flex flex-wrap gap-2">
    <?php if ($teach): ?>
      <a class="btn btn-primary !py-2" href="<?= e(url('/admin/cohorts/create')) ?>">New cohort</a>
      <a class="btn btn-ghost !py-2" href="<?= e(url('/admin/sessions/create')) ?>">Add classes</a>
      <a class="btn btn-ghost !py-2" href="<?= e(url('/admin/announcements/create')) ?>">Announce</a>
    <?php elseif ($money): ?>
      <a class="btn btn-primary !py-2" href="<?= e(url('/admin/payments')) ?>">Payments</a>
      <a class="btn btn-ghost !py-2" href="<?= e(url('/admin/reports')) ?>">Reports</a>
    <?php endif; ?>
  </div>
</div>

<!-- Needs attention -->
<section class="mt-6" aria-labelledby="attn-h">
  <h2 id="attn-h" class="font-display font-bold text-lg">Needs your attention</h2>
  <?php if (!$attention): ?>
    <div class="mt-2 flex items-center gap-3 bg-white border border-green-200 rounded-lg p-4">
      <span class="grid place-items-center w-9 h-9 rounded-full bg-green-100 text-green-700" aria-hidden="true">&#10003;</span>
      <p><span class="font-bold">All clear.</span> <span class="text-slate-600">Nothing is waiting on you right now.</span></p>
    </div>
  <?php else: ?>
    <ul class="mt-2 grid md:grid-cols-2 xl:grid-cols-3 gap-3">
      <?php foreach ($attention as $a): ?>
        <li><a class="block h-full border-l-4 rounded-lg p-4 bg-white hover:shadow-md transition-shadow <?= $tone[$a['tone']] ?>" href="<?= e(url($a['url'])) ?>">
          <span class="flex items-baseline gap-2"><span class="font-display font-extrabold text-3xl"><?= (int) $a['n'] ?></span><span class="font-bold"><?= e($a['label']) ?></span></span>
          <span class="block text-sm text-slate-600 mt-1"><?= e($a['hint']) ?></span>
          <span class="block text-sm font-semibold text-bolt-dark mt-2">Open &rarr;</span></a></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<!-- KPIs -->
<section class="mt-8 grid sm:grid-cols-2 xl:grid-cols-4 gap-4" aria-label="Key numbers">
  <?php if ($money): ?>
  <div class="bg-white border rounded-lg p-5">
    <p class="text-sm text-slate-500">Net revenue this month</p>
    <p class="mt-1 font-display font-extrabold text-3xl"><?= e($fmt($revThis)) ?></p>
    <p class="mt-1 text-sm <?= $pct === null ? 'text-slate-500' : ($pct >= 0 ? 'text-green-700' : 'text-red-700') ?>">
      <?= $pct === null ? 'No revenue last month to compare' : ($pct >= 0 ? '&#9650; ' : '&#9660; ') . abs($pct) . '% vs last month (' . e($fmt($revLast)) . ')' ?></p>
  </div>
  <?php endif; ?>
  <div class="bg-white border rounded-lg p-5">
    <p class="text-sm text-slate-500">Confirmed enrolments</p>
    <p class="mt-1 font-display font-extrabold text-3xl"><?= (int) $kpi['confirmed'] ?></p>
    <p class="mt-1 text-sm text-slate-500">+<?= (int) $kpi['newEnrol'] ?> registrations in the last 7 days</p>
  </div>
  <div class="bg-white border rounded-lg p-5">
    <p class="text-sm text-slate-500">Active cohorts</p>
    <p class="mt-1 font-display font-extrabold text-3xl"><?= (int) $kpi['cohorts'] ?></p>
    <p class="mt-1 text-sm text-slate-500"><?= (int) $kpi['startingSoon'] ?> starting in the next 14 days</p>
  </div>
  <div class="bg-white border rounded-lg p-5">
    <p class="text-sm text-slate-500">Students</p>
    <p class="mt-1 font-display font-extrabold text-3xl"><?= (int) $kpi['students'] ?></p>
    <p class="mt-1 text-sm text-slate-500">+<?= (int) $kpi['newStudents'] ?> new this week</p>
  </div>
</section>

<div class="mt-8 grid xl:grid-cols-5 gap-6">
  <div class="xl:col-span-3 space-y-6">
    <?php if ($teach): ?>
    <section class="bg-white border rounded-lg" aria-labelledby="up-h">
      <div class="flex items-center justify-between p-5 pb-3">
        <h2 id="up-h" class="font-display font-bold text-lg">Upcoming classes <?php if ($kpi['todayClasses']): ?><span class="badge badge-open ml-1"><?= (int) $kpi['todayClasses'] ?> today</span><?php endif; ?></h2>
        <a class="text-sm font-semibold text-bolt-dark underline" href="<?= e(url('/admin/sessions?when=upcoming')) ?>">All sessions</a>
      </div>
      <ul class="divide-y">
        <?php foreach ($upcoming as $s): $live = $s['starts_at'] <= utc_now(); ?>
          <li class="px-5 py-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-4 min-w-0">
              <div class="text-center w-14 shrink-0 rounded-md bg-bolt-tint py-1">
                <p class="text-xs uppercase font-bold text-bolt-dark"><?= e(local_date($s['starts_at'], $s['timezone'], 'M')) ?></p>
                <p class="font-display font-extrabold text-xl leading-none"><?= e(local_date($s['starts_at'], $s['timezone'], 'j')) ?></p></div>
              <div class="min-w-0"><p class="font-semibold truncate"><?= e($s['title']) ?> <?= $live ? '<span class="badge badge-open">In progress</span>' : '' ?></p>
                <p class="text-sm text-slate-600 truncate"><?= e($s['cohort_name']) ?> &middot; <?= e(local_date($s['starts_at'], $s['timezone'], 'g:i A')) ?> &middot; <?= (int) $s['students'] ?> student<?= $s['students'] == 1 ? '' : 's' ?></p></div>
            </div>
            <div class="flex items-center gap-3">
              <?= $s['meeting_url'] ? '<span class="text-sm text-green-700">Link set</span>' : '<span class="badge badge-closed">No link</span>' ?>
              <a class="underline text-bolt-dark text-sm font-semibold" href="<?= e(url('/admin/sessions/' . $s['id'] . '/edit')) ?>">Edit</a>
            </div>
          </li>
        <?php endforeach; if (!$upcoming): ?>
          <li class="px-5 py-8 text-center text-slate-500">No classes scheduled. <a class="underline text-bolt-dark" href="<?= e(url('/admin/sessions/create')) ?>">Add some</a>.</li>
        <?php endif; ?>
      </ul>
    </section>

    <section class="bg-white border rounded-lg p-5" aria-labelledby="fill-h">
      <div class="flex items-center justify-between"><h2 id="fill-h" class="font-display font-bold text-lg">Cohort seats</h2>
        <a class="text-sm font-semibold text-bolt-dark underline" href="<?= e(url('/admin/cohorts')) ?>">All cohorts</a></div>
      <ul class="mt-4 space-y-5">
        <?php foreach ($fill as $c):
          $cap = $c['capacity'] !== null ? (int) $c['capacity'] : null; $conf = (int) $c['confirmed']; $pen = (int) $c['pending'];
          $pc = $cap ? min(100, round(100 * $conf / $cap)) : 0; $pp = $cap ? min(100 - $pc, round(100 * $pen / $cap)) : 0; ?>
          <li>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <a class="font-semibold hover:underline" href="<?= e(url('/admin/cohorts/' . $c['id'])) ?>"><?= e($c['cohort_name']) ?></a>
              <span class="text-sm text-slate-600"><?= $conf ?> confirmed<?= $pen ? ' + ' . $pen . ' awaiting payment' : '' ?><?= (int) $c['waitlisted'] ? ' &middot; ' . (int) $c['waitlisted'] . ' waitlisted' : '' ?> &middot; <?= $cap ? $cap . ' seats' : 'unlimited' ?></span>
            </div>
            <?php if ($cap): ?>
              <div class="mt-2 h-2.5 w-full rounded-full bg-slate-200 overflow-hidden flex" role="img" aria-label="<?= $pc ?>% confirmed, <?= $pp ?>% awaiting payment">
                <div class="h-full bg-bolt" style="width:<?= $pc ?>%"></div><div class="h-full bg-volt" style="width:<?= $pp ?>%"></div></div>
            <?php endif; ?>
            <p class="mt-1 text-xs text-slate-500">Starts <?= e(local_date($c['start_at'], $c['timezone'], 'j M Y')) ?></p>
          </li>
        <?php endforeach; if (!$fill): ?><li class="text-slate-500">No active cohorts. <a class="underline text-bolt-dark" href="<?= e(url('/admin/cohorts/create')) ?>">Create one</a>.</li><?php endif; ?>
      </ul>
      <?php if ($fill): ?><p class="mt-4 text-xs text-slate-500 flex gap-4"><span><span class="inline-block w-3 h-3 rounded-sm bg-bolt align-middle"></span> Confirmed</span><span><span class="inline-block w-3 h-3 rounded-sm bg-volt align-middle"></span> Awaiting payment</span></p><?php endif; ?>
    </section>
    <?php endif; ?>
  </div>

  <div class="xl:col-span-2 space-y-6">
    <?php if ($money): ?>
    <section class="bg-white border rounded-lg p-5" aria-labelledby="rev-h">
      <h2 id="rev-h" class="font-display font-bold text-lg">Net revenue, last 6 months</h2>
      <div class="mt-4 flex items-end gap-2 h-40" role="img" aria-label="Monthly net revenue bar chart. Table below has the figures.">
        <?php $i = 0; $n = count($months); foreach ($months as $m => $v): $i++; $h = $v > 0 ? max(4, round(100 * $v / $maxMonth)) : 2; ?>
          <div class="flex-1 flex flex-col items-center justify-end h-full" title="<?= e(local_date($m . '-01 00:00:00', 'UTC', 'F Y') . ': ' . $fmt($v)) ?>">
            <div class="w-full rounded-t <?= $i === $n ? 'bg-bolt' : 'bg-bolt/40' ?>" style="height:<?= $h ?>%"></div></div>
        <?php endforeach; ?>
      </div>
      <div class="mt-1 flex gap-2 text-xs text-slate-500" aria-hidden="true"><?php foreach ($months as $m => $v): ?><span class="flex-1 text-center"><?= e(local_date($m . '-01 00:00:00', 'UTC', 'M')) ?></span><?php endforeach; ?></div>
      <table class="sr-only"><caption>Net revenue by month</caption><tbody><?php foreach ($months as $m => $v): ?><tr><th scope="row"><?= e($m) ?></th><td><?= e($fmt($v)) ?></td></tr><?php endforeach; ?></tbody></table>
      <a class="mt-3 inline-block text-sm font-semibold text-bolt-dark underline" href="<?= e(url('/admin/reports')) ?>">Full reports &amp; CSV export</a>
    </section>

    <section class="bg-white border rounded-lg" aria-labelledby="rec-h">
      <div class="flex items-center justify-between p-5 pb-3"><h2 id="rec-h" class="font-display font-bold text-lg">Recent registrations</h2>
        <a class="text-sm font-semibold text-bolt-dark underline" href="<?= e(url('/admin/enrolments')) ?>">View all</a></div>
      <ul class="divide-y">
        <?php foreach ($recent as $r): ?>
          <li><a class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-slate-50" href="<?= e(url('/admin/enrolments/' . $r['id'])) ?>">
            <span class="min-w-0"><span class="block font-semibold truncate"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></span>
              <span class="block text-sm text-slate-600 truncate"><?= e($r['cohort_name']) ?> &middot; <?= e(local_date($r['registered_at'], null, 'j M, g:i A')) ?></span></span>
            <span class="badge <?= $r['status'] === 'confirmed' ? 'badge-open' : ($r['status'] === 'pending_review' ? 'badge-warn' : '') ?>"><?= e(str_replace('_', ' ', $r['status'])) ?></span></a></li>
        <?php endforeach; if (!$recent): ?><li class="px-5 pb-6 text-slate-500">No registrations yet.</li><?php endif; ?>
      </ul>
    </section>
    <?php endif; ?>
  </div>
</div>
