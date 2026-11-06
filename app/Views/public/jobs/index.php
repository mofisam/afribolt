<?php
use App\Core\View;
$g = fn(string $k) => (string) ($f[$k] ?? '');
$qs = fn(array $o) => '?' . http_build_query(array_filter(array_merge($f, $o, ['page' => null]), fn($v) => $v !== null && $v !== ''));
$keep = function (array $skip) use ($f) { $h = ''; foreach ($f as $k => $v) { if (!in_array($k, $skip, true)) { $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">'; } } return $h; };
$opt = fn(string $name, string $val, string $label) => '<label class="opt"><input type="radio" name="' . e($name) . '" value="' . e($val) . '" ' . ($g($name) === $val ? 'checked' : '') . '> ' . e($label) . '</label>';
$active = count(array_diff_key($f, ['sort' => 1, 'q' => 1, 'state' => 1, 'category' => 1]));
?>
<section class="bg-ink text-white">
  <div class="max-w-6xl mx-auto px-4 py-10 md:py-12">
    <h1 class="text-3xl md:text-5xl font-extrabold">Find your next job in Nigeria</h1>
    <p class="mt-2 text-slate-200 max-w-2xl">Fresh openings from trusted sources, tidied up and checked for scams. Learn the skills you need on AfriBolt, then apply.</p>
    <form method="get" role="search" class="mt-6 grid md:grid-cols-[1fr_14rem_auto] gap-3 bg-white rounded-lg p-3">
      <?= $keep(['q', 'state']) ?>
      <div><label class="sr-only" for="jq">Job title, skill or company</label><input class="field" id="jq" type="search" name="q" value="<?= e($g('q')) ?>" placeholder="Job title, skill or company"></div>
      <div><label class="sr-only" for="jstate">State</label><select class="field" id="jstate" name="state"><option value="">All of Nigeria</option>
        <?php foreach ($states as $s): ?><option value="<?= e($s) ?>" <?= $g('state') === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-accent" type="submit">Search jobs</button>
    </form>
  </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-6">
  <?php if ($categories): ?>
  <nav class="catchips" aria-label="Job categories">
    <a href="<?= e($qs(['category' => null])) ?>" <?= $g('category') === '' ? 'aria-current="true"' : '' ?>>All</a>
    <?php foreach ($categories as $c): ?><a href="<?= e($qs(['category' => $c['slug']])) ?>" <?= $g('category') === $c['slug'] ? 'aria-current="true"' : '' ?>><?= e($c['name']) ?> (<?= (int) $c['n'] ?>)</a><?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <div class="mt-6 grid lg:grid-cols-[15rem_minmax(0,1fr)] gap-8">
    <form id="filters" method="get" class="filters">
      <?= $keep(['type', 'mode', 'level', 'posted']) ?>
      <details open data-collapse-mobile data-active="<?= (int) $active ?>">
        <summary class="btn btn-ghost w-full justify-between lg:!hidden">Filters <?= $active ? '(' . (int) $active . ')' : '' ?></summary>
        <div class="pt-2">
          <details open><summary>Job type</summary><div class="pt-1"><?= $opt('type', '', 'Any') ?><?= $opt('type', 'full_time', 'Full time') ?><?= $opt('type', 'part_time', 'Part time') ?><?= $opt('type', 'contract', 'Contract') ?><?= $opt('type', 'internship', 'Internship / trainee') ?></div></details>
          <details open><summary>Work mode</summary><div class="pt-1"><?= $opt('mode', '', 'Any') ?><?= $opt('mode', 'onsite', 'On-site') ?><?= $opt('mode', 'remote', 'Remote') ?><?= $opt('mode', 'hybrid', 'Hybrid') ?></div></details>
          <details open><summary>Experience</summary><div class="pt-1"><?= $opt('level', '', 'Any') ?><?= $opt('level', 'entry', 'Entry level') ?><?= $opt('level', 'mid', 'Mid level') ?><?= $opt('level', 'senior', 'Senior') ?></div></details>
          <details><summary>Posted</summary><div class="pt-1"><?= $opt('posted', '', 'Any time') ?><?= $opt('posted', '1', 'Last 24 hours') ?><?= $opt('posted', '7', 'Last 7 days') ?><?= $opt('posted', '30', 'Last 30 days') ?></div></details>
          <button class="btn btn-primary w-full mt-3" type="submit" data-js-hide>Apply filters</button>
          <?php if ($f): ?><a class="block text-center underline text-bolt-dark mt-3" href="<?= e(url('/jobs')) ?>">Clear all</a><?php endif; ?>
        </div>
      </details>
    </form>

    <div>
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <p class="font-bold"><?= (int) $total ?> job<?= $total == 1 ? '' : 's' ?><?= $g('q') !== '' ? ' for &ldquo;' . e($g('q')) . '&rdquo;' : '' ?></p>
        <div class="flex gap-3 text-sm"><a class="underline text-bolt-dark" href="<?= e($qs(['sort' => null])) ?>">Newest</a><a class="underline text-bolt-dark" href="<?= e($qs(['sort' => 'deadline'])) ?>">Closing soon</a>
          <?php if (App\Core\Auth::check('student')): ?><a class="underline text-bolt-dark font-bold" href="<?= e(url('/student/jobs')) ?>">Jobs for you</a><?php endif; ?></div>
      </div>
      <p class="text-sm bg-yellow-50 border border-yellow-200 rounded p-3 mb-4" role="note"><strong>Stay safe:</strong> never pay to get a job. Jobs are collected from public sources and summarised by AI, so always check the details on the employer's own page before you apply.</p>

      <?php if (!$jobs): ?>
        <div class="bg-white border rounded-lg p-10 text-center"><p class="text-xl font-bold">No jobs match yet.</p><p class="mt-1 text-slate-600">Try fewer filters or a different word. New jobs arrive every day.</p>
          <a class="btn btn-primary mt-4" href="<?= e(url('/jobs')) ?>">Clear filters</a></div>
      <?php else: ?>
        <div class="space-y-3"><?php foreach ($jobs as $job): ?><?= View::partial('partials/job-card', ['job' => $job, 'saved' => in_array((int) $job['id'], $saved, true)]) ?><?php endforeach; ?></div>
        <?= View::partial('admin/partials/pager', ['pager' => $pager]) ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= e(asset('js/course.js')) ?>" defer></script>
