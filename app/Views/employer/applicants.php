<?php /** @var array $job @var array $apps @var array $statuses */
$tone = ['submitted' => '', 'viewed' => '', 'shortlisted' => 'badge-open', 'interview' => 'badge-open', 'hired' => 'badge-open', 'rejected' => 'badge-closed'];
?>
<a class="text-sm underline text-bolt-dark" href="<?= e(url('/employer/jobs')) ?>">&larr; My jobs</a>
<h1 class="mt-1 text-3xl font-extrabold">Applicants</h1>
<p class="text-slate-600"><?= e($job['title']) ?> &middot; <?= count($apps) ?> applicant<?= count($apps) === 1 ? '' : 's' ?></p>

<?php if (!$apps): ?><p class="mt-6 text-slate-600">No one has applied yet. Share the job link to reach more people.</p><?php endif; ?>
<ul class="mt-6 space-y-4">
  <?php foreach ($apps as $a): ?>
    <li class="bg-white border rounded-lg p-4">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div><p class="font-bold text-lg"><?= e($a['first_name'] . ' ' . $a['last_name']) ?> <span class="badge <?= $tone[$a['status']] ?? '' ?>"><?= e($statuses[$a['status']]) ?></span></p>
          <p class="text-sm text-slate-600"><a class="underline" href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a><?= $a['phone'] ? ' &middot; ' . e($a['phone']) : '' ?> &middot; applied <?= e(local_date($a['created_at'], null, 'j M Y, g:i A')) ?></p></div>
        <?php if ($a['cv_stored_name']): ?><a class="btn btn-ghost !py-1.5 text-sm" href="<?= e(url('/employer/applications/' . (int) $a['id'] . '/cv')) ?>">Download CV</a><?php endif; ?>
      </div>
      <?php if (trim((string) $a['cover_note']) !== ''): ?><details class="mt-3"><summary class="cursor-pointer font-semibold underline text-bolt-dark">Cover note</summary><p class="mt-2 whitespace-pre-line"><?= e($a['cover_note']) ?></p></details><?php endif; ?>
      <form method="post" action="<?= e(url('/employer/applications/' . (int) $a['id'] . '/status')) ?>" class="mt-3 grid sm:grid-cols-[10rem_1fr_auto] gap-3 items-end">
        <?= csrf_field() ?>
        <div><label class="label" for="st<?= (int) $a['id'] ?>">Status</label><select class="field" id="st<?= (int) $a['id'] ?>" name="status"><?php foreach ($statuses as $k => $l): ?><option value="<?= e($k) ?>" <?= $a['status'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        <div><label class="label" for="nt<?= (int) $a['id'] ?>">Message to the applicant (optional, they will see it)</label><input class="field" id="nt<?= (int) $a['id'] ?>" name="employer_note" maxlength="1000" value="<?= e($a['employer_note'] ?? '') ?>"></div>
        <button class="btn btn-primary !py-2" type="submit">Update</button>
      </form>
    </li>
  <?php endforeach; ?>
</ul>
