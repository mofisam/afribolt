<?php
$v = fn(string $k, $d = '') => old($k, $lesson[$k] ?? $d);
$lid = (int) ($lesson['id'] ?? 0);
$back = url('/admin/courses/' . (int) $course['id'] . '/curriculum');
?>
<div class="max-w-3xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e($back) ?>">&larr; <?= e($course['title']) ?> curriculum</a>
  <h1 class="mt-1 text-3xl font-extrabold"><?= e($title) ?></h1>

  <form method="post" action="<?= e($action) ?>" class="mt-6 bg-white border rounded-lg p-6 grid sm:grid-cols-2 gap-5" novalidate>
    <?= csrf_field() ?>
    <div><label class="label" for="section_id">Section</label>
      <select class="field" id="section_id" name="section_id"><?php foreach ($sections as $sid => $st): ?><option value="<?= (int) $sid ?>" <?= (int) $v('section_id') === (int) $sid ? 'selected' : '' ?>><?= e($st) ?></option><?php endforeach; ?></select><?= field_error('section_id') ?></div>
    <div><label class="label" for="lesson_type">Lesson type</label>
      <select class="field" id="lesson_type" name="lesson_type"><?php foreach (['video' => 'Video', 'article' => 'Article (reading)', 'quiz' => 'Quiz'] as $k => $l): ?><option value="<?= $k ?>" <?= $v('lesson_type') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="sm:col-span-2"><label class="label" for="title">Title</label><input class="field" id="title" name="title" value="<?= e($v('title')) ?>" required><?= field_error('title') ?></div>

    <div class="sm:col-span-2" data-for="video"><label class="label" for="video_url">Video link</label>
      <input class="field" type="url" id="video_url" name="video_url" value="<?= e($v('video_url')) ?>" placeholder="https://www.youtube.com/watch?v=...">
      <p class="mt-1 text-sm text-slate-600">YouTube (unlisted works well), Vimeo, or a direct https .mp4 / .webm link. Large files should be hosted on a video service, not on this server.</p><?= field_error('video_url') ?></div>
    <div data-for="video article"><label class="label" for="duration_minutes">Length (minutes)</label>
      <input class="field" type="number" step="0.1" min="0" id="duration_minutes" name="duration_minutes" value="<?= e($v('duration_minutes')) ?>">
      <p class="mt-1 text-sm text-slate-600">Shown to learners. Articles are estimated from word count if left blank.</p><?= field_error('duration_minutes') ?></div>

    <div class="sm:col-span-2" data-for="article"><label class="label" for="content">Lesson text</label>
      <textarea class="field font-mono text-sm" id="content" name="content" rows="14"><?= e($v('content')) ?></textarea><?= field_error('content') ?>
      <details class="mt-2 text-sm"><summary class="cursor-pointer underline text-bolt-dark">Formatting help</summary>
        <pre class="mt-2 bg-slate-100 p-3 rounded whitespace-pre-wrap"># Big heading   ## Medium   ### Small
