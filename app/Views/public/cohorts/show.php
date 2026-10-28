<?php $tz = $cohort['timezone']; $future = $cohort['start_at'] > utc_now(); ?>
<section class="max-w-5xl mx-auto px-4 py-12 grid lg:grid-cols-3 gap-10">
  <div class="lg:col-span-2">
    <a class="text-bolt-dark underline" href="<?= e(url('/courses/' . $cohort['course_slug'])) ?>"><?= e($cohort['course_title']) ?></a>
    <h1 class="mt-1 text-4xl font-extrabold"><?= e($cohort['cohort_name']) ?></h1>
    <?php if ($cohort['summary']): ?><p class="mt-3 text-lg"><?= e($cohort['summary']) ?></p><?php endif; ?>

    <dl class="mt-6 grid sm:grid-cols-2 gap-4">
      <div><dt class="text-slate-500 text-sm">Starts</dt><dd class="font-bold"><?= e(local_date($cohort['start_at'], $tz)) ?></dd></div>
      <?php if ($cohort['end_at']): ?><div><dt class="text-slate-500 text-sm">Ends</dt><dd class="font-bold"><?= e(local_date($cohort['end_at'], $tz)) ?></dd></div><?php endif; ?>
      <div><dt class="text-slate-500 text-sm">Fee</dt><dd class="font-bold"><?= e(money($cohort['fee_amount'], $cohort['currency'])) ?></dd></div>
      <?php if ($cohort['registration_closes_at']): ?><div><dt class="text-slate-500 text-sm">Register by</dt><dd class="font-bold"><?= e(local_date($cohort['registration_closes_at'], $tz)) ?></dd></div><?php endif; ?>
      <?php if ($seatsLeft !== null): ?><div><dt class="text-slate-500 text-sm">Seats left</dt><dd class="font-bold"><?= (int) $seatsLeft ?></dd></div><?php endif; ?>
      <div><dt class="text-slate-500 text-sm">Times shown in</dt><dd class="font-bold"><?= e($tz) ?></dd></div>
    </dl>

    <?php if ($cohort['registration_instructions']): ?><div class="mt-6 bg-bolt-tint rounded p-4 whitespace-pre-line"><?= e($cohort['registration_instructions']) ?></div><?php endif; ?>

    <h2 class="mt-10 text-2xl font-bold">Class timetable</h2>
    <?php if (!$sessions): ?><p class="mt-2 text-slate-600">The timetable will be published soon.</p>
    <?php else: ?>
      <ul class="mt-3 divide-y border rounded-lg bg-white">
        <?php foreach ($sessions as $s): ?>
          <li class="p-4 flex flex-wrap justify-between gap-2">
            <div><p class="font-bold"><?= $s['session_number'] ? 'Class ' . (int) $s['session_number'] . ': ' : '' ?><?= e($s['title']) ?></p>
              <p class="text-sm text-slate-600"><?= e(local_date($s['starts_at'], $s['timezone'])) ?> to <?= e(local_date($s['ends_at'], $s['timezone'], 'g:i A')) ?></p></div>
            <?php if ($s['status'] !== 'scheduled'): ?><span class="badge badge-warn"><?= e(ucfirst($s['status'])) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <?php if ($cohort['course_description']): ?><h2 class="mt-10 text-2xl font-bold">About the course</h2><div class="mt-2 whitespace-pre-line"><?= e($cohort['course_description']) ?></div><?php endif; ?>
  </div>

  <aside class="lg:sticky lg:top-24 self-start border border-slate-200 rounded-lg p-6 bg-white space-y-5">
    <?php if ($future): ?><?= App\Core\View::partial('partials/countdown', ['target' => utc_iso($cohort['start_at']), 'label' => 'Programme starts in', 'reload' => true]) ?><?php endif; ?>
    <?php if ($policy['ok']): ?>
      <p class="font-semibold"><?= e($policy['message']) ?></p>
      <?php if (App\Core\Auth::check('student')): ?>
        <form method="post" action="<?= e(url('/cohorts/' . $cohort['cohort_code'] . '/register')) ?>" class="space-y-3">
          <?= csrf_field() ?>
          <label class="flex gap-2 text-sm"><input type="checkbox" name="accept_terms" value="1" required>
            <span>I accept the <a class="underline" href="<?= e(url('/terms')) ?>">terms</a> and <a class="underline" href="<?= e(url('/refund-policy')) ?>">refund policy</a>.</span></label>
          <button class="btn btn-accent w-full" type="submit"><?= $policy['waitlist'] ? 'Join the waitlist' : ((float) $cohort['fee_amount'] > 0 ? 'Register & continue to payment' : 'Register for free') ?></button>
        </form>
      <?php else: ?>
        <a class="btn btn-accent w-full" href="<?= e(url('/register')) ?>">Create an account to register</a>
        <p class="text-sm text-center">Already have one? <a class="underline" href="<?= e(url('/login')) ?>">Log in</a></p>
      <?php endif; ?>
    <?php else: ?>
      <p class="badge badge-closed"><?= e($policy['message']) ?></p>
      <a class="btn btn-ghost w-full" href="<?= e(url('/contact?type=course')) ?>">Ask about the next cohort</a>
    <?php endif; ?>
  </aside>
</section>
