<?php
$cid = (int) $course['id'];
$all = 0; $pub = 0; foreach ($curriculum as $s) { foreach ($s['lessons'] as $l) { $all++; if ($l['status'] === 'published') $pub++; } }
$icon = ['video' => '&#9654;', 'article' => '&#9776;', 'quiz' => '?'];
?>
<a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/courses')) ?>">&larr; All courses</a>
<div class="mt-1 flex flex-wrap items-start justify-between gap-3">
  <div><h1 class="text-3xl font-extrabold">Curriculum</h1><p class="text-slate-600"><?= e($course['title']) ?> &middot; <span class="badge"><?= e($course['status']) ?></span> &middot; <?= e(str_replace('_', ' ', $course['delivery_mode'])) ?></p></div>
  <div class="flex flex-wrap gap-2"><a class="btn btn-ghost !py-2" href="<?= e(url('/admin/courses/' . $cid . '/edit')) ?>">Course settings</a>
    <?php if ($course['status'] === 'published'): ?><a class="btn btn-ghost !py-2" href="<?= e(url('/courses/' . $course['slug'])) ?>" target="_blank" rel="noopener">View public page</a><?php endif; ?></div>
</div>

<div class="mt-5 grid sm:grid-cols-3 gap-4">
  <?php foreach ([['Sections', count($curriculum)], ['Lessons (published / all)', $pub . ' / ' . $all], ['Total length', fmt_duration((int) $course['total_seconds'], true)]] as [$l, $v]): ?>
    <div class="bg-white border rounded-lg p-4"><p class="text-slate-500 text-sm"><?= e($l) ?></p><p class="font-display font-extrabold text-2xl"><?= e($v) ?></p></div>
  <?php endforeach; ?>
</div>
<?php if ($pub === 0): ?><p class="mt-4 border-l-4 border-spark bg-orange-50 p-3 rounded" role="status">No published lessons yet. Learners can't enrol until at least one lesson is published.</p><?php endif; ?>

<div class="mt-6 space-y-5">
<?php foreach ($curriculum as $si => $sec): $sid = (int) $sec['id']; ?>
  <section class="bg-white border rounded-lg" aria-label="Section <?= $si + 1 ?>">
    <div class="p-4 bg-slate-50 rounded-t-lg border-b flex flex-wrap items-center gap-3">
      <span class="font-bold text-slate-500">Section <?= $si + 1 ?></span>
      <form method="post" action="<?= e(url('/admin/sections/' . $sid)) ?>" class="flex-1 min-w-[12rem] flex gap-2"><?= csrf_field() ?>
        <label class="sr-only" for="st<?= $sid ?>">Section title</label><input class="field !py-1.5" id="st<?= $sid ?>" name="title" value="<?= e($sec['title']) ?>" required>
        <button class="btn btn-ghost !py-1.5" type="submit">Rename</button></form>
      <div class="flex gap-1">
        <?php foreach (['up' => '&#9650;', 'down' => '&#9660;'] as $dir => $sym): ?>
          <form method="post" action="<?= e(url('/admin/sections/' . $sid . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="btn btn-ghost !py-1.5 !px-3" type="submit" aria-label="Move section <?= $dir ?>"><?= $sym ?></button></form>
        <?php endforeach; ?>
        <form method="post" action="<?= e(url('/admin/sections/' . $sid . '/delete')) ?>" onsubmit="return confirm('Delete this section and ALL its lessons?')"><?= csrf_field() ?><button class="btn btn-ghost !py-1.5 text-red-700" type="submit">Delete</button></form>
      </div>
    </div>
    <ul class="divide-y">
      <?php foreach ($sec['lessons'] as $l): $lid = (int) $l['id']; ?>
        <li class="px-4 py-3 flex flex-wrap items-center gap-3">
          <span class="w-5 text-center text-slate-500" aria-hidden="true"><?= $icon[$l['lesson_type']] ?></span>
          <div class="flex-1 min-w-[10rem]"><a class="font-semibold hover:underline" href="<?= e(url('/admin/lessons/' . $lid . '/edit')) ?>"><?= e($l['title']) ?></a>
            <p class="text-xs text-slate-500"><?= e(ucfirst($l['lesson_type'])) ?><?= $l['lesson_type'] === 'quiz' ? ' &middot; ' . (int) ($qcounts[$lid] ?? 0) . ' questions' : ' &middot; ' . e(fmt_duration((int) $l['duration_seconds'])) ?><?= (int) $l['resource_count'] ? ' &middot; ' . (int) $l['resource_count'] . ' file(s)' : '' ?></p></div>
          <?php if ($l['is_preview']): ?><span class="tag">Free preview</span><?php endif; ?>
          <span class="badge <?= $l['status'] === 'published' ? 'badge-open' : 'badge-warn' ?>"><?= e($l['status']) ?></span>
          <div class="flex items-center gap-1">
            <?php foreach (['up' => '&#9650;', 'down' => '&#9660;'] as $dir => $sym): ?>
              <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="dir" value="<?= $dir ?>"><button class="adm-iconbtn" style="color:var(--ink)" type="submit" aria-label="Move lesson <?= $dir ?>"><?= $sym ?></button></form>
            <?php endforeach; ?>
            <a class="underline text-bolt-dark mx-2" href="<?= e(url('/admin/lessons/' . $lid . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/delete')) ?>" onsubmit="return confirm('Delete this lesson?')"><?= csrf_field() ?><button class="underline text-red-700">Delete</button></form>
          </div>
        </li>
      <?php endforeach; if (!$sec['lessons']): ?><li class="px-4 py-4 text-slate-500">No lessons in this section yet.</li><?php endif; ?>
    </ul>
    <div class="p-3 border-t"><a class="btn btn-ghost !py-1.5" href="<?= e(url('/admin/courses/' . $cid . '/lessons/create?section=' . $sid)) ?>">+ Add lesson</a></div>
  </section>
<?php endforeach; ?>
</div>

<form method="post" action="<?= e(url('/admin/courses/' . $cid . '/sections')) ?>" class="mt-6 bg-white border border-dashed border-slate-400 rounded-lg p-5 flex flex-wrap items-end gap-3 max-w-2xl">
  <?= csrf_field() ?>
  <div class="flex-1 min-w-[14rem]"><label class="label" for="new_section">New section title</label><input class="field" id="new_section" name="title" placeholder="e.g. Getting started" required></div>
  <button class="btn btn-primary" type="submit">+ Add section</button>
</form>
