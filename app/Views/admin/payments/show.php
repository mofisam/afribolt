<a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/payments')) ?>">&larr; All payments</a>
<h1 class="mt-1 text-3xl font-extrabold"><?= e($p['internal_reference']) ?> <span class="badge"><?= e(str_replace('_', ' ', $p['status'])) ?></span></h1>
<div class="mt-5 grid md:grid-cols-2 gap-6">
  <dl class="bg-white border rounded-lg p-5 space-y-2 text-sm">
    <div class="flex justify-between"><dt class="text-slate-500">Student</dt><dd><?= e($p['first_name'] . ' ' . $p['last_name']) ?> (<?= e($p['email']) ?>)</dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Cohort</dt><dd><?= e($p['cohort_name']) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500"><?= $p['enrolment_id'] ? 'Enrolment' : 'Purchase' ?></dt><dd><?php if ($p['enrolment_id']): ?><a class="underline text-bolt-dark" href="<?= e(url('/admin/enrolments/' . $p['enrolment_id'])) ?>"><?= e($p['enrolment_number']) ?></a><?php else: ?><a class="underline text-bolt-dark" href="<?= e(url('/admin/learners')) ?>">Self-paced course</a><?php endif; ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Amount</dt><dd><?= e(number_format((float) $p['amount'], 2)) ?> <?= e($p['currency']) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Refunded</dt><dd><?= e(number_format((float) $p['refunded_amount'], 2)) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Gateway</dt><dd><?= e($p['gateway_name'] ?? 'n/a') ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Provider ref</dt><dd><?= e($p['provider_reference'] ?? '') ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Method</dt><dd><?= e($p['payment_method'] ?? '') ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Created</dt><dd><?= e(local_date($p['created_at'])) ?></dd></div>
    <div class="flex justify-between"><dt class="text-slate-500">Paid</dt><dd><?= $p['paid_at'] ? e(local_date($p['paid_at'])) : 'Not paid' ?></dd></div>
    <?php if ($p['failure_reason']): ?><div class="flex justify-between"><dt class="text-slate-500">Failure</dt><dd class="text-red-700"><?= e($p['failure_reason']) ?></dd></div><?php endif; ?>
  </dl>
  <div class="space-y-6">
    <?php if (!in_array($p['status'], ['successful', 'refunded', 'partially_refunded'], true)): ?>
    <div class="bg-white border rounded-lg p-5"><h2 class="font-bold text-xl">Re-check with provider</h2>
      <p class="mt-1 text-sm text-slate-600">Use if the student says they paid but the seat wasn't confirmed. We ask the provider directly; nothing is confirmed unless they confirm it.</p>
      <form method="post" action="<?= e(url('/admin/payments/' . $p['id'] . '/verify')) ?>" class="mt-3"><?= csrf_field() ?><button class="btn btn-primary" type="submit">Verify now</button></form></div>
    <?php endif; ?>
    <?php if ($canRefund && in_array($p['status'], ['successful', 'partially_refunded'], true)): ?>
    <div class="bg-white border rounded-lg p-5"><h2 class="font-bold text-xl">Record a refund</h2>
      <p class="mt-1 text-sm text-slate-600">This only <strong>records</strong> a refund you have already paid out (bank transfer or provider dashboard). It does not move money.</p>
      <form method="post" action="<?= e(url('/admin/payments/' . $p['id'] . '/refund')) ?>" class="mt-3 space-y-3" onsubmit="return confirm('Record this refund?')"><?= csrf_field() ?>
        <div><label class="label" for="amount">Amount (max <?= e(number_format((float) $p['amount'] - (float) $p['refunded_amount'], 2)) ?>)</label><input class="field" id="amount" name="amount" type="number" step="0.01" min="0.01" required></div>
        <div><label class="label" for="note">Note / refund reference</label><input class="field" id="note" name="note" required></div>
        <button class="btn btn-ghost" type="submit">Record refund</button></form></div>
    <?php endif; ?>
  </div>
</div>
<?php if ($json): ?><h2 class="mt-8 text-xl font-bold">Provider response</h2>
<pre class="mt-3 bg-white border rounded-lg p-4 text-xs overflow-x-auto"><?= e($json) ?></pre><?php endif; ?>