**bold**   *italic*   `code`   [link text](https://example.com)
- bullet item          1. numbered item
&gt; a quote
```
fenced code block
```</pre></details></div>

    <div class="sm:col-span-2" data-for="quiz"><p class="bg-bolt-tint rounded p-3 text-sm">Quizzes are made of multiple-choice questions. Save the lesson first, then add questions below. Learners need <?= (int) \App\Services\LearningService::PASS_MARK ?>% to pass.</p></div>

    <div class="sm:col-span-2" data-for="video article"><label class="flex items-start gap-2"><input type="hidden" name="is_preview" value="0"><input type="checkbox" name="is_preview" value="1" class="mt-1" <?= (string) $v('is_preview') === '1' ? 'checked' : '' ?>>
      <span><span class="font-bold">Free preview</span><span class="block text-sm text-slate-600">Anyone can watch this lesson without enrolling. Great for a short intro.</span></span></label></div>
    <div><label class="label" for="status">Status</label>
      <select class="field" id="status" name="status"><?php foreach (['draft' => 'Draft (hidden from learners)', 'published' => 'Published'] as $k => $l): ?><option value="<?= $k ?>" <?= $v('status', 'draft') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select><?= field_error('status') ?></div>
    <div class="sm:col-span-2 flex gap-3"><button class="btn btn-primary" type="submit">Save lesson</button><a class="btn btn-ghost" href="<?= e($back) ?>">Back</a></div>
  </form>

  <?php if (!$isNew): ?>
  <section id="resources" class="mt-8 bg-white border rounded-lg p-6" style="scroll-margin-top:5rem">
    <h2 class="font-bold text-xl">Downloadable files</h2>
    <p class="text-sm text-slate-600">Slides, code samples, worksheets. Only enrolled learners can download them.</p>
    <ul class="mt-3 divide-y">
      <?php foreach ($resources as $r): ?><li class="py-2 flex items-center justify-between gap-3"><span class="min-w-0"><span class="font-semibold block truncate"><?= e($r['title']) ?></span><span class="text-xs text-slate-500"><?= e($r['file_name']) ?> &middot; <?= e(number_format($r['file_size'] / 1048576, 1)) ?> MB</span></span>
        <form method="post" action="<?= e(url('/admin/resources/' . $r['id'] . '/delete')) ?>" onsubmit="return confirm('Remove this file?')"><?= csrf_field() ?><button class="underline text-red-700 text-sm">Remove</button></form></li>
      <?php endforeach; if (!$resources): ?><li class="py-2 text-slate-500">No files yet.</li><?php endif; ?>
    </ul>
    <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/resources')) ?>" enctype="multipart/form-data" class="mt-4 grid sm:grid-cols-2 gap-3 items-end"><?= csrf_field() ?>
      <div><label class="label" for="r_title">Display name (optional)</label><input class="field" id="r_title" name="title"></div>
      <div><label class="label" for="r_file">File</label><input class="field" type="file" id="r_file" name="resource" required></div>
      <div class="sm:col-span-2"><button class="btn btn-ghost" type="submit">Upload file</button></div>
    </form>
  </section>

  <?php if (($lesson['lesson_type'] ?? '') === 'quiz'): ?>
  <section id="quiz" class="mt-8 bg-white border rounded-lg p-6" style="scroll-margin-top:5rem">
    <h2 class="font-bold text-xl">Quiz questions (<?= count($questions) ?>)</h2>
    <ol class="mt-3 space-y-4">
      <?php foreach ($questions as $q): ?>
        <li class="border rounded p-3"><div class="flex justify-between gap-3"><p class="font-semibold"><?= e($q['question']) ?></p>
          <form method="post" action="<?= e(url('/admin/questions/' . $q['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this question?')"><?= csrf_field() ?><button class="underline text-red-700 text-sm">Delete</button></form></div>
          <ul class="mt-2 text-sm space-y-1"><?php foreach ($q['options'] as $o): ?><li class="<?= $o['is_correct'] ? 'text-green-700 font-bold' : '' ?>"><?= $o['is_correct'] ? '&#10003; ' : '&#9675; ' ?><?= e($o['option_text']) ?></li><?php endforeach; ?></ul>
          <?php if ($q['explanation']): ?><p class="mt-2 text-xs text-slate-600">Explanation: <?= e($q['explanation']) ?></p><?php endif; ?></li>
      <?php endforeach; if (!$questions): ?><li class="text-slate-500">No questions yet.</li><?php endif; ?>
    </ol>
    <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/questions')) ?>" class="mt-5 space-y-3 border-t pt-5"><?= csrf_field() ?>
      <h3 class="font-bold">Add a question</h3>
      <div><label class="label" for="question">Question</label><textarea class="field" id="question" name="question" rows="2" required></textarea></div>
      <fieldset><legend class="label">Options (select the correct one)</legend>
        <?php for ($i = 0; $i < 4; $i++): ?><div class="flex items-center gap-2 mt-2"><input type="radio" name="correct" value="<?= $i ?>" id="c<?= $i ?>" <?= $i === 0 ? 'checked' : '' ?> aria-label="Option <?= $i + 1 ?> is correct">
          <input class="field" name="option[<?= $i ?>]" placeholder="Option <?= $i + 1 ?><?= $i > 1 ? ' (optional)' : '' ?>"></div><?php endfor; ?></fieldset>
      <div><label class="label" for="explanation">Explanation shown after answering (optional)</label><input class="field" id="explanation" name="explanation"></div>
      <button class="btn btn-ghost" type="submit">Add question</button>
    </form>
  </section>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/delete')) ?>" class="mt-8" onsubmit="return confirm('Delete this lesson permanently?')"><?= csrf_field() ?><button class="underline text-red-700">Delete this lesson</button></form>
  <?php endif; ?>
</div>
<script src="<?= e(asset('js/curriculum.js')) ?>" defer></script>
