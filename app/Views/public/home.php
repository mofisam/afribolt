<?php
$next = null;
foreach ($cohorts as $c) { if ($c['start_at'] > utc_now() && in_array($c['status'], ['published', 'open'], true)) { $next = $c; break; } }
?>
<section class="bg-ink text-white">
  <div class="max-w-6xl mx-auto px-4 py-16 md:py-24 grid md:grid-cols-2 gap-10 items-center">
    <div>
      <h1 class="font-display font-extrabold text-4xl md:text-5xl leading-tight">Live classes. Fixed start dates. A real cohort beside you.</h1>
      <p class="mt-5 text-lg text-slate-200 max-w-prose">Pick a programme, secure your seat, and count down to day one. Every class is taught live, so you learn with other people and ask questions as they come up.</p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a class="btn btn-accent" href="<?= e(url('/courses')) ?>">Browse courses</a>
        <a class="btn border-white text-white hover:bg-white hover:text-ink" href="<?= e(url('/career-accelerator')) ?>">Career Accelerator</a>
      </div>
    </div>
    <div class="bg-white/10 border border-white/20 rounded-lg p-6">
      <?php if ($next): ?>
        <p class="text-volt font-bold"><?= e($next['course_title']) ?></p>
        <p class="text-xl font-display font-bold mb-4"><?= e($next['cohort_name']) ?></p>
        <?= App\Core\View::partial('partials/countdown', ['target' => utc_iso($next['start_at']), 'label' => 'Next cohort starts in', 'dark' => true, 'reload' => true]) ?>
        <a class="btn btn-primary mt-6" href="<?= e(url('/cohorts/' . $next['cohort_code'])) ?>">See details &amp; register</a>
      <?php else: ?>
        <p class="font-display font-bold text-2xl">New cohorts are being scheduled.</p>
        <p class="mt-2 text-slate-200">Tell us what you want to learn and we'll let you know when the next one opens.</p>
        <a class="btn btn-primary mt-6" href="<?= e(url('/contact')) ?>">Get in touch</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="max-w-6xl mx-auto px-4 mt-16">
  <h2 class="text-3xl font-bold">How it works</h2>
  <ol class="mt-6 grid md:grid-cols-4 gap-4">
    <?php foreach (['Choose a course and a cohort date', 'Create an account and register', 'Pay securely, your seat is confirmed once we verify it', 'Join each live class from your dashboard'] as $i => $step): ?>
      <li class="border border-slate-200 rounded-lg p-4 bg-white"><span class="font-display font-extrabold text-bolt text-2xl"><?= $i + 1 ?></span><p class="mt-1"><?= e($step) ?></p></li>
    <?php endforeach; ?>
  </ol>
</section>

<?php if ($cohorts): ?>
<section class="max-w-6xl mx-auto px-4 mt-16">
  <div class="flex items-end justify-between"><h2 class="text-3xl font-bold">Upcoming cohorts</h2><a class="font-semibold text-bolt-dark underline" href="<?= e(url('/courses')) ?>">All courses</a></div>
  <div class="mt-6 grid md:grid-cols-3 gap-5">
    <?php foreach ($cohorts as $c): ?><?= App\Core\View::partial('partials/cohort-card', ['c' => $c]) ?><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($courses): ?>
<section class="max-w-6xl mx-auto px-4 mt-16">
  <h2 class="text-3xl font-bold">Programmes</h2>
  <div class="mt-6 grid md:grid-cols-3 gap-5">
    <?php foreach ($courses as $course): ?><?= App\Core\View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="max-w-6xl mx-auto px-4 mt-16">
  <div class="bg-bolt-tint rounded-lg p-8 md:flex items-center justify-between gap-6">
    <div><h2 class="text-2xl font-bold">Looking for your next role?</h2>
    <p class="mt-1 max-w-prose">The Career Accelerator helps graduates and job seekers with CV revamps, interview coaching, internships and job-search strategy.</p></div>
    <a class="btn btn-primary mt-4 md:mt-0" href="<?= e(url('/career-accelerator')) ?>">Explore career support</a>
  </div>
</section>
