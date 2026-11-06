<section class="bg-black"><div class="max-w-5xl mx-auto"><div class="pl-frame">
  <?php if ($embed): ?><?php if ($isFile): ?><video src="<?= e($embed) ?>" controls preload="metadata"></video><?php else: ?><iframe src="<?= e($embed) ?>" title="<?= e($lesson['title']) ?>" allow="encrypted-media; picture-in-picture; fullscreen" allowfullscreen></iframe><?php endif; ?>
  <?php endif; ?></div></div></section>
<div class="max-w-3xl mx-auto px-4 py-8">
  <p class="text-sm"><span class="tag">Free preview</span> &middot; <a class="underline text-bolt-dark" href="<?= e(url('/courses/' . $course['slug'])) ?>">&larr; Back to <?= e($course['title']) ?></a></p>
  <h1 class="mt-2 text-3xl font-extrabold"><?= e($lesson['title']) ?></h1>
  <?php if ($lesson['lesson_type'] === 'article' && $lesson['content']): ?><div class="mt-4 prose-lite"><?= render_content((string) $lesson['content']) ?></div><?php endif; ?>
  <div class="mt-8 rounded-lg bg-bolt-tint p-5 md:flex items-center justify-between gap-4"><p class="font-bold">Like what you see? Get the full course.</p>
    <a class="btn btn-accent mt-3 md:mt-0" href="<?= e(url('/courses/' . $course['slug'])) ?>">View course</a></div>
</div>
