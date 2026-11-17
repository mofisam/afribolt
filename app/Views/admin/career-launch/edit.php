<?php /** @var array $reg */ ?>
<div class="max-w-xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/career-launch')) ?>">&larr; Career Launch registrations</a>
  <h1 class="mt-1 text-3xl font-extrabold">Payment link</h1>
  <p class="text-slate-600"><strong><?= e($reg['full_name']) ?></strong> &middot; <?= e($reg['email']) ?> &middot; <?= e($reg['phone']) ?></p>
  <p class="text-slate-600">Target role: <?= e($reg['target_role']) ?><?= !empty($reg['user_id']) ? '' : ' &middot; <span class="text-red-700">No student account linked yet, so they cannot see the link on a dashboard.</span>' ?></p>

  <form method="post" action="<?= e(url('/admin/career-launch/' . (int) $reg['id'])) ?>" class="mt-5 bg-white border rounded-lg p-5 space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="payment_link">Payment link</label>
      <input class="field" type="url" id="payment_link" name="payment_link" value="<?= e(old('payment_link', $reg['payment_link'] ?? '')) ?>" placeholder="https://paystack.com/pay/..."><?= field_error('payment_link') ?>
      <p class="mt-1 text-sm text-slate-600">Must start with https://. When you save a new link the student gets an email, a notification, and a <strong>Pay now</strong> button on their dashboard. Clear the box to remove the link.</p></div>
    <div><label class="label" for="admin_note">Internal note (students never see this)</label>
      <textarea class="field" id="admin_note" name="admin_note" rows="3" maxlength="500"><?= e(old('admin_note', $reg['admin_note'] ?? '')) ?></textarea><?= field_error('admin_note') ?></div>
    <div class="flex flex-wrap gap-3"><button class="btn btn-primary" type="submit">Save</button><a class="btn btn-ghost" href="<?= e(url('/admin/career-launch')) ?>">Cancel</a></div>
  </form>
</div>
