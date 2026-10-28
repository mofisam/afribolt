<a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/enrolments')) ?>">&larr; All enrolments</a>
<h1 class="mt-1 text-3xl font-extrabold"><?= e($e['enrolment_number']) ?> <span class="badge"><?= e(str_replace('_', ' ', $e['status'])) ?></span></h1>
<div class="mt-5 grid md:grid-cols-2 gap-6">
  <dl class="bg-white border rounded-lg p-5 space-y-2 text-sm">
    <div class="flex justify-between"><dt class="text-slate-500">Student</dt><dd><a class="underline text-bolt-dark" href="<?= e(url('/admin/students/' . $e['student_id'])) ?>"><?= e($e['first_name'] . ' ' . $e['last_name']) ?></a></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Contact</dt><dd><?= e($e['email']) ?><?= $e['phone'] ? ' &middot; ' . e($e['phone']) : '' ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Cohort</dt><dd><a class="underline text-bolt-dark" href="<?= e(url('/admin/cohorts/' . $e['cohort_id'])) ?>"><?= e($e['cohort_name']) ?></a></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Course</dt><dd><?= e($e['course_title']) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Fee / paid</dt><dd><?= e(number_format((float) $e['fee_amount'], 2)) ?> / <?= e(number_format((float) $e['amount_paid'], 2)) ?> <?= e($e['currency']) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Registered</dt><dd><?= e(local_date($e['registered_at'])) ?></dd></div>
    <?php if ($e['confirmed_at']): ?><div class="flex justify-between"><dt class="text-slate-500">Confirmed</dt><dd><?= e(local_date($e['confirmed_at'])) ?></dd></div><?php endif; ?>
  </dl>
  <div class="bg-white border rounded-lg p-5">
    <h2 class="font-bold text-xl">Actions</h2>
    <form method="post" action="<?= e(url('/admin/enrolments/' . $e['id'] . '/action')) ?>" class="mt-3 space-y-3">
      <?= csrf_field() ?>
      <div><label class="label" for="note">Note <span class="font-normal text-slate-500">(required for manual confirmation)</span></label>
        <textarea class="field" id="note" name="note" rows="2" placeholder="e.g. Bank transfer ref 12345 received"></textarea></div>
      <div class="flex flex-wrap gap-2">
        <?php $btns = ['note' => ['Save note', 'btn-ghost']];
        if (in_array($e['status'], ['pending_payment', 'pending_review', 'waitlisted', 'cancelled'], true) && $e['status'] !== 'waitlisted') { $btns['confirm'] = ['Confirm manually', 'btn-primary']; }
        if ($e['status'] === 'pending_review') { $btns['confirm'] = ['Confirm manually', 'btn-primary']; }
        if ($e['status'] === 'waitlisted') { $btns['promote'] = ['Promote from waitlist', 'btn-primary']; }
        if (in_array($e['status'], ['pending_payment', 'pending_review', 'confirmed', 'waitlisted'], true)) { $btns['cancel'] = ['Cancel enrolment', 'btn-ghost']; }
        if ($e['status'] === 'confirmed') { $btns['complete'] = ['Mark completed', 'btn-ghost']; }
        foreach ($btns as $act => [$label, $cls]): ?>
          <button class="btn <?= $cls ?> !py-1.5" type="submit" name="action" value="<?= e($act) ?>" <?= in_array($act, ['cancel'], true) ? 'onclick="return confirm(\'Cancel this enrolment and release the seat?\')"' : '' ?>><?= e($label) ?></button>
        <?php endforeach; ?>
      </div>
    </form>
  </div>
</div>
<h2 class="mt-8 text-xl font-bold">Payments</h2>
<div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Reference</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Paid at</th><th></th></tr></thead><tbody>
  <?php foreach ($payments as $p): ?>
    <tr class="border-t"><td class="p-3"><?= e($p['internal_reference']) ?></td><td class="p-3"><?= e(number_format((float) $p['amount'], 2)) ?> <?= e($p['currency']) ?></td>
      <td class="p-3"><span class="badge"><?= e(str_replace('_', ' ', $p['status'])) ?></span></td><td class="p-3"><?= $p['paid_at'] ? e(local_date($p['paid_at'])) : '' ?></td>
      <td class="p-3"><a class="underline text-bolt-dark" href="<?= e(url('/admin/payments/' . $p['id'])) ?>">Open</a></td></tr>
  <?php endforeach; if (!$payments): ?><tr><td class="p-4 text-slate-500" colspan="5">No payment attempts.</td></tr><?php endif; ?></tbody></table></div>
<?php if (!empty($e['admin_notes'])): ?>
<h2 class="mt-8 text-xl font-bold">History &amp; notes</h2>
<pre class="mt-3 bg-white border rounded-lg p-4 text-sm whitespace-pre-wrap"><?= e(trim($e['admin_notes'])) ?></pre>
<?php endif; ?>
