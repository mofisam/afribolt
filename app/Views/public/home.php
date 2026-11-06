<?php use App\Core\{Auth, View}; ?>
<section class="bg-ink text-white">
  <div class="max-w-6xl mx-auto px-4 py-14 md:py-20 grid lg:grid-cols-[1.2fr_1fr] gap-10 items-center">
    <div>
      <h1 class="font-display font-extrabold text-4xl md:text-6xl leading-[1.05]">Learn at your own pace.<br><span class="text-volt">Then get hired.</span></h1>
      <p class="mt-5 text-lg text-slate-200 max-w-xl">Start any course today, learn on your phone or laptop, and earn a certificate when you finish. Then find real jobs in Nigeria that match the skills you've built.</p>
      <form action="<?= e(url('/courses')) ?>" method="get" role="search" class="mt-7 flex max-w-xl gap-2 bg-white rounded-lg p-2">
        <label class="sr-only" for="hq">What do you want to learn?</label>
        <input id="hq" class="field !border-0 !shadow-none" type="search" name="q" placeholder="What do you want to learn?">
        <button class="btn btn-accent shrink-0" type="submit">Search</button>
      </form>
      <div class="mt-5 flex flex-wrap gap-3">
        <a class="btn btn-primary" href="<?= e(url('/courses?mode=self_paced')) ?>">Browse courses</a>
        <a class="btn border-white text-white hover:bg-white hover:text-ink" href="<?= e(url('/jobs')) ?>">Find jobs<?= $jobTotal ? ' (' . (int) $jobTotal . ')' : '' ?></a>
      </div>
    </div>
    <ul class="grid gap-3" aria-label="Why learn here">
      <?php foreach ([['Start any time', 'No fixed dates. Learn whenever it suits you, and pick up exactly where you left off.'], ['Learn on any device', 'Videos, reading and quizzes that work on a phone as well as a laptop.'],
          ['Certificates you can share', 'Finish a course and get a certificate employers can verify online.'], ['Jobs that fit what you learn', 'Fresh Nigerian openings, tidied up and checked for scams.']] as $i => [$t, $d]): ?>
        <li class="flex gap-4 bg-white/10 border border-white/20 rounded-lg p-4"><span class="grid place-items-center w-9 h-9 rounded-full bg-volt text-ink font-extrabold shrink-0" aria-hidden="true"><?= $i + 1 ?></span>
          <span><span class="block font-bold"><?= e($t) ?></span><span class="text-slate-200 text-sm"><?= e($d) ?></span></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<?php if ($categories): ?>
<section class="max-w-6xl mx-auto px-4 mt-10" aria-label="Topics">
  <div class="catchips"><?php foreach ($categories as $c): ?><a href="<?= e(url('/courses?category=' . $c['slug'])) ?>"><?= e($c['name']) ?></a><?php endforeach; ?></div>
</section>
<?php endif; ?>

<section class="max-w-6xl mx-auto px-4 mt-12" aria-labelledby="pop-h">
  <div class="flex items-end justify-between gap-3"><div><h2 id="pop-h" class="text-3xl font-extrabold">Learn at your own pace</h2>
    <p class="text-slate-600">Popular on-demand courses<?= $courseTotal ? ' &middot; ' . (int) $courseTotal . ' to choose from' : '' ?></p></div>
    <a class="font-bold text-bolt-dark underline shrink-0" href="<?= e(url('/courses?mode=self_paced')) ?>">Browse all</a></div>
  <?php if ($courses): ?><div class="mt-6 grid sm:grid-cols-2 lg:grid-cols-4 gap-5"><?php foreach ($courses as $course): ?><?= View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?></div>
  <?php else: ?><p class="mt-6 bg-white border rounded-lg p-8 text-center text-slate-600">New self-paced courses are on the way. <a class="underline text-bolt-dark" href="<?= e(url('/contact')) ?>">Tell us what you want to learn.</a></p><?php endif; ?>
</section>

<section class="max-w-6xl mx-auto px-4 mt-14" aria-labelledby="how-h">
  <h2 id="how-h" class="text-3xl font-extrabold">How it works</h2>
  <ol class="mt-6 grid md:grid-cols-4 gap-4">
    <?php foreach ([['Pick a course', 'Search by topic, level or price. Many have free preview lessons.'], ['Learn when you can', 'Watch, read and take quizzes at your own speed. Progress saves itself.'], ['Earn your certificate', 'Complete every lesson and download a certificate with a verification link.'], ['Find your next job', 'Get job matches that use your interests and the courses you take.']] as $i => [$t, $d]): ?>
      <li class="border border-slate-200 rounded-lg p-5 bg-white"><span class="font-display font-extrabold text-bolt text-3xl"><?= $i + 1 ?></span><p class="mt-1 font-bold"><?= e($t) ?></p><p class="text-sm text-slate-600 mt-1"><?= e($d) ?></p></li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="max-w-6xl mx-auto px-4 mt-14" aria-labelledby="jobs-h">
  <div class="flex items-end justify-between gap-3"><div><h2 id="jobs-h" class="text-3xl font-extrabold">Latest jobs in Nigeria</h2>
    <p class="text-slate-600">Collected from public sources, summarised by AI and checked for scams. Always apply on the employer's own page.</p></div>
    <a class="font-bold text-bolt-dark underline shrink-0" href="<?= e(url('/jobs')) ?>">See all jobs</a></div>
  <?php if ($jobs): ?><div class="mt-6 grid lg:grid-cols-2 gap-3"><?php foreach ($jobs as $job): ?><?= View::partial('partials/job-card', ['job' => $job, 'saved' => false]) ?><?php endforeach; ?></div>
  <?php else: ?><p class="mt-6 bg-white border rounded-lg p-8 text-center text-slate-600">Jobs will appear here as soon as the first sources are connected.</p><?php endif; ?>
</section>

<section class="max-w-6xl mx-auto px-4 mt-14" aria-labelledby="live-h">
  <div class="rounded-lg bg-bolt-tint p-6 md:p-8">
    <div class="md:flex items-end justify-between gap-4"><div><h2 id="live-h" class="text-2xl font-extrabold">Prefer to learn with others?</h2>
      <p class="mt-1 text-slate-700 max-w-xl">Join a live cohort with an instructor and a group. Cohort students also get the on-demand lessons for the course.</p></div>
      <a class="btn btn-primary mt-4 md:mt-0" href="<?= e(url('/courses?mode=live')) ?>">See live cohorts</a></div>
    <?php if ($cohorts): ?><div class="mt-5 grid md:grid-cols-3 gap-4"><?php foreach ($cohorts as $c): ?><?= View::partial('partials/cohort-card', ['c' => $c]) ?><?php endforeach; ?></div><?php endif; ?>
  </div>
</section>

<section class="max-w-6xl mx-auto px-4 mt-10">
  <div class="rounded-lg border border-slate-200 bg-white p-6 md:flex items-center justify-between gap-6">
    <div><h2 class="text-xl font-extrabold">Need help with your CV or interviews?</h2><p class="text-slate-600">The Career Accelerator offers CV revamps, interview coaching and job-search support.</p></div>
    <a class="btn btn-ghost mt-3 md:mt-0" href="<?= e(url('/career-accelerator')) ?>">Explore career support</a>
  </div>
  <?php if (!Auth::check('student')): ?><p class="mt-6 text-center"><a class="btn btn-accent !py-3 !px-8" href="<?= e(url('/register')) ?>">Create your free account</a></p><?php endif; ?>
</section>
