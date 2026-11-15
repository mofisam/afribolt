<?php
/** @var array $course @var string $text @var array|null $result @var bool $publish */
$cid = (int) $course['id'];
$back = url('/admin/courses/' . $cid . '/curriculum');
$errs = $result['errors'] ?? [];
$ok = $result && !$errs && $result['lesson_count'] > 0;
$icon = ['video' => '&#9654; Video', 'article' => '&#9776; Article', 'quiz' => '? Quiz'];
?>
<div class="max-w-3xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e($back) ?>">&larr; <?= e($course['title']) ?> curriculum</a>
  <h1 class="mt-1 text-3xl font-extrabold">Import a course outline</h1>
  <p class="text-slate-600 max-w-2xl">Write (or paste) the whole course in one text file: sections, videos, articles and quizzes with their questions. It is added after whatever the course already has, so nothing is overwritten.</p>

  <?php if ($errs): ?>
    <div class="mt-5 border-l-4 border-red-600 bg-red-50 p-4 rounded" role="alert">
      <p class="font-bold text-red-800">Fix these problems, then press "Check outline". Nothing has been saved.</p>
      <ul class="mt-2 list-disc pl-5 text-sm text-red-900 space-y-1"><?php foreach ($errs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php elseif ($ok): ?>
    <div class="mt-5 border-l-4 border-green-600 bg-green-50 p-4 rounded" role="status">
      <p class="font-bold text-green-800"><?= count($result['sections']) ?> section(s), <?= (int) $result['lesson_count'] ?> lesson(s), <?= (int) $result['question_count'] ?> quiz question(s) ready. Check the list below, then press Import.</p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/admin/courses/' . $cid . '/import')) ?>" enctype="multipart/form-data" class="mt-5 bg-white border rounded-lg p-5 space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="ofile">Upload a .txt file</label><input class="field" type="file" id="ofile" name="ofile" accept=".txt,.text,.md,text/plain"></div>
    <div><label class="label" for="text">&hellip;or paste / edit the text here</label>
      <textarea class="field font-mono text-sm" id="text" name="text" rows="<?= $result ? 16 : 10 ?>" placeholder="[SECTION] Getting started&#10;[VIDEO] Welcome | https://youtu.be/... | 4 | preview"><?= e($text) ?></textarea>
      <p class="mt-1 text-sm text-slate-600">If you paste text, it is used instead of the file.</p></div>
    <label class="flex items-start gap-2"><input type="checkbox" name="publish" value="1" class="mt-1" <?= $publish ? 'checked' : '' ?>>
      <span><span class="font-bold">Publish complete lessons straight away</span><span class="block text-sm text-slate-600">Leave unticked to import everything as drafts and review first. Quizzes without questions always stay as drafts.</span></span></label>
    <div class="flex flex-wrap gap-3">
      <button class="btn btn-ghost" type="submit"><?= $result ? 'Check outline' : 'Check outline (preview)' ?></button>
      <?php if ($ok): ?><button class="btn btn-primary" type="submit" formaction="<?= e(url('/admin/courses/' . $cid . '/import/save')) ?>">Import <?= (int) $result['lesson_count'] ?> lesson<?= $result['lesson_count'] === 1 ? '' : 's' ?></button><?php endif; ?>
      <a class="btn btn-ghost" href="<?= e($back) ?>">Cancel</a>
    </div>
  </form>

  <?php if ($result && $result['sections']): ?>
    <h2 class="mt-8 font-bold text-xl">Preview</h2>
    <div class="mt-3 space-y-4">
      <?php foreach ($result['sections'] as $si => $s): ?>
        <section class="bg-white border rounded-lg">
          <p class="p-3 bg-slate-50 rounded-t-lg border-b font-bold"><span class="text-slate-500">Section <?= $si + 1 ?>:</span> <?= e($s['title']) ?></p>
          <ul class="divide-y">
            <?php foreach ($s['lessons'] as $l): ?>
              <li class="px-3 py-2 text-sm flex flex-wrap gap-x-3 gap-y-1 items-center">
                <span class="text-slate-500 w-20"><?= $icon[$l['type']] ?></span>
                <span class="font-semibold flex-1 min-w-[10rem]"><?= e($l['title']) ?></span>
                <span class="text-slate-500"><?php
                  if ($l['type'] === 'quiz') { echo count($l['questions']) . ' question(s)'; }
                  elseif ($l['type'] === 'article') { echo e(mb_strlen((string) $l['content'])) . ' characters'; }
                  elseif ($l['video_url']) { echo e(parse_url($l['video_url'], PHP_URL_HOST) ?: ''); }
                ?><?= $l['minutes'] !== null ? ' &middot; ' . e(rtrim(rtrim(number_format($l['minutes'], 1), '0'), '.')) . ' min' : '' ?></span>
                <?php if ($l['preview']): ?><span class="tag">Free preview</span><?php endif; ?>
              </li>
            <?php endforeach; if (!$s['lessons']): ?><li class="px-3 py-2 text-slate-500 text-sm">No lessons.</li><?php endif; ?>
          </ul>
        </section>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <details class="mt-8 bg-white border rounded-lg p-5" <?= $result ? '' : 'open' ?>>
    <summary class="cursor-pointer font-bold">How to write the file &middot; <a class="underline text-bolt-dark" href="<?= e(url('/admin/import-samples/outline')) ?>">download a sample</a></summary>
    <pre class="mt-3 bg-slate-100 p-3 rounded text-xs whitespace-pre-wrap"><?= e(\App\Services\CourseOutlineImportService::SAMPLE) ?></pre>
  </details>
</div>
