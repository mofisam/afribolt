<?php use App\Core\View; ?>
<div class="flex flex-wrap items-end justify-between gap-3">
  <div><h1 class="text-3xl font-extrabold">Jobs for you</h1><p class="text-slate-600">Picked from fresh Nigerian openings using your interests and what you are learning.</p></div>
  <div class="flex gap-2"><a class="btn btn-ghost !py-2" href="<?= e(url('/student/jobs/preferences')) ?>">Job preferences</a><a class="btn btn-primary !py-2" href="<?= e(url('/jobs')) ?>">Browse all jobs</a></div>
</div>
<nav class="mt-5 flex gap-1 border-b" aria-label="Jobs">
  <a class="pl-tab" href="<?= e(url('/student/jobs')) ?>" <?= $tab === 'foryou' ? 'aria-selected="true"' : '' ?>>For you</a>
  <a class="pl-tab" href="<?= e(url('/student/jobs?tab=saved')) ?>" <?= $tab === 'saved' ? 'aria-selected="true"' : '' ?>>Saved (<?= count($saved) ?>)</a>
</nav>

<?php if ($tab === 'saved'): ?>
  <?php if (!$saved): ?><div class="mt-6 bg-white border rounded-lg p-8 text-center text-slate-600">You haven't saved any jobs yet. Tap the heart on a job to keep it here.</div><?php endif; ?>
  <div class="mt-5 space-y-3"><?php foreach ($saved as $j): ?><?= View::partial('partials/job-card', ['job' => $j, 'saved' => true]) ?><?php endforeach; ?></div>
<?php else: ?>
  <?php if ($profile['empty']): ?>
    <div class="mt-6 rounded-lg bg-bolt-tint p-6"><h2 class="font-display font-bold text-xl">Tell us what you're looking for</h2>
      <p class="mt-1 text-slate-700">Pick a few interests and skills, or start a course, and we'll recommend jobs that fit. Until then, here are the newest jobs.</p>
      <a class="btn btn-primary mt-3" href="<?= e(url('/student/jobs/preferences')) ?>">Set my preferences</a></div>
  <?php elseif (!$profile['prefs']['saved']): ?>
    <p class="mt-4 text-sm bg-bolt-tint rounded p-3">These come from the courses you are taking. <a class="underline font-bold text-bolt-dark" href="<?= e(url('/student/jobs/preferences')) ?>">Add your preferences</a> for sharper matches.</p>
  <?php endif; ?>
  <?php if (!$recs): ?><div class="mt-6 bg-white border rounded-lg p-8 text-center"><p class="font-bold text-lg">No strong matches right now.</p><p class="text-slate-600 mt-1">New jobs arrive daily. Try adding more interests or <a class="underline text-bolt-dark" href="<?= e(url('/jobs')) ?>">browse every job</a>.</p></div><?php endif; ?>
  <div class="mt-5 space-y-3"><?php foreach ($recs as $j): ?><?= View::partial('partials/job-card', ['job' => $j, 'rec' => $profile['empty'] ? [] : $j, 'saved' => in_array((int) $j['id'], $savedIds, true)]) ?><?php endforeach; ?></div>
  <?php if ($recs && !$profile['empty']): ?><p class="mt-4 text-xs text-slate-500">Matches are scored from your interests and course topics. Short explanations are written by AI using only that information, never your name or email.</p><?php endif; ?>
<?php endif; ?>
