<a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/students')) ?>">&larr; All students</a>
<h1 class="mt-1 text-3xl font-extrabold"><?= e($s['first_name'] . ' ' . $s['last_name']) ?> <span class="badge <?= $s['account_status'] === 'active' ? 'badge-open' : ($s['account_status'] === 'suspended' ? 'badge-closed' : '') ?>"><?= e(str_replace('_', ' ', $s['account_status'])) ?></span></h1>
<div class="mt-5 grid md:grid-cols-2 gap-6">
  <dl class="bg-white border rounded-lg p-5 space-y-2 text-sm">
    <div class="flex justify-between"><dt class="text-slate-500">Email</dt><dd><?= e($s['email']) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd><?= e($s['phone'] ?? 'Not given') ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Email verified</dt><dd><?= $s['email_verified_at'] ? e(local_date($s['email_verified_at'])) : 'No' ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Joined</dt><dd><?= e(local_date($s['created_at'])) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Last login</dt><dd><?= $s['last_login_at'] ? e(local_date($s['last_login_at'])) : 'Never' ?></dd></div>
  </dl>
  <?php if ($canEdit): ?>
  <div class="bg-white border rounded-lg p-5"><h2 class="font-bold text-xl">Account</h2>
    <div class="mt-3 flex flex-wrap gap-2">
      <?php foreach (['active' => 'Activate', 'suspended' => 'Suspend', 'closed' => 'Close account'] as $k => $l): if ($k === $s['account_status']) continue; ?>
        <form method="post" action="<?= e(url('/admin/students/' . $s['id'] . '/status')) ?>" onsubmit="return confirm('Change this account to <?= e($k) ?>?')"><?= csrf_field() ?>
          <input type="hidden" name="status" value="<?= e($k) ?>"><button class="btn btn-ghost !py-1.5" type="submit"><?= e($l) ?></button></form>
      <?php endforeach; ?></div>
    <p class="mt-3 text-sm text-slate-600">Suspended or closed students are signed out and cannot log in.</p></div>
  <?php endif; ?>
</div>
<h2 class="mt-8 text-xl font-bold">Enrolments</h2>
<div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Ref</th><th class="p-3">Cohort</th><th class="p-3">Status</th><th class="p-3">Paid</th><th class="p-3">Registered</th><th></th></tr></thead><tbody>
  <?php foreach ($enrolments as $e): ?>
    <tr class="border-t"><td class="p-3"><?= e($e['enrolment_number']) ?></td><td class="p-3"><?= e($e['course_title']) ?><br><span class="text-slate-500"><?= e($e['cohort_name']) ?></span></td>
      <td class="p-3"><span class="badge"><?= e(str_replace('_', ' ', $e['status'])) ?></span></td>
      <td class="p-3"><?= e(number_format((float) $e['amount_paid'], 2)) ?> / <?= e(number_format((float) $e['fee_amount'], 2)) ?> <?= e($e['currency']) ?></td>
      <td class="p-3"><?= e(local_date($e['registered_at'], null, 'j M Y')) ?></td>
      <td class="p-3"><a class="underline text-bolt-dark" href="<?= e(url('/admin/enrolments/' . $e['id'])) ?>">Open</a></td></tr>
  <?php endforeach; if (!$enrolments): ?><tr><td class="p-4 text-slate-500" colspan="6">No enrolments.</td></tr><?php endif; ?></tbody></table></div>
