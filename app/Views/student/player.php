<?php
use App\Core\View;
$slug = $course['slug']; $lid = (int) $lesson['id'];
$lurl = fn(array $l) => url('/learn/' . $slug . '/' . $l['id']);
$done = !empty($progress[$lid]['completed']);
$isQuiz = $lesson['lesson_type'] === 'quiz';
$origin = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$n = 0;
?>
<!doctype html>
<html lang="en">
<head><?= View::partial('partials/head', ['title' => $title]) ?></head>
<body class="bg-white">
<header class="pl-top">
  <a href="<?= e(url('/student/learning')) ?>" class="shrink-0 font-bold" aria-label="Back to my learning">&lsaquo; <span class="hidden sm:inline">My learning</span></a>
  <span class="w-px h-6 bg-white/30 shrink-0" aria-hidden="true"></span>
  <a href="<?= e(url('/courses/' . $slug)) ?>" class="font-display font-bold truncate flex-1"><?= e($course['title']) ?></a>
  <div class="flex items-center gap-2 shrink-0"><span class="hidden md:block text-sm text-slate-200" data-progress-text><?= (int) $summary['done'] ?> of <?= (int) $summary['total'] ?> complete</span>
    <div class="pl-ring" style="--p:<?= (int) $summary['percent'] ?>" role="img" aria-label="<?= (int) $summary['percent'] ?> percent complete"><span><?= (int) $summary['percent'] ?>%</span></div></div>
</header>

