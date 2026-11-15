<?php
/** @var array $lesson @var array $course @var string $text @var array $result @var string $mode @var int $existing */
$lid = (int) $lesson['id'];
$back = url('/admin/lessons/' . $lid . '/edit#quiz');
$qs = $result['questions']; $errs = $result['errors'];
$ok = !$errs && $qs;
?>
<div class="max-w-3xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e($back) ?>">&larr; Back to the quiz</a>
  <h1 class="mt-1 text-3xl font-extrabold">Import questions</h1>
  <p class="text-slate-600"><?= e($course['title']) ?> &middot; <?= e($lesson['title']) ?></p>

  <?php if ($errs): ?>
    <div class="mt-5 border-l-4 border-red-600 bg-red-50 p-4 rounded" role="alert">
      <p class="font-bold text-red-800">Fix these problems, then press "Check again". Nothing has been saved.</p>
      <ul class="mt-2 list-disc pl-5 text-sm text-red-900 space-y-1"><?php foreach ($errs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
  <?php elseif ($qs): ?>
    <div class="mt-5 border-l-4 border-green-600 bg-green-50 p-4 rounded" role="status">
      <p class="font-bold text-green-800"><?= count($qs) ?> question<?= count($qs) === 1 ? '' : 's' ?> ready to import. Check them below, then press Import.</p>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('/admin/lessons/' . $lid . '/questions/import')) ?>" class="mt-5 bg-white border rounded-lg p-5 space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="text">Your text (you can edit it here)</label>
      <textarea class="field font-mono text-sm" id="text" name="text" rows="14"><?= e($text) ?></textarea></div>

    <fieldset><legend class="label">What should happen to the <?= (int) $existing ?> question<?= $existing === 1 ? '' : 's' ?> already in this quiz?</legend>
      <label class="flex items-center gap-2 mt-1"><input type="radio" name="mode" value="append" <?= $mode === 'append' ? 'checked' : '' ?>> Keep them and add the new ones after</label>
      <label class="flex items-center gap-2 mt-1"><input type="radio" name="mode" value="replace" <?= $mode === 'replace' ? 'checked' : '' ?>> Replace them with the imported questions</label></fieldset>

    <div class="flex flex-wrap gap-3">
      <button class="btn btn-ghost" type="submit">Check again</button>
      <?php if ($ok): ?>
        <button class="btn btn-primary" type="submit" formaction="<?= e(url('/admin/lessons/' . $lid . '/questions/import/save')) ?>"
          onclick="var r=document.querySelector('input[name=mode]:checked');return !(r&&r.value==='replace')||confirm('Replace the existing questions with these <?= count($qs) ?>?')">Import <?= count($qs) ?> question<?= count($qs) === 1 ? '' : 's' ?></button>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e($back) ?>">Cancel</a>
    </div>
  </form>

  <?php if ($qs): ?>
    <h2 class="mt-8 font-bold text-xl">Preview</h2>
    <ol class="mt-3 space-y-3">
      <?php foreach ($qs as $i => $q): ?>
        <li class="bg-white border rounded p-3">
          <p class="font-semibold"><?= $i + 1 ?>. <?= e($q['question']) ?></p>
          <ul class="mt-2 text-sm space-y-1"><?php foreach ($q['options'] as $k => $o): ?><li class="<?= $k === $q['correct'] ? 'text-green-700 font-bold' : '' ?>"><?= $k === $q['correct'] ? '&#10003; ' : '&#9675; ' ?><?= e($o) ?></li><?php endforeach; ?></ul>
          <?php if ($q['explanation']): ?><p class="mt-2 text-xs text-slate-600">Explanation: <?= e($q['explanation']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  <?php endif; ?>
</div>
