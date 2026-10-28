<?php $tz = $c['timezone']; ?>
<a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/cohorts')) ?>">&larr; All cohorts</a>
<div class="mt-1 flex flex-wrap items-start justify-between gap-3">
  <div><h1 class="text-3xl font-extrabold"><?= e($c['cohort_name']) ?></h1>
    <p class="text-slate-600"><?= e($c['course_title']) ?> &middot; code <strong><?= e($c['cohort_code']) ?></strong> &middot; <span class="badge"><?= e(str_replace('_', ' ', $c['status'])) ?></span></p></div>
  <div class="flex gap-2">
    <a class="btn btn-primary" href="<?= e(url('/admin/cohorts/' . $c['id'] . '/edit')) ?>">Edit</a>
    <a class="btn btn-ghost" href="<?= e(url('/cohorts/' . $c['cohort_code'])) ?>">Public page</a>
  </div>
</div>

<div class="mt-6 grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
  <?php foreach ([
    ['Seats taken', $seatsTaken . ' / ' . ($c['capacity'] ?? '∞')], ['Confirmed', $counts['confirmed'] ?? 0],
    ['Awaiting payment', $counts['pending_payment'] ?? 0], ['Waitlisted', $counts['waitlisted'] ?? 0],
    ['Net revenue', money($revenue, $c['currency']) === 'Free' ? '0' : money($revenue, $c['currency'])], ['Starts', local_date($c['start_at'], $tz, 'j M Y, g:i A')],
    ['Ends', $c['end_at'] ? local_date($c['end_at'], $tz, 'j M Y') : 'Not set'], ['Fee', money($c['fee_amount'], $c['currency'])],
  ] as [$l, $v]): ?>
    <div class="bg-white border rounded-lg p-4"><p class="text-slate-500 text-sm"><?= e($l) ?></p><p class="font-display font-extrabold text-2xl"><?= e($v) ?></p></div>
  <?php endforeach; ?>
</div>

<section class="mt-8 bg-white border rounded-lg p-5">
  <h2 class="font-bold text-xl">Change status</h2>
  <div class="mt-3 flex flex-wrap gap-2">
    <?php foreach ($statuses as $k => $label): if ($k === $c['status']) continue; ?>
      <form method="post" action="<?= e(url('/admin/cohorts/' . $c['id'] . '/status')) ?>" <?= in_array($k, ['cancelled', 'postponed'], true) ? 'onsubmit="return confirm(\'This notifies all enrolled students. Continue?\')"' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="status" value="<?= e($k) ?>"><button class="btn btn-ghost !py-1.5" type="submit"><?= e($label) ?></button></form>
    <?php endforeach; ?>
  </div>
</section>

<section class="mt-8">
  <div class="flex items-center justify-between"><h2 class="font-bold text-xl">Class sessions (<?= count($sessions) ?>)</h2>
    <a class="btn btn-primary" href="<?= e(url('/admin/sessions/create?cohort_id=' . $c['id'])) ?>">Add sessions</a></div>
  <div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-3">#</th><th class="p-3">Title</th><th class="p-3">When</th><th class="p-3">Link</th><th class="p-3">Status</th><th></th></tr></thead><tbody>
    <?php foreach ($sessions as $s): ?>
      <tr class="border-t"><td class="p-3"><?= e($s['session_number']) ?></td><td class="p-3"><?= e($s['title']) ?></td>
        <td class="p-3"><?= e(local_date($s['starts_at'], $s['timezone'])) ?></td>
        <td class="p-3"><?= $s['meeting_url'] ? 'Set' : '<span class="text-red-700">Missing</span>' ?></td>
        <td class="p-3"><span class="badge"><?= e($s['status']) ?></span></td>
        <td class="p-3"><a class="underline text-bolt-dark" href="<?= e(url('/admin/sessions/' . $s['id'] . '/edit')) ?>">Edit</a></td></tr>
    <?php endforeach; if (!$sessions): ?><tr><td class="p-4 text-slate-500" colspan="6">No sessions yet.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>

<section class="mt-8 grid lg:grid-cols-2 gap-6">
  <div class="bg-white border rounded-lg p-5">
    <h2 class="font-bold text-xl">Instructors</h2>
    <ul class="mt-3 divide-y">
      <?php foreach ($instructors as $i): ?>
        <li class="py-2 flex items-center justify-between"><span><?= e($i['first_name'] . ' ' . $i['last_name']) ?> <span class="badge"><?= e($i['assignment_role']) ?></span></span>
          <form method="post" action="<?= e(url('/admin/cohorts/' . $c['id'] . '/instructors/' . $i['instructor_id'] . '/remove')) ?>"><?= csrf_field() ?><button class="underline text-red-700 text-sm">Remove</button></form></li>
      <?php endforeach; if (!$instructors): ?><li class="py-2 text-slate-500">None assigned.</li><?php endif; ?>
    </ul>
    <?php if ($available): ?>
    <form method="post" action="<?= e(url('/admin/cohorts/' . $c['id'] . '/instructors')) ?>" class="mt-4 flex flex-wrap gap-2 items-end">
      <?= csrf_field() ?>
      <div><label class="label text-sm" for="instructor_id">Assign</label><select class="field" id="instructor_id" name="instructor_id">
        <?php foreach ($available as $i): ?><option value="<?= (int) $i['id'] ?>"><?= e($i['first_name'] . ' ' . $i['last_name']) ?></option><?php endforeach; ?></select></div>
      <div><label class="label text-sm" for="assignment_role">Role</label><select class="field" id="assignment_role" name="assignment_role"><option value="lead">Lead</option><option value="assistant">Assistant</option></select></div>
      <button class="btn btn-primary" type="submit">Assign</button>
    </form><?php endif; ?>
  </div>
  <div class="bg-white border rounded-lg p-5">
    <h2 class="font-bold text-xl">Registration rules</h2>
    <dl class="mt-3 space-y-1 text-sm">
      <div class="flex justify-between"><dt>Registration enabled</dt><dd><?= $c['registration_enabled'] ? 'Yes' : 'No' ?></dd></div>
      <div class="flex justify-between"><dt>Opens</dt><dd><?= $c['registration_opens_at'] ? e(local_date($c['registration_opens_at'], $tz)) : 'Immediately' ?></dd></div>
      <div class="flex justify-between"><dt>Closes</dt><dd><?= $c['registration_closes_at'] ? e(local_date($c['registration_closes_at'], $tz)) : 'No deadline' ?></dd></div>
      <div class="flex justify-between"><dt>Late registration</dt><dd><?= $c['allow_registration_after_start'] ? 'Allowed' : 'Not allowed' ?></dd></div>
      <div class="flex justify-between"><dt>Waitlist</dt><dd><?= $c['waitlist_enabled'] ? 'On' : 'Off' ?></dd></div>
      <div class="flex justify-between"><dt>Payment required</dt><dd><?= $c['require_payment'] ? 'Yes' : 'No' ?></dd></div>
    </dl>
    <a class="underline text-bolt-dark mt-3 inline-block" href="<?= e(url('/admin/enrolments?cohort=' . $c['id'])) ?>">View enrolments</a>
  </div>
</section>
