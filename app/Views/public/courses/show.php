<?php
use App\Models\Course;
use App\Core\View;
$price = Course::priceNow($course); $sale = Course::onPromo($course);
$cur = $course['price_currency'];
$objectives = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $course['learning_objectives']))));
$total = array_sum($dist);
$lessonCount = (int) $course['lessons_count'];
?>
<div class="max-w-6xl mx-auto px-4 cgrid">
  <!-- Hero -->
  <header class="chero">
    <nav class="text-sm" aria-label="Breadcrumb"><a href="<?= e(url('/courses')) ?>">Courses</a>
      <?php if ($course['category_name']): ?> &rsaquo; <a href="<?= e(url('/courses?category=' . $course['category_slug'])) ?>"><?= e($course['category_name']) ?></a><?php endif; ?></nav>
    <h1 class="mt-3 text-3xl md:text-4xl font-extrabold leading-tight"><?= e($course['title']) ?></h1>
    <?php if ($course['short_description']): ?><p class="mt-3 text-lg text-slate-200 max-w-3xl"><?= e($course['short_description']) ?></p><?php endif; ?>
    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
      <?php if ($selfPaced && (int) $course['rating_count'] > 0): ?>
        <span class="inline-flex items-center gap-1 text-amber-300"><strong><?= e(number_format((float) $course['rating_avg'], 1)) ?></strong>
          <span class="stars" style="--p:<?= round($course['rating_avg'] / 5 * 100) ?>%" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span></span>
        <a href="#reviews">(<?= number_format((int) $course['rating_count']) ?> rating<?= $course['rating_count'] == 1 ? '' : 's' ?>)</a>
      <?php endif; ?>
      <?php if ($selfPaced && (int) $course['learners_count'] > 0): ?><span><?= number_format((int) $course['learners_count']) ?> learner<?= $course['learners_count'] == 1 ? '' : 's' ?></span><?php endif; ?>
    </div>
    <?php if ($course['instructor_name']): ?><p class="mt-2 text-sm">Created by <strong><?= e($course['instructor_name']) ?></strong></p><?php endif; ?>
    <p class="mt-2 text-sm text-slate-300">Last updated <?= e(local_date($course['updated_at'], null, 'F Y')) ?> &middot; <?= e($course['language']) ?> &middot; <?= e(str_replace('_', ' ', ucfirst($course['course_level']))) ?></p>
  </header>

  <!-- Purchase card -->
  <aside class="cside mt-6 lg:mt-7" aria-label="Enrol">
    <div class="buycard">
      <?php if ($previewEmbed): ?>
        <div class="media"><?php if ($previewFile): ?><video src="<?= e($previewEmbed) ?>" controls preload="metadata" poster="<?= !empty($course['thumbnail_url']) ? e(asset($course['thumbnail_url'])) : '' ?>"></video>
          <?php else: ?><iframe src="<?= e($previewEmbed) ?>" title="Course preview" loading="lazy" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe><?php endif; ?></div>
      <?php elseif (!empty($course['thumbnail_url'])): ?><div class="media"><img src="<?= e(asset($course['thumbnail_url'])) ?>" alt=""></div><?php endif; ?>
      <div class="p-5">
        <?php if ($selfPaced): ?>
          <?php if ($source): ?>
            <?php if ($progress): ?><p class="font-bold"><?= (int) $progress['percent'] ?>% complete</p><div class="bar mt-2"><span style="width:<?= (int) $progress['percent'] ?>%"></span></div><?php endif; ?>
            <a class="btn btn-primary w-full mt-4 !py-3" href="<?= e(url('/learn/' . $course['slug'])) ?>"><?= $progress && $progress['done'] > 0 ? 'Continue learning' : 'Start learning' ?></a>
          <?php else: ?>
            <p class="flex items-baseline gap-3"><span class="font-display font-extrabold text-3xl"><?= e($price > 0 ? money($price, $cur) : 'Free') ?></span>
              <?php if ($sale): ?><s class="text-slate-500 text-lg"><?= e(money($course['price'], $cur)) ?></s><span class="tag tag-sale"><?= (int) round(100 * (1 - $price / (float) $course['price'])) ?>% off</span><?php endif; ?></p>
            <?php if ($sale && $course['promo_ends_at']): ?><div class="mt-3 rounded bg-bolt-tint p-3"><?= View::partial('partials/countdown', ['target' => utc_iso($course['promo_ends_at']), 'label' => 'Sale ends in', 'reload' => true]) ?></div><?php endif; ?>
            <?php if (App\Core\Auth::check('student')): ?>
              <form method="post" action="<?= e(url('/courses/' . $course['slug'] . '/enroll')) ?>" class="mt-4"><?= csrf_field() ?>
                <button class="btn btn-accent w-full !py-3 text-lg" type="submit"><?= $price > 0 ? 'Buy now' : 'Enrol for free' ?></button></form>
            <?php else: ?>
              <a class="btn btn-accent w-full mt-4 !py-3 text-lg" href="<?= e(url('/login')) ?>"><?= $price > 0 ? 'Log in to buy' : 'Log in to enrol' ?></a>
              <p class="text-center text-sm mt-2">New here? <a class="underline font-bold text-bolt-dark" href="<?= e(url('/register')) ?>">Create an account</a></p>
            <?php endif; ?>
            <p class="text-center text-xs text-slate-500 mt-2">Secure checkout. Card details never touch our servers.</p>
          <?php endif; ?>
          <h2 class="mt-5 font-bold">This course includes</h2>
          <ul class="mt-2 space-y-1.5 text-sm">
            <?php if ($course['total_seconds'] > 0): ?><li>&#9654; <?= e(fmt_duration((int) $course['total_seconds'], true)) ?> of on-demand content</li><?php endif; ?>
            <li>&#9776; <?= $lessonCount ?> lesson<?= $lessonCount == 1 ? '' : 's' ?></li>
            <?php if ($resourceCount): ?><li>&#8681; <?= (int) $resourceCount ?> downloadable resource<?= $resourceCount == 1 ? '' : 's' ?></li><?php endif; ?>
            <li>&#8734; Full lifetime access</li><li>&#128241; Learn on phone, tablet or computer</li>
            <?php if ($course['has_certificate']): ?><li>&#127942; Certificate of completion</li><?php endif; ?>
          </ul>
        <?php endif; ?>
        <?php if ($cohorts || $course['delivery_mode'] === 'live'): ?>
          <a class="btn <?= $selfPaced ? 'btn-ghost mt-5' : 'btn-accent mt-0 !py-3' ?> w-full" href="#cohorts"><?= $selfPaced ? 'Or join a live cohort' : 'See live cohort dates' ?></a>
        <?php endif; ?>
        <button type="button" class="mt-3 w-full text-center underline text-bolt-dark text-sm" data-copy="<?= e(url('/courses/' . $course['slug'])) ?>">Copy link to share</button>
      </div>
    </div>
  </aside>

  <!-- Body -->
  <div class="cmain py-8 space-y-10">
    <?php if ($objectives): ?>
    <section class="border border-slate-300 rounded-lg p-6" aria-labelledby="learn-h">
      <h2 id="learn-h" class="text-2xl font-extrabold">What you'll learn</h2>
      <ul class="checks mt-4 md:grid-cols-2 md:grid"><?php foreach ($objectives as $o): ?><li><?= e($o) ?></li><?php endforeach; ?></ul>
    </section>
    <?php endif; ?>

    <?php if ($selfPaced && $curriculum): ?>
    <section aria-labelledby="content-h">
      <h2 id="content-h" class="text-2xl font-extrabold">Course content</h2>
      <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-sm text-slate-600">
        <span><?= count($curriculum) ?> section<?= count($curriculum) == 1 ? '' : 's' ?> &middot; <?= $lessonCount ?> lesson<?= $lessonCount == 1 ? '' : 's' ?> &middot; <?= e(fmt_duration((int) $course['total_seconds'], true)) ?> total</span>
        <button type="button" class="underline text-bolt-dark font-bold" data-expand-all>Expand all sections</button></div>
      <div class="mt-3">
        <?php foreach ($curriculum as $i => $sec): ?>
          <details class="cur-sec" <?= $i === 0 ? 'open' : '' ?>>
            <summary><span><?= e($sec['title']) ?></span><span class="font-normal text-sm text-slate-600 shrink-0"><?= count($sec['lessons']) ?> lessons &middot; <?= e(fmt_duration((int) $sec['seconds'], true)) ?></span></summary>
            <?php foreach ($sec['lessons'] as $l): $ico = ['video' => '&#9654;', 'article' => '&#9776;', 'quiz' => '?'][$l['lesson_type']]; ?>
              <div class="cur-lesson"><span class="flex gap-3 min-w-0"><span class="text-slate-500 w-4 text-center" aria-hidden="true"><?= $ico ?></span>
                <?php if ($l['is_preview']): ?><a class="underline text-bolt-dark" href="<?= e(url('/courses/' . $course['slug'] . '/preview/' . $l['id'])) ?>"><?= e($l['title']) ?></a> <span class="tag">Preview</span>
                <?php else: ?><span><?= e($l['title']) ?></span><?php endif; ?></span>
                <span class="text-slate-500 shrink-0"><?= $l['lesson_type'] === 'quiz' ? 'Quiz' : e(fmt_duration((int) $l['duration_seconds'])) ?></span></div>
            <?php endforeach; ?>
          </details>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($course['prerequisites']): ?><section><h2 class="text-2xl font-extrabold">Requirements</h2><ul class="mt-3 list-disc pl-6 space-y-1"><?php foreach (array_filter(array_map('trim', preg_split('/\R/', (string) $course['prerequisites']))) as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul></section><?php endif; ?>

    <?php if ($course['description']): ?><section><h2 class="text-2xl font-extrabold">Description</h2><div class="mt-3 prose-lite"><?= render_content((string) $course['description']) ?></div></section><?php endif; ?>

    <?php if ($course['instructor_name']): ?>
    <section aria-labelledby="inst-h"><h2 id="inst-h" class="text-2xl font-extrabold">Your instructor</h2>
      <div class="mt-3 flex gap-4"><span class="adm-avatar !w-14 !h-14 text-lg shrink-0" aria-hidden="true"><?= e(strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $course['instructor_name']), 0, 2))))) ?></span>
        <div><p class="font-bold text-lg"><?= e($course['instructor_name']) ?></p><?php if ($course['instructor_bio']): ?><p class="mt-1 text-slate-700 whitespace-pre-line"><?= e($course['instructor_bio']) ?></p><?php endif; ?></div></div></section>
    <?php endif; ?>

    <?php if ($selfPaced && $total > 0): ?>
    <section id="reviews" aria-labelledby="rev-h" style="scroll-margin-top:5rem"><h2 id="rev-h" class="text-2xl font-extrabold">Learner reviews</h2>
      <div class="mt-4 grid md:grid-cols-[10rem_1fr] gap-6 items-center">
        <div class="text-center"><p class="font-display font-extrabold text-5xl"><?= e(number_format((float) $course['rating_avg'], 1)) ?></p>
          <?= View::partial('partials/stars', ['rating' => $course['rating_avg']]) ?><p class="text-sm text-slate-600"><?= (int) $total ?> rating<?= $total == 1 ? '' : 's' ?></p></div>
        <div class="space-y-1.5"><?php for ($s = 5; $s >= 1; $s--): $pc = round(100 * $dist[$s] / $total); ?>
          <div class="dist-row"><span><?= $s ?> star<?= $s == 1 ? '' : 's' ?></span><div class="bar"><span style="width:<?= $pc ?>%"></span></div><span class="text-slate-600"><?= $pc ?>%</span></div><?php endfor; ?></div>
      </div>
      <ul class="mt-6 divide-y"><?php foreach ($reviews as $r): ?>
        <li class="py-4"><p class="font-bold"><?= e($r['first_name'] . ' ' . mb_substr($r['last_name'], 0, 1) . '.') ?></p>
          <p class="flex items-center gap-2 text-sm"><?= View::partial('partials/stars', ['rating' => $r['rating']]) ?><span class="text-slate-500"><?= e(local_date($r['created_at'], null, 'j M Y')) ?></span></p>
          <p class="mt-1 whitespace-pre-line"><?= e($r['comment']) ?></p></li><?php endforeach; ?></ul>
    </section>
    <?php endif; ?>

    <?php if ($cohorts || $course['delivery_mode'] === 'live'): ?>
    <section id="cohorts" aria-labelledby="coh-h" style="scroll-margin-top:5rem"><h2 id="coh-h" class="text-2xl font-extrabold">Live cohorts</h2>
      <p class="mt-1 text-slate-600">Prefer to learn with an instructor and a group? Join a live cohort<?= $course['cohort_includes_access'] && $selfPaced ? ' (you also get the on-demand course material)' : '' ?>.</p>
      <?php if (!$cohorts): ?><p class="mt-3 text-slate-600">No cohort is open right now. <a class="underline text-bolt-dark" href="<?= e(url('/contact?type=course')) ?>">Ask to be notified</a>.</p>
      <?php else: ?><div class="mt-4 grid sm:grid-cols-2 gap-5"><?php foreach ($cohorts as $c): ?><?= View::partial('partials/cohort-card', ['c' => $c]) ?><?php endforeach; ?></div><?php endif; ?>
    </section>
    <?php endif; ?>
  </div>
</div>
<script src="<?= e(asset('js/course.js')) ?>" defer></script>
