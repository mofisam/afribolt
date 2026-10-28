<section class="max-w-4xl mx-auto px-4 py-12">
  <?php if (!empty($course['category_name'])): ?><span class="badge"><?= e($course['category_name']) ?></span><?php endif; ?>
  <h1 class="mt-2 text-4xl font-extrabold"><?= e($course['title']) ?></h1>
  <p class="mt-3 text-lg text-slate-700"><?= e($course['short_description']) ?></p>
  <p class="mt-2 text-sm text-slate-500">Level: <?= e(str_replace('_', ' ', $course['course_level'])) ?><?= $course['default_duration_weeks'] ? ' &middot; ' . (int) $course['default_duration_weeks'] . ' weeks' : '' ?></p>
  <?php if ($course['description']): ?><div class="mt-8 whitespace-pre-line"><?= e($course['description']) ?></div><?php endif; ?>
  <?php if ($course['learning_objectives']): ?><h2 class="mt-8 text-2xl font-bold">What you'll learn</h2><div class="mt-2 whitespace-pre-line"><?= e($course['learning_objectives']) ?></div><?php endif; ?>
  <?php if ($course['prerequisites']): ?><h2 class="mt-8 text-2xl font-bold">Requirements</h2><div class="mt-2 whitespace-pre-line"><?= e($course['prerequisites']) ?></div><?php endif; ?>

  <h2 class="mt-12 text-2xl font-bold">Available cohorts</h2>
  <?php if (!$cohorts): ?>
    <p class="mt-3 text-slate-600">No cohort is open right now. <a class="underline text-bolt-dark" href="<?= e(url('/contact?type=course')) ?>">Ask to be notified</a> when the next one is scheduled.</p>
  <?php else: ?>
    <div class="mt-4 grid sm:grid-cols-2 gap-5"><?php foreach ($cohorts as $c): ?><?= App\Core\View::partial('partials/cohort-card', ['c' => $c]) ?><?php endforeach; ?></div>
  <?php endif; ?>
</section>
