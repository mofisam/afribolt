<a class="text-sm underline text-bolt-dark" href="<?= e(url('/instructor')) ?>">&larr; My classes</a>
<div class="mt-1 flex flex-wrap items-start justify-between gap-3">
  <div><h1 class="text-3xl font-extrabold"><?= e($cohort['cohort_name']) ?></h1>
    <p class="text-slate-600"><?= e($cohort['course_title']) ?> &middot; starts <?= e(local_date($cohort['start_at'], $cohort['timezone'])) ?></p></div>
  <div class="flex flex-wrap gap-2">
    <a class="btn btn-primary !py-1.5" href="<?= e(url('/instructor/announcements?cohort=' . $cohort['id'])) ?>">Message students</a>
    <a class="btn btn-ghost !py-1.5" href="<?= e(url('/instructor/cohorts/' . $cohort['id'] . '/roster.csv')) ?>">Export roster (CSV)</a>
  </div>
</div>

<h2 class="mt-8 text-xl font-bold">Sessions</h2>
<ul class="mt-3 divide-y bg-white border rounded-lg">
  <?php foreach ($sessions as $s): $open = in_array($s['status'], ['scheduled', 'live'], true); ?>
    <li class="p-4 flex flex-wrap items-center justify-between gap-3">
      <div><p class="font-semibold"><?= e($s['title']) ?> <span class="badge <?= $s['status'] === 'live' ? 'badge-open' : '' ?>"><?= e($s['status']) ?></span>
          <?php if ($open && !$s['meeting_url']): ?><span class="badge badge-warn">No link</span><?php endif; ?></p>
        <p class="text-sm text-slate-600"><?= e(local_date($s['starts_at'], $s['timezone'])) ?></p></div>
      <div class="flex flex-wrap gap-2 items-center">
        <?php if ($open && $s['meeting_url']): ?><a class="btn btn-primary !py-1.5" target="_blank" rel="noopener" href="<?= e($s['meeting_url']) ?>">Open meeting</a><?php endif; ?>
        <a class="btn btn-ghost !py-1.5" href="<?= e(url('/instructor/sessions/' . $s['id'] . '/edit')) ?>">Meeting details</a>
        <a class="btn btn-ghost !py-1.5" href="<?= e(url('/instructor/sessions/' . $s['id'] . '/attendance')) ?>">Attendance</a>
        <?php if ($open): foreach (($s['status'] === 'scheduled' ? ['live' => 'Start class'] : []) + ['completed' => 'Finish class'] as $st => $label): ?>
          <form method="post" action="<?= e(url('/instructor/sessions/' . $s['id'] . '/status')) ?>"><?= csrf_field() ?><input type="hidden" name="status" value="<?= $st ?>"><button class="btn btn-ghost !py-1.5" type="submit"><?= e($label) ?></button></form>
        <?php endforeach; endif; ?>
      </div>
    </li>
  <?php endforeach; if (!$sessions): ?><li class="p-4 text-slate-500">No sessions scheduled. Ask an admin to add them.</li><?php endif; ?>
</ul>
<p class="mt-2 text-sm text-slate-500">Need to change a class time or cancel a class? Ask an admin so students are notified correctly.</p>

<h2 class="mt-8 text-xl font-bold">Students (<?= count($students) ?>)</h2>
<div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Name</th><th class="p-3">Email</th><th class="p-3">Attendance</th></tr></thead><tbody>
  <?php foreach ($students as $u): $rate = (int) $u['held'] > 0 ? round(100 * (int) $u['attended'] / (int) $u['held']) : null; ?>
    <tr class="border-t"><td class="p-3"><?= e($u['first_name'] . ' ' . $u['last_name']) ?></td><td class="p-3"><?= e($u['email']) ?></td>
      <td class="p-3"><?= (int) $u['attended'] ?> of <?= (int) $u['held'] ?> held<?= $rate !== null ? ' (' . $rate . '%)' : '' ?></td></tr>
  <?php endforeach; if (!$students): ?><tr><td class="p-4 text-slate-500" colspan="3">No confirmed students yet.</td></tr><?php endif; ?></tbody></table></div>
