<h1 class="text-3xl font-extrabold">Hi <?= e($student['first_name']) ?></h1>
<?php if ($unread): ?><p class="mt-1"><a class="underline text-bolt-dark" href="<?= e(url('/student/notifications')) ?>">You have <?= (int) $unread ?> unread notification<?= $unread > 1 ? 's' : '' ?></a></p><?php endif; ?>

<?php if (!empty($learning)): ?>
<section class="mt-6" aria-labelledby="cl-h"><div class="flex items-center justify-between"><h2 id="cl-h" class="font-display font-bold text-xl">Continue learning</h2>
  <a class="text-sm font-bold underline text-bolt-dark" href="<?= e(url('/student/learning')) ?>">My learning</a></div>
  <div class="mt-3 grid sm:grid-cols-2 gap-4">
    <?php foreach ($learning as $c): ?>
      <a class="ccard !flex-row" href="<?= e(url('/learn/' . $c['slug'])) ?>">
        <div class="ccard-img !aspect-auto w-28 shrink-0"><?php if (!empty($c['thumbnail_url'])): ?><img src="<?= e(asset($c['thumbnail_url'])) ?>" alt=""><?php else: ?><span class="ph-letters !text-2xl"><?= e(strtoupper(substr($c['title'], 0, 2))) ?></span><?php endif; ?></div>
        <div class="ccard-body justify-center"><h3 class="ccard-title"><?= e($c['title']) ?></h3>
          <div class="bar mt-1"><span style="width:<?= (int) $c['progress']['percent'] ?>%"></span></div>
          <p class="text-xs text-slate-600"><?= (int) $c['progress']['percent'] ?>% complete &middot; <span class="font-bold text-bolt-dark">Continue &rsaquo;</span></p></div></a>
    <?php endforeach; ?></div></section>
<?php endif; ?>

<?php if (!$enrolments && empty($learning)): ?>
  <div class="mt-8 bg-white border rounded-lg p-8 text-center">
    <p class="text-lg font-semibold">You haven't registered for a cohort yet.</p>
    <a class="btn btn-primary mt-4" href="<?= e(url('/courses')) ?>">Browse courses</a>
  </div>
<?php endif; ?>

<?php foreach ($enrolments as $e): $tz = $e['timezone']; ?>
<section class="mt-8 bg-white border rounded-lg overflow-hidden">
  <div class="p-6 md:flex justify-between gap-6">
    <div>
      <span class="badge <?= $e['status'] === 'confirmed' ? 'badge-open' : ($e['status'] === 'pending_payment' ? 'badge-warn' : '') ?>"><?= e(str_replace('_', ' ', $e['status'])) ?></span>
      <h2 class="mt-2 text-2xl font-bold"><?= e($e['course_title']) ?></h2>
      <p class="text-slate-600"><?= e($e['cohort_name']) ?> &middot; starts <?= e(local_date($e['start_at'], $tz)) ?></p>
      <p class="text-sm text-slate-500 mt-1">Ref <?= e($e['enrolment_number']) ?> &middot; <?= e(money($e['amount_paid'], $e['currency']) === 'Free' ? 'Nothing paid yet' : money($e['amount_paid'], $e['currency']) . ' paid') ?> of <?= e(money($e['fee_amount'], $e['currency'])) ?></p>
      <?php if ($e['status'] === 'pending_payment'): ?>
        <form method="post" action="<?= e(url('/student/enrolments/' . $e['id'] . '/pay')) ?>" class="mt-4"><?= csrf_field() ?><button class="btn btn-accent" type="submit">Pay now to confirm your seat</button></form>
      <?php elseif ($e['status'] === 'waitlisted'): ?>
        <p class="mt-3 text-sm">You're on the waitlist. We'll notify you if a seat opens.</p>
      <?php endif; ?>
    </div>
    <?php if ($e['countdown'] && $e['status'] === 'confirmed'): ?>
      <div class="mt-6 md:mt-0 bg-bolt-tint rounded-lg p-5"><?= App\Core\View::partial('partials/countdown', ['target' => $e['countdown']['target'], 'label' => $e['countdown']['label'], 'reload' => true]) ?></div>
    <?php endif; ?>
  </div>

  <?php if ($e['sessions']): ?>
  <div class="border-t">
    <h3 class="px-6 pt-4 font-bold">Timetable</h3>
    <ul class="divide-y">
      <?php foreach ($e['sessions'] as $s): $isNext = $e['countdown'] && isset($e['countdown']['session']) && (int) $e['countdown']['session']['id'] === (int) $s['id']; ?>
        <li class="px-6 py-4 flex flex-wrap items-center justify-between gap-3 <?= $isNext ? 'bg-bolt-tint/60' : '' ?>">
          <div><p class="font-semibold"><?= $s['session_number'] ? 'Class ' . (int) $s['session_number'] . ': ' : '' ?><?= e($s['title']) ?></p>
            <p class="text-sm text-slate-600"><?= e(local_date($s['starts_at'], $s['timezone'])) ?> to <?= e(local_date($s['ends_at'], $s['timezone'], 'g:i A')) ?></p></div>
          <div class="text-right">
            <?php if (in_array($s['status'], ['scheduled', 'live'], true) && $s['ends_at'] > utc_now()): ?>
              <button type="button" class="btn btn-primary" disabled data-join-session
                data-endpoint="<?= e(url('/student/sessions/' . $s['id'] . '/join')) ?>"
                data-starts="<?= e(utc_iso($s['starts_at'])) ?>" data-ends="<?= e(utc_iso($s['ends_at'])) ?>"
                data-early="<?= (int) $s['join_early_minutes'] ?>" data-server-now="<?= e(utc_iso(utc_now())) ?>">Join class</button>
              <p class="text-xs text-slate-500 mt-1">Opens <?= (int) $s['join_early_minutes'] ?> min before start</p>
              <p class="text-sm text-red-700" data-join-msg role="status"></p>
            <?php else: ?>
              <span class="badge <?= in_array($s['status'], ['cancelled', 'postponed'], true) ? 'badge-warn' : '' ?>"><?= e(ucfirst($s['status'] === 'scheduled' ? 'ended' : $s['status'])) ?></span>
            <?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php if ($announcements): ?>
<h2 class="mt-10 text-xl font-bold">Announcements</h2>
<div class="mt-3 space-y-3">
  <?php foreach ($announcements as $a): ?>
    <article class="bg-white border rounded-lg p-5"><h3 class="font-bold"><?= e($a['title']) ?></h3>
      <?php if ($a['cohort_name']): ?><p class="text-xs text-slate-500"><?= e($a['cohort_name']) ?></p><?php endif; ?>
      <div class="mt-2 whitespace-pre-line"><?= e($a['body']) ?></div></article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
