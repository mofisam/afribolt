<?php /** @var array $course */ ?>
<a href="<?= e(url('/courses/' . $course['slug'])) ?>" class="block border border-slate-300 rounded-lg overflow-hidden bg-white hover:border-bolt">
  <?php if (!empty($course['thumbnail_url'])): ?>
    <img src="<?= e(asset($course['thumbnail_url'])) ?>" alt="" class="w-full h-40 object-cover" loading="lazy">
  <?php else: ?>
    <div class="h-40 bg-bolt-tint flex items-center justify-center"><span class="font-display font-extrabold text-bolt text-4xl"><?= e(strtoupper(substr($course['title'], 0, 2))) ?></span></div>
  <?php endif; ?>
  <div class="p-5">
    <?php if (!empty($course['category_name'])): ?><span class="badge"><?= e($course['category_name']) ?></span><?php endif; ?>
    <h3 class="mt-2 text-xl font-bold"><?= e($course['title']) ?></h3>
    <p class="mt-1 text-slate-600"><?= e($course['short_description']) ?></p>
  </div>
</a>