<div id="player" data-base="<?= e(url('/learn/' . $slug . '/' . $lid)) ?>" data-next="<?= $next ? e($lurl($next)) : '' ?>" data-done="<?= $done ? '1' : '0' ?>" data-pass="<?= (int) $passMark ?>" data-cert-base="<?= e(url('/certificate/')) ?>">
<div class="pl-layout">
  <main id="main">
    <?php if ($lesson['lesson_type'] === 'video'): ?>
      <div class="pl-media"><div class="pl-frame">
        <?php if (!$embed): ?><div class="absolute inset-0 grid place-items-center text-white p-6 text-center">This video isn't available yet. Check back soon.</div>
        <?php elseif ($video['provider'] === 'file'): ?><video id="lesson-video" controls preload="metadata" data-pos="<?= (int) ($progress[$lid]['pos'] ?? 0) ?>" src="<?= e($embed) ?>"></video>
        <?php elseif ($video['provider'] === 'youtube'): ?><iframe id="lesson-yt" src="<?= e($embed . '&origin=' . rawurlencode($origin)) ?>" title="<?= e($lesson['title']) ?>" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe>
        <?php else: ?><iframe src="<?= e($embed) ?>" title="<?= e($lesson['title']) ?>" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe><?php endif; ?>
      </div></div>
    <?php endif; ?>

    <div class="p-4 md:p-8 max-w-4xl">
      <div id="cert-banner" class="mb-5 rounded-lg border border-green-300 bg-green-50 p-4 flex flex-wrap items-center justify-between gap-3" <?= $certificate ? '' : 'hidden' ?>>
        <p class="font-bold">&#127881; You completed this course!</p>
        <a class="btn btn-primary !py-1.5" href="<?= $certificate ? e(url('/certificate/' . $certificate)) : '#' ?>" target="_blank" rel="noopener">View your certificate</a>
      </div>

      <h1 class="text-2xl md:text-3xl font-extrabold"><?= e($lesson['title']) ?></h1>

      <div class="mt-4 flex flex-wrap items-center gap-2">
        <?php if ($prev): ?><a class="btn btn-ghost !py-2" href="<?= e($lurl($prev)) ?>">&lsaquo; Previous</a><?php endif; ?>
        <?php if (!$isQuiz): ?><button id="complete-btn" type="button" class="btn btn-primary !py-2">Mark as complete</button><?php endif; ?>
        <?php if ($next): ?>
          <?php if ($isQuiz): ?><a class="btn btn-ghost !py-2" href="<?= e($lurl($next)) ?>">Next &rsaquo;</a>
          <?php else: ?><button id="complete-next" type="button" class="btn btn-accent !py-2">Complete &amp; next &rsaquo;</button><?php endif; ?>
        <?php else: ?><a class="btn btn-ghost !py-2" href="<?= e(url('/student/learning')) ?>">Back to my learning</a><?php endif; ?>
        <span id="complete-msg" class="text-sm text-red-700" role="status"></span>
      </div>

      <?php if ($lesson['lesson_type'] === 'article'): ?>
        <div class="mt-6 prose-lite"><?= render_content((string) $lesson['content']) ?></div>
      <?php elseif ($isQuiz): ?>
        <form id="quiz-form" class="mt-4">
          <p class="text-slate-600">Answer every question. You need <?= (int) $passMark ?>% to pass and can retry as often as you like.<?= isset($progress[$lid]['quiz']) ? ' Your best score: <strong>' . (int) $progress[$lid]['quiz'] . '%</strong>.' : '' ?></p>
          <?php foreach ($quiz as $qi => $q): ?>
            <fieldset class="quiz-q" data-q="<?= (int) $q['id'] ?>"><legend class="font-bold px-1"><?= $qi + 1 ?>. <?= e($q['question']) ?></legend>
              <?php foreach ($q['options'] as $o): ?><label class="quiz-opt"><input type="radio" name="q[<?= (int) $q['id'] ?>]" value="<?= (int) $o['id'] ?>" class="mt-1"><span><?= e($o['option_text']) ?></span></label><?php endforeach; ?>
              <p class="quiz-ex mt-2 text-sm text-slate-700" hidden></p></fieldset>
          <?php endforeach; if (!$quiz): ?><p class="mt-4 text-slate-600">This quiz has no questions yet.</p><?php endif; ?>
          <?php if ($quiz): ?><button class="btn btn-primary mt-5" type="submit">Submit answers</button><?php endif; ?>
          <p id="quiz-result" class="mt-4 font-bold" role="status" aria-live="polite"></p>
        </form>
      <?php endif; ?>

      <div class="mt-8">
        <div class="pl-tabs" role="tablist" aria-label="Lesson details">
          <button class="pl-tab" role="tab" id="tab-about" aria-controls="panel-about" aria-selected="true">About this course</button>
          <button class="pl-tab" role="tab" id="tab-resources" aria-controls="panel-resources" aria-selected="false" tabindex="-1">Resources<?= $resources ? ' (' . count($resources) . ')' : '' ?></button>
          <button class="pl-tab" role="tab" id="tab-reviews" aria-controls="panel-reviews" aria-selected="false" tabindex="-1">Review</button>
        </div>
        <div id="panel-about" role="tabpanel" aria-labelledby="tab-about" class="py-5">
          <p class="text-lg"><?= e($course['short_description']) ?></p>
          <?php if ($course['instructor_name']): ?><p class="mt-3 text-slate-600">Instructor: <strong><?= e($course['instructor_name']) ?></strong></p><?php endif; ?>
          <?php if ($course['description']): ?><div class="mt-4 prose-lite"><?= render_content((string) $course['description']) ?></div><?php endif; ?>
        </div>
        <div id="panel-resources" role="tabpanel" aria-labelledby="tab-resources" class="py-5" hidden>
          <?php if (!$resources): ?><p class="text-slate-600">No downloads for this lesson.</p><?php endif; ?>
          <ul class="divide-y border rounded-lg"><?php foreach ($resources as $r): ?><li class="p-3 flex items-center justify-between gap-3"><span class="min-w-0"><span class="font-semibold block truncate"><?= e($r['title']) ?></span><span class="text-xs text-slate-500"><?= e($r['file_name']) ?> &middot; <?= e(number_format($r['file_size'] / 1048576, 1)) ?> MB</span></span>
            <a class="btn btn-ghost !py-1.5" href="<?= e(url('/learn/resource/' . $r['id'])) ?>">Download</a></li><?php endforeach; ?></ul>
        </div>
        <div id="panel-reviews" role="tabpanel" aria-labelledby="tab-reviews" class="py-5" hidden>
          <h2 class="font-bold text-xl"><?= $review ? 'Update your review' : 'How is this course?' ?></h2>
          <form method="post" action="<?= e(url('/learn/' . $slug . '/review')) ?>" class="mt-3 space-y-3 max-w-xl"><?= csrf_field() ?>
            <fieldset><legend class="sr-only">Your rating</legend>
              <div class="star-input"><?php for ($s = 5; $s >= 1; $s--): ?><input type="radio" id="star<?= $s ?>" name="rating" value="<?= $s ?>" <?= (int) old('rating', $review['rating'] ?? 0) === $s ? 'checked' : '' ?> required><label for="star<?= $s ?>" title="<?= $s ?> star<?= $s > 1 ? 's' : '' ?>">&#9733;<span class="sr-only"> <?= $s ?> star<?= $s > 1 ? 's' : '' ?></span></label><?php endfor; ?></div>
              <?= field_error('rating') ?></fieldset>
            <div><label class="label" for="comment">Tell others what you think (optional)</label><textarea class="field" id="comment" name="comment" rows="4"><?= e(old('comment', $review['comment'] ?? '')) ?></textarea></div>
            <button class="btn btn-primary" type="submit"><?= $review ? 'Update review' : 'Submit review' ?></button>
          </form>
        </div>
      </div>
    </div>
  </main>

  <aside class="pl-side" aria-label="Course content">
    <div class="p-4 border-b"><h2 class="font-display font-bold text-lg">Course content</h2>
      <div class="bar mt-2"><span data-progress-bar style="width:<?= (int) $summary['percent'] ?>%"></span></div>
      <p class="text-sm text-slate-600 mt-1" data-progress-text><?= (int) $summary['done'] ?> of <?= (int) $summary['total'] ?> complete</p></div>
    <?php foreach ($curriculum as $si => $sec): $has = false; foreach ($sec['lessons'] as $l) { if ((int) $l['id'] === $lid) $has = true; } ?>
      <details class="pl-sec" <?= $has ? 'open' : '' ?>>
        <summary><span>Section <?= $si + 1 ?>: <?= e($sec['title']) ?></span><span class="font-normal text-xs text-slate-600 shrink-0"><?= count($sec['lessons']) ?></span></summary>
        <?php foreach ($sec['lessons'] as $l): $n++; $isDone = !empty($progress[(int) $l['id']]['completed']); $ico = ['video' => '&#9654; ', 'article' => '&#9776; ', 'quiz' => '? Quiz '][$l['lesson_type']]; ?>
          <a class="pl-lesson <?= $isDone ? 'done' : '' ?>" href="<?= e($lurl($l)) ?>" <?= (int) $l['id'] === $lid ? 'aria-current="true"' : '' ?>>
            <span class="pl-check" aria-hidden="true"></span>
            <span class="min-w-0"><span class="block text-sm"><?= $n ?>. <?= e($l['title']) ?></span>
              <span class="text-xs text-slate-500"><?= $ico ?><?= $l['lesson_type'] === 'quiz' ? '' : e(fmt_duration((int) $l['duration_seconds'])) ?><span class="sr-only"><?= $isDone ? ' (completed)' : '' ?></span></span></span>
          </a>
        <?php endforeach; ?>
      </details>
    <?php endforeach; ?>
  </aside>
</div>
</div>
<script>
// Tabs work even if player.js fails to load.
(function () {
  var tabs = [].slice.call(document.querySelectorAll('#player [role=tab]'));
  if (!tabs.length) return;
  function show(t) { tabs.forEach(function (x) { var on = x === t; x.setAttribute('aria-selected', String(on)); x.tabIndex = on ? 0 : -1; var p = document.getElementById(x.getAttribute('aria-controls')); if (p) p.hidden = !on; }); }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { show(t); });
    t.addEventListener('keydown', function (e) {
      var n = e.key === 'ArrowRight' ? tabs[(i + 1) % tabs.length] : e.key === 'ArrowLeft' ? tabs[(i - 1 + tabs.length) % tabs.length] : null;
      if (n) { e.preventDefault(); n.focus(); show(n); }
    });
  });
  if (location.hash === '#reviews') show(document.getElementById('tab-reviews'));
})();
</script>
<script src="<?= e(asset('js/app.js')) ?>" defer></script>
<script src="<?= e(asset('js/player.js')) ?>" defer></script>
</body>
</html>
