<?php /** @var array $jobs @var array $emp @var bool $approval */
$tone = ['published' => 'badge-open', 'pending' => '', 'hidden' => 'badge-closed', 'rejected' => 'badge-closed', 'expired' => 'badge-closed'];
$label = ['published' => 'Live', 'pending' => 'In review', 'hidden' => 'Closed', 'rejected' => 'Not approved', 'expired' => 'Expired'];
?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div><h1 class="text-3xl font-extrabold">My jobs</h1><p class="text-slate-600">Post vacancies and manage the people who apply.</p></div>
  <?php if ($emp['verification_status'] === 'verified'): ?><a class="btn btn-primary" href="<?= e(url('/employer/jobs/create')) ?>">Post a job</a><?php endif; ?>
</div>
<?php if ($emp['verification_status'] !== 'verified'): ?>
  <p class="mt-4 rounded-lg border border-amber-300 bg-amber-50 p-4">You can post jobs after your company is verified. <a class="underline font-bold" href="<?= e(url('/employer/verification')) ?>">Go to verification</a>.</p>
<?php endif; ?>
<?php if (!$jobs): ?>
  <p class="mt-6 text-slate-600">You have not posted any jobs yet.</p>
<?php else: ?>
  <ul class="mt-6 space-y-3">
    <?php foreach ($jobs as $j): ?>
      <li class="bg-white border rounded-lg p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="font-bold text-lg"><?= e($j['title']) ?> <span class="badge <?= $tone[$j['status']] ?? '' ?>"><?= e($label[$j['status']] ?? $j['status']) ?></span></p>
          <p class="text-sm text-slate-600"><?= e($j['location_text'] ?: 'Location not set') ?> &middot; <?= (int) $j['views'] ?> views &middot; <strong><?= (int) $j['applicants'] ?></strong> applicant<?= (int) $j['applicants'] === 1 ? '' : 's' ?><?= (int) $j['fresh'] ? ' (<strong>' . (int) $j['fresh'] . ' new</strong>)' : '' ?><?= $j['deadline_at'] ? ' &middot; closes ' . e(local_date($j['deadline_at'], null, 'j M Y')) : '' ?></p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
          <a class="btn btn-ghost !py-1.5" href="<?= e(url('/employer/jobs/' . (int) $j['id'] . '/applicants')) ?>">Applicants</a>
          <?php if ($emp['verification_status'] === 'verified'): ?>
            <a class="underline text-bolt-dark font-bold" href="<?= e(url('/employer/jobs/' . (int) $j['id'] . '/edit')) ?>">Edit</a>
            <?php if (in_array($j['status'], ['published', 'hidden'], true)): ?>
              <form method="post" action="<?= e(url('/employer/jobs/' . (int) $j['id'] . '/toggle')) ?>" <?= $j['status'] === 'published' ? 'onsubmit="return confirm(\'Close this job? Students will no longer see it.\')"' : '' ?>><?= csrf_field() ?><button class="underline <?= $j['status'] === 'published' ? 'text-red-700' : 'text-bolt-dark' ?> font-bold"><?= $j['status'] === 'published' ? 'Close' : 'Reopen' ?></button></form>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($j['status'] === 'published'): ?><a class="underline" href="<?= e(url('/jobs/' . $j['slug'])) ?>" target="_blank" rel="noopener">View</a><?php endif; ?>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>
