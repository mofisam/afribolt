<h1 class="text-3xl font-extrabold">Reports</h1>
<form method="get" class="mt-5 bg-white border rounded-lg p-4 flex flex-wrap gap-3 items-end">
  <div><label class="label text-sm" for="from">From</label><input class="field" type="date" id="from" name="from" value="<?= e($from) ?>"></div>
  <div><label class="label text-sm" for="to">To</label><input class="field" type="date" id="to" name="to" value="<?= e($to) ?>"></div>
  <button class="btn btn-primary" type="submit">Apply</button>
  <a class="btn btn-ghost" href="<?= e(url('/admin/reports/export?type=enrolments&from=' . $from . '&to=' . $to)) ?>">Export enrolments (CSV)</a>
  <a class="btn btn-ghost" href="<?= e(url('/admin/reports/export?type=payments&from=' . $from . '&to=' . $to)) ?>">Export payments (CSV)</a>
</form>
<div class="mt-6 grid lg:grid-cols-2 gap-6">
  <section class="bg-white border rounded-lg p-5"><h2 class="font-bold text-xl">Registrations by status</h2>
    <table class="mt-3 w-full text-sm"><tbody>
    <?php foreach ($byStatus as $r): ?><tr class="border-t"><td class="py-2"><?= e(str_replace('_', ' ', $r['status'])) ?></td><td class="py-2 text-right font-bold"><?= (int) $r['n'] ?></td></tr>
    <?php endforeach; if (!$byStatus): ?><tr><td class="py-2 text-slate-500">No registrations in this period.</td></tr><?php endif; ?></tbody></table></section>
  <section class="bg-white border rounded-lg p-5"><h2 class="font-bold text-xl">Revenue by month (by payment date)</h2>
    <table class="mt-3 w-full text-sm"><thead class="text-left text-slate-500"><tr><th>Month</th><th>Payments</th><th class="text-right">Gross</th><th class="text-right">Refunded</th><th class="text-right">Net</th></tr></thead><tbody>
    <?php foreach ($revenue as $r): ?><tr class="border-t"><td class="py-2"><?= e($r['month']) ?></td><td><?= (int) $r['payments'] ?></td>
      <td class="text-right"><?= e(number_format((float) $r['gross'], 2)) ?></td><td class="text-right"><?= e(number_format((float) $r['refunded'], 2)) ?></td>
      <td class="text-right font-bold"><?= e(number_format((float) $r['gross'] - (float) $r['refunded'], 2)) ?> <?= e($r['currency']) ?></td></tr>
    <?php endforeach; if (!$revenue): ?><tr><td class="py-2 text-slate-500" colspan="5">No payments in this period.</td></tr><?php endif; ?></tbody></table></section>
</div>
<section class="mt-6 bg-white border rounded-lg p-5 overflow-x-auto"><h2 class="font-bold text-xl">Cohort fill rate</h2>
  <table class="mt-3 w-full text-sm"><thead class="text-left text-slate-500"><tr><th class="py-1">Cohort</th><th>Starts</th><th>Status</th><th>Confirmed</th><th>Awaiting payment</th><th>Waitlist</th><th>Capacity</th><th>Fill</th></tr></thead><tbody>
  <?php foreach ($cohorts as $c): $fill = $c['capacity'] ? round(100 * (int) $c['confirmed'] / (int) $c['capacity']) : null; ?>
    <tr class="border-t"><td class="py-2"><?= e($c['cohort_name']) ?></td><td><?= e(local_date($c['start_at'], $c['timezone'], 'j M Y')) ?></td><td><?= e(str_replace('_', ' ', $c['status'])) ?></td>
      <td><?= (int) $c['confirmed'] ?></td><td><?= (int) $c['pending'] ?></td><td><?= (int) $c['waitlisted'] ?></td><td><?= $c['capacity'] ?? '∞' ?></td>
      <td><?= $fill === null ? 'n/a' : (int) $fill . '%' ?></td></tr>
  <?php endforeach; if (!$cohorts): ?><tr><td class="py-2 text-slate-500" colspan="8">No cohorts.</td></tr><?php endif; ?></tbody></table></section>
