<section class="max-w-6xl mx-auto px-4 py-12">
  <h1 class="text-4xl font-extrabold">Courses</h1>
  <?php if ($categories): ?>
  <nav class="mt-6 flex flex-wrap gap-2" aria-label="Categories">
    <a class="badge <?= $activeCategory === '' ? 'badge-open' : '' ?>" href="<?= e(url('/courses')) ?>">All</a>
    <?php foreach ($categories as $cat): if ($cat['status'] !== 'active') continue; ?>
      <a class="badge <?= $activeCategory === $cat['slug'] ? 'badge-open' : '' ?>" href="<?= e(url('/courses?category=' . $cat['slug'])) ?>"><?= e($cat['name']) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>
  <?php if (!$courses): ?>
    <p class="mt-10 text-slate-600">No courses are published yet. Please check back soon.</p>
  <?php else: ?>
    <div class="mt-8 grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <?php foreach ($courses as $course): ?><?= App\Core\View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
