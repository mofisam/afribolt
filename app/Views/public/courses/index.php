<?php
use App\Core\View;
$g = fn(string $k) => (string) ($f[$k] ?? '');
// Build a link that keeps the current filters but changes some (and resets paging)
$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge($f, $o, ['page' => null]), fn($v) => $v !== null && $v !== ''));
// Hidden inputs so one form keeps the values that live in the other form
$keep = function (array $skip) use ($f) { $h = ''; foreach ($f as $k => $v) { if (!in_array($k, $skip, true)) { $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; } } return $h; };
$opt = fn(string $name, string $val, string $label) => '<label class="opt"><input type="radio" name="' . e($name) . '" value="' . e($val) . '" ' . ($g($name) === $val ? 'checked' : '') . '> <span>' . e($label) . '</span></label>';
$rate = function (string $val, string $label) use ($g) {
    $stars = $val !== '' ? '<span class="stars" style="--p:' . round((float) $val / 5 * 100) . '%" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span> ' : '';
    return '<label class="opt"><input type="radio" name="rating" value="' . e($val) . '" ' . ($g('rating') === $val ? 'checked' : '') . '> ' . $stars . '<span>' . e($label) . '</span></label>';
};
$active = count(array_intersect_key($f, array_flip(['mode', 'price', 'level', 'rating'])));
$sorts = ['popular' => 'Most popular', 'newest' => 'Newest', 'rating' => 'Highest rated', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'];
$curSort = $g('sort') ?: 'popular';
?>
<section class="bg-ink text-white">
  <div class="max-w-6xl mx-auto px-4 py-10 md:py-12">
    <h1 class="text-3xl md:text-5xl font-extrabold">Learn at your own pace</h1>
    <p class="mt-2 text-slate-200 max-w-2xl">Start any course today, or join a live cohort. Earn a certificate, then find jobs that match what you learned.</p>
    <form method="get" role="search" class="mt-6 grid md:grid-cols-[1fr_14rem_auto] gap-3 bg-white rounded-lg p-3">
      <?= $keep(['q', 'mode']) ?>
      <div><label class="sr-only" for="cq">What do you want to learn?</label><input class="field" id="cq" type="search" name="q" value="<?= e($g('q')) ?>" placeholder="What do you want to learn?"></div>
      <div><label class="sr-only" for="cmode">Format</label><select class="field" id="cmode" name="mode">
        <option value="">Any format</option><option value="self_paced" <?= $g('mode') === 'self_paced' ? 'selected' : '' ?>>Self-paced (on demand)</option><option value="live" <?= $g('mode') === 'live' ? 'selected' : '' ?>>Live cohort</option></select></div>
      <button class="btn btn-accent" type="submit">Search courses</button>
    </form>
  </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-6">
  <?php if ($categories): ?>
  <nav class="catchips" aria-label="Course topics">
    <a href="<?= e($qs(['category' => null])) ?>" <?= $g('category') === '' ? 'aria-current="true"' : '' ?>>All topics</a>
    <?php foreach ($categories as $c): ?><a href="<?= e($qs(['category' => $c['slug']])) ?>" <?= $g('category') === $c['slug'] ? 'aria-current="true"' : '' ?>><?= e($c['name']) ?> (<?= (int) $c['n'] ?>)</a><?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <div class="mt-6 grid lg:grid-cols-[15rem_minmax(0,1fr)] gap-8">
    <form id="filters" method="get" class="filters">
      <?= $keep(['mode', 'price', 'level', 'rating']) ?>
      <details open data-collapse-mobile data-active="<?= (int) $active ?>">
        <summary class="btn btn-ghost w-full justify-between lg:!hidden">Filters <?= $active ? '(' . (int) $active . ')' : '' ?></summary>
        <div class="pt-2">
          <details open><summary>Learn how</summary><div class="pt-1"><?= $opt('mode', '', 'Any format') ?><?= $opt('mode', 'self_paced', 'Self-paced (on demand)') ?><?= $opt('mode', 'live', 'Live cohort') ?></div></details>
          <details open><summary>Price</summary><div class="pt-1"><?= $opt('price', '', 'Any price') ?><?= $opt('price', 'free', 'Free') ?><?= $opt('price', 'paid', 'Paid') ?></div></details>
          <details open><summary>Rating</summary><div class="pt-1"><?= $rate('', 'Any rating') ?><?= $rate('4.5', '4.5 & up') ?><?= $rate('4', '4.0 & up') ?><?= $rate('3', '3.0 & up') ?></div></details>
          <details><summary>Level</summary><div class="pt-1"><?= $opt('level', '', 'All levels') ?><?= $opt('level', 'beginner', 'Beginner') ?><?= $opt('level', 'intermediate', 'Intermediate') ?><?= $opt('level', 'advanced', 'Advanced') ?></div></details>
          <button class="btn btn-primary w-full mt-3" type="submit" data-js-hide>Apply filters</button>
          <?php if ($f): ?><a class="block text-center underline text-bolt-dark mt-3" href="<?= e(url('/courses')) ?>">Clear all</a><?php endif; ?>
        </div>
      </details>
    </form>

    <div>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="font-bold"><?= (int) $total ?> course<?= $total == 1 ? '' : 's' ?><?= $g('q') !== '' ? ' for &ldquo;' . e($g('q')) . '&rdquo;' : '' ?></p>
        <nav class="sortlinks flex flex-wrap gap-x-4 gap-y-1 text-sm" aria-label="Sort courses">
          <?php foreach ($sorts as $k => $l): ?><a href="<?= e($qs(['sort' => $k === 'popular' ? null : $k])) ?>" <?= $curSort === $k ? 'aria-current="true"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
        </nav>
      </div>

      <?php if (!$courses): ?>
        <div class="bg-white border rounded-lg p-10 text-center"><p class="text-xl font-bold">No courses match yet.</p><p class="mt-1 text-slate-600">Try fewer filters or a different word. New courses are added often.</p>
          <a class="btn btn-primary mt-4" href="<?= e(url('/courses')) ?>">Clear filters</a></div>
      <?php else: ?>
        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5"><?php foreach ($courses as $course): ?><?= View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?></div>
        <?= View::partial('admin/partials/pager', ['pager' => $pager]) ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/course.js')) ?>" defer></script>
