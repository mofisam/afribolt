<h1 class="text-3xl font-extrabold">Announcements</h1>
<p class="mt-1 text-slate-600 max-w-2xl">Post an update to the students of one of your cohorts. It appears on their dashboard straight away and they get a notification.</p>

<?php if (!$cohorts): ?>
  <p class="mt-6 bg-white border rounded-lg p-5">You aren't assigned to an active cohort yet.</p>
<?php else: $pre = (int) ($_GET['cohort'] ?? 0); ?>
<form method="post" action="<?= e(url('/instructor/announcements')) ?>" class="mt-6 bg-white border rounded-lg p-6 space-y-4 max-w-3xl" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="cohort_id">Cohort</label>
    <select class="field" id="cohort_id" name="cohort_id"><?php foreach ($cohorts as $id => $name): ?><option value="<?= (int) $id ?>" <?= (string) old('cohort_id', $pre) === (string) $id ? 'selected' : '' ?>><?= e($name) ?></option><?php endforeach; ?></select><?= field_error('cohort_id') ?></div>
  <div><label class="label" for="title">Title</label><input class="field" id="title" name="title" value="<?= e(old('title')) ?>" required><?= field_error('title') ?></div>
  <div><label class="label" for="body">Message</label><textarea class="field" id="body" name="body" rows="5" required><?= e(old('body')) ?></textarea><?= field_error('body') ?></div>
  <button class="btn btn-primary" type="submit" onclick="return confirm('Send this to all students in the cohort now?')">Post &amp; notify students</button>
</form>
<?php endif; ?>

<h2 class="mt-10 text-xl font-bold">My posts</h2>
<ul class="mt-3 divide-y bg-white border rounded-lg">
  <?php foreach ($items as $a): ?>
    <li class="p-4 flex flex-wrap items-start justify-between gap-3">
      <div class="min-w-0"><p class="font-semibold"><?= e($a['title']) ?> <span class="badge <?= $a['status'] === 'published' ? 'badge-open' : '' ?>"><?= e($a['status']) ?></span></p>
        <p class="text-xs text-slate-500"><?= e($a['cohort_name']) ?> &middot; <?= e(local_date($a['created_at'])) ?></p>
        <p class="mt-1 text-sm whitespace-pre-line"><?= e(mb_strimwidth($a['body'], 0, 220, '...')) ?></p></div>
      <?php if ($a['status'] === 'published'): ?>
        <form method="post" action="<?= e(url('/instructor/announcements/' . $a['id'] . '/archive')) ?>" onsubmit="return confirm('Hide this from students?')"><?= csrf_field() ?><button class="underline text-red-700 text-sm">Archive</button></form>
      <?php endif; ?>
    </li>
  <?php endforeach; if (!$items): ?><li class="p-4 text-slate-500">You haven't posted anything yet.</li><?php endif; ?>
</ul>
