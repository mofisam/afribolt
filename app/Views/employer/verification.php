<?php /** @var array $emp @var array $docs @var bool $editable @var array $missing @var array $labels @var array $sizes @var array $states */
$vs = $emp['verification_status'];
$statusText = ['unverified' => 'Not submitted yet', 'pending_review' => 'Under review', 'verified' => 'Verified', 'rejected' => 'Not approved'][$vs];
$ro = $editable ? '' : 'readonly';
?>
<h1 class="text-3xl font-extrabold">Company verification</h1>
<p class="text-slate-600">We check every company before it can post jobs, to keep job seekers safe from scams.</p>

<div class="mt-4 rounded-lg border p-4 <?= $vs === 'verified' ? 'bg-green-50 border-green-300' : ($vs === 'rejected' ? 'bg-red-50 border-red-300' : 'bg-white') ?>" role="status">
  <p>Status: <strong><?= e($statusText) ?></strong></p>
  <?php if (!empty($emp['review_note'])): ?><p class="mt-1 text-sm"><strong>Note from our team:</strong> <?= e($emp['review_note']) ?></p><?php endif; ?>
</div>

<section class="mt-6 bg-white border rounded-lg p-5" aria-labelledby="p-h">
  <h2 id="p-h" class="font-bold text-xl">1. Company profile</h2>
  <form method="post" action="<?= e(url('/employer/verification/profile')) ?>" class="mt-3 grid sm:grid-cols-2 gap-4" novalidate>
    <?= csrf_field() ?>
    <div><label class="label" for="company_name">Company name</label><input class="field" id="company_name" name="company_name" value="<?= e(old('company_name', $emp['company_name'])) ?>" <?= $ro ?> required><?= field_error('company_name') ?></div>
    <div><label class="label" for="rc">CAC / RC number</label><input class="field" id="rc" name="rc_number" value="<?= e(old('rc_number', $emp['rc_number'] ?? '')) ?>" placeholder="e.g. RC1234567" <?= $ro ?>><?= field_error('rc_number') ?></div>
    <div><label class="label" for="phone">Phone or WhatsApp</label><input class="field" id="phone" name="phone" value="<?= e(old('phone', $emp['phone'] ?? '')) ?>" required><?= field_error('phone') ?></div>
    <div><label class="label" for="website">Website (optional)</label><input class="field" type="url" id="website" name="website" value="<?= e(old('website', $emp['website'] ?? '')) ?>" placeholder="https://"><?= field_error('website') ?></div>
    <div><label class="label" for="industry">Industry</label><input class="field" id="industry" name="industry" value="<?= e(old('industry', $emp['industry'] ?? '')) ?>" placeholder="e.g. Logistics"><?= field_error('industry') ?></div>
    <div><label class="label" for="size">Company size</label><select class="field" id="size" name="company_size"><option value="">Choose</option><?php foreach ($sizes as $s): ?><option <?= ($emp['company_size'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <div class="sm:col-span-2"><label class="label" for="address">Business address</label><input class="field" id="address" name="address" value="<?= e(old('address', $emp['address'] ?? '')) ?>"><?= field_error('address') ?></div>
    <div><label class="label" for="state">State</label><select class="field" id="state" name="state"><option value="">Choose</option><?php foreach ($states as $s): ?><option <?= ($emp['state'] ?? '') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <div class="sm:col-span-2"><label class="label" for="desc">What does the company do?</label><textarea class="field" id="desc" name="description" rows="4" maxlength="1500"><?= e(old('description', $emp['description'] ?? '')) ?></textarea><?= field_error('description') ?></div>
    <div class="sm:col-span-2"><button class="btn btn-primary" type="submit">Save profile</button></div>
  </form>
</section>

<section class="mt-6 bg-white border rounded-lg p-5" aria-labelledby="d-h">
  <h2 id="d-h" class="font-bold text-xl">2. Documents</h2>
  <p class="text-sm text-slate-600">PDF, JPG or PNG, up to 5 MB each. Only our verification team can open them. They are never shown to the public or to students.</p>
  <?php if ($docs): ?>
    <ul class="mt-3 divide-y border rounded">
      <?php foreach ($docs as $d): ?>
        <li class="p-3 flex flex-wrap items-center justify-between gap-2 text-sm"><span><strong><?= e(explode(' (', $labels[$d['doc_type']])[0]) ?></strong><br><span class="text-slate-600"><?= e($d['original_name']) ?> &middot; <?= e(number_format($d['size_bytes'] / 1024)) ?> KB</span></span>
          <?php if ($editable): ?><form method="post" action="<?= e(url('/employer/verification/documents/' . (int) $d['id'] . '/delete')) ?>" onsubmit="return confirm('Remove this document?')"><?= csrf_field() ?><button class="underline text-red-700">Remove</button></form><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?><p class="mt-3 text-slate-600">No documents yet.</p><?php endif; ?>
  <?php if ($missing && $editable): ?><p class="mt-3 text-sm text-amber-800"><strong>Still needed:</strong> <?= e(implode(' and ', array_map(fn($m) => strtolower(explode(' (', $labels[$m])[0]), $missing))) ?>.</p><?php endif; ?>

  <?php if ($editable): ?>
  <form method="post" action="<?= e(url('/employer/verification/documents')) ?>" enctype="multipart/form-data" class="mt-4 grid sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
    <?= csrf_field() ?>
    <div><label class="label" for="doc_type">Document type</label><select class="field" id="doc_type" name="doc_type" required><?php foreach ($labels as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div><label class="label" for="document">File</label><input class="field" type="file" id="document" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required></div>
    <button class="btn btn-ghost" type="submit">Upload</button>
  </form>
  <?php endif; ?>
</section>

<?php if ($editable): ?>
<section class="mt-6 bg-white border rounded-lg p-5" aria-labelledby="s-h">
  <h2 id="s-h" class="font-bold text-xl">3. Submit for review</h2>
  <p class="text-slate-700">When your profile and the two required documents are ready, send them to our team. Reviews usually take a few working days. Once submitted you cannot edit the documents until we reply.</p>
  <form method="post" action="<?= e(url('/employer/verification/submit')) ?>" class="mt-3" onsubmit="return confirm('Submit your company for verification?')"><?= csrf_field() ?><button class="btn btn-accent" type="submit"><?= $vs === 'rejected' ? 'Resubmit for review' : 'Submit for verification' ?></button></form>
</section>
<?php endif; ?>
