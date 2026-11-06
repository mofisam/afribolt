<?php
$g = fn(string $k) => (string) ($f[$k] ?? '');
$opt = function (string $name, string $val, string $label) use ($g) {
    return '<label class="opt"><input type="radio" name="' . e($name) . '" value="' . e($val) . '" ' . ($g($name) === $val ? 'checked' : '') . '> ' . e($label) . '</label>';
};
$active = count($f) - (isset($f['sort']) ? 1 : 0);
?>
<section class="bg-bolt-tint border-b border-slate-200">
  <div class="max-w-6xl mx-auto px-4 py-8 md:py-10">
    <h1 class="text-3xl md:text-4xl font-extrabold"><?= $g('q') !== '' ? 'Results for &ldquo;' . e($g('q')) . '&rdquo;' : 'All courses' ?></h1>
    <p class="mt-1 text-slate-700"><?= (int) $total ?> course<?= $total == 1 ? '' : 's' ?>. Learn live with a cohort or at your own pace.</p>
  </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-8 grid lg:grid-cols-[15rem_minmax(0,1fr)] gap-8">
  <form id="filters" method="get" class="filters">
    <details class="!border-0 !p-0" open data-collapse-mobile data-active="<?= (int) $active ?>">
      <summary class="btn btn-ghost w-full justify-between lg:!hidden">Filters <?= $active ? '(' . (int) $active . ')' : '' ?></summary>
      <div class="pt-2">
        <?php if ($g('q') !== ''): ?><input type="hidden" name="q" value="<?= e($g('q')) ?>"><?php endif; ?>
        <?php if ($g('sort') !== ''): ?><input type="hidden" name="sort" value="<?= e($g('sort')) ?>"><?php endif; ?>
        <?= App\Core\View::partial('public/courses/_filters', compact('opt', 'categories', 'g')) ?>
        <button class="btn btn-primary w-full mt-3" type="submit" data-js-hide>Apply filters</button>
        <?php if ($active): ?><a class="block text-center underline text-bolt-dark mt-3" href="<?= e(url('/courses')) ?>">Clear all</a><?php endif; ?>
      </div>
    </details>
  </form>

  <div>
    <form method="get" class="flex flex-wrap items-center justify-between gap-3 mb-5">
      <?php foreach ($f as $k => $v): if ($k === 'sort') continue; ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
      <p class="text-slate-600"><?= (int) $total ?> result<?= $total == 1 ? '' : 's' ?></p>
      <label class="flex items-center gap-2 font-semibold">Sort by
        <select class="field !w-auto !py-1.5" name="sort" onchange="this.form.submit()">
          <?php foreach (['popular' => 'Most popular', 'newest' => 'Newest', 'rating' => 'Highest rated', 'price_low' => 'Price: low to high', 'price_high' => 'Price: high to low'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= ($g('sort') ?: 'popular') === $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select></label>
    </form>

    <?php if (!$courses): ?>
      <div class="bg-white border rounded-lg p-10 text-center"><p class="text-xl font-bold">No courses match those filters.</p>
        <p class="mt-1 text-slate-600">Try removing a filter or searching for something else.</p><a class="btn btn-primary mt-4" href="<?= e(url('/courses')) ?>">Clear filters</a></div>
    <?php else: ?>
      <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-5"><?php foreach ($courses as $course): ?><?= App\Core\View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?></div>
      <?= App\Core\View::partial('admin/partials/pager', ['pager' => $pager]) ?>
    <?php endif; ?>
  </div>
</div>
<script src="<?= e(asset('js/course.js')) ?>" defer></script>
