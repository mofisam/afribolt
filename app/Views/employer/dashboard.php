<?php /** @var array $emp @var int $live @var int $waiting @var int $applicants @var int $fresh */
$vs = $emp['verification_status'];
?>
<h1 class="text-3xl font-extrabold">Welcome, <?= e($emp['contact_first_name']) ?></h1>
<p class="text-slate-600"><?= e($emp['company_name']) ?></p>

<?php if ($vs === 'verified'): ?>
  <div class="mt-5 rounded-lg border border-green-300 bg-green-50 p-4 flex flex-wrap items-center justify-between gap-3" role="status">
    <p><strong class="text-green-800">&#10003; Verified company.</strong> You can post jobs whenever you have a vacancy.</p>
    <a class="btn btn-primary !py-2" href="<?= e(url('/employer/jobs/create')) ?>">Post a job</a>
  </div>
<?php elseif ($vs === 'pending_review'): ?>
  <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4" role="status"><p><strong>Under review.</strong> We received your documents on <?= e(local_date($emp['submitted_at'], null, 'j M Y')) ?>. We will email you as soon as the review is done. You can post jobs once you are verified.</p></div>
<?php else: ?>
  <div class="mt-5 rounded-lg border <?= $vs === 'rejected' ? 'border-red-300 bg-red-50' : 'border-amber-300 bg-amber-50' ?> p-4" role="status">
    <p><strong><?= $vs === 'rejected' ? 'Verification was not approved.' : 'Verification needed.' ?></strong> <?= $vs === 'rejected' ? 'Read the note, fix the problem and submit again.' : 'Complete your company profile and upload your documents to start posting jobs.' ?></p>
    <?php if (!empty($emp['review_note'])): ?><p class="mt-2 text-sm"><strong>Note from our team:</strong> <?= e($emp['review_note']) ?></p><?php endif; ?>
    <a class="btn btn-primary !py-2 mt-3" href="<?= e(url('/employer/verification')) ?>"><?= $vs === 'rejected' ? 'Update and resubmit' : 'Continue verification' ?></a>
  </div>
<?php endif; ?>

<div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
  <?php foreach ([['Live jobs', $live], ['Waiting for review', $waiting], ['Applicants', $applicants], ['New applicants', $fresh]] as [$l, $n]): ?>
    <div class="bg-white border rounded-lg p-4"><p class="text-3xl font-extrabold"><?= (int) $n ?></p><p class="text-sm text-slate-600"><?= e($l) ?></p></div>
  <?php endforeach; ?>
</div>
<p class="mt-6"><a class="underline text-bolt-dark font-bold" href="<?= e(url('/employer/jobs')) ?>">Manage my jobs and applicants &rarr;</a></p>
