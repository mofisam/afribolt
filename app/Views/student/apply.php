<?php /** @var array $job @var array $student */ ?>
<div class="max-w-2xl mx-auto">
  <a class="text-sm underline text-bolt-dark" href="<?= e(url('/jobs/' . $job['slug'])) ?>">&larr; Back to the job</a>
  <h1 class="mt-1 text-3xl font-extrabold">Apply for this job</h1>
  <p class="text-lg"><strong><?= e($job['title']) ?></strong> &middot; <?= e($job['company']) ?></p>

  <form method="post" action="<?= e(url('/student/jobs/' . $job['slug'] . '/apply')) ?>" enctype="multipart/form-data" class="mt-5 bg-white border rounded-lg p-5 space-y-4" novalidate>
    <?= csrf_field() ?>
    <div class="rounded-lg bg-bolt-tint p-4 text-sm">
      <p class="font-bold">The employer will see:</p>
      <p><?= e($student['first_name'] . ' ' . $student['last_name']) ?> &middot; <?= e($student['email']) ?><?= !empty($student['phone']) ? ' &middot; ' . e($student['phone']) : '' ?></p>
      <p class="mt-1"><a class="underline text-bolt-dark" href="<?= e(url('/student/profile')) ?>">Update your details</a> if anything is wrong.</p>
    </div>
    <div><label class="label" for="cover_note">Short note to the employer</label>
      <textarea class="field" id="cover_note" name="cover_note" rows="6" maxlength="2000" placeholder="Tell them why you are a good fit&hellip;"><?= e(old('cover_note')) ?></textarea><?= field_error('cover_note') ?></div>
    <div><label class="label" for="cv">Your CV (optional, PDF or Word, up to 3 MB)</label>
      <input class="field" type="file" id="cv" name="cv" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
      <p class="mt-1 text-sm text-slate-600">Add a note, a CV, or both. Only this employer can download your CV.</p></div>
    <div class="flex flex-wrap gap-3"><button class="btn btn-accent" type="submit">Send application</button><a class="btn btn-ghost" href="<?= e(url('/jobs/' . $job['slug'])) ?>">Cancel</a></div>
    <p class="text-sm text-slate-600"><strong>Stay safe:</strong> a real employer never asks you to pay to apply, interview or start work.</p>
  </form>
</div>
