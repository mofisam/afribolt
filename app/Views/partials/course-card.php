<?php
use App\Models\Course;
/** @var array $course */
$self = in_array($course['delivery_mode'], ['self_paced', 'both'], true);
$price = Course::priceNow($course);
$sale = Course::onPromo($course);
?>
<a href="<?= e(url('/courses/' . $course['slug'])) ?>" class="ccard">
  <div class="ccard-img">
    <?php if (!empty($course['thumbnail_url'])): ?><img src="<?= e(asset($course['thumbnail_url'])) ?>" alt="" loading="lazy">
    <?php else: ?><span class="ph-letters"><?= e(strtoupper(substr($course['title'], 0, 2))) ?></span><?php endif; ?>
  </div>
  <div class="ccard-body">
    <h3 class="ccard-title"><?= e($course['title']) ?></h3>
    <?php if (!empty($course['instructor_name'])): ?><p class="ccard-meta"><?= e($course['instructor_name']) ?></p><?php endif; ?>
    <?php if ($self): ?><?= App\Core\View::partial('partials/stars', ['rating' => $course['rating_avg'], 'count' => $course['rating_count']]) ?>
      <?php if ((int) $course['total_seconds'] > 0): ?><p class="ccard-meta"><?= e(fmt_duration((int) $course['total_seconds'], true)) ?> &middot; <?= (int) $course['lessons_count'] ?> lessons &middot; <?= e(str_replace('_', ' ', ucfirst($course['course_level']))) ?></p><?php endif; ?>
    <?php else: ?><p class="ccard-meta"><?= e(str_replace('_', ' ', ucfirst($course['course_level']))) ?><?= $course['default_duration_weeks'] ? ' &middot; ' . (int) $course['default_duration_weeks'] . ' weeks' : '' ?></p><?php endif; ?>
    <div class="mt-auto pt-2 flex flex-wrap items-center gap-2">
      <?php if ($self): ?>
        <span class="price"><?= e($price > 0 ? money($price, $course['price_currency']) : 'Free') ?><?php if ($sale): ?><s><?= e(money($course['price'], $course['price_currency'])) ?></s><?php endif; ?></span>
        <?php if ($sale): ?><span class="tag tag-sale">Sale</span><?php elseif ($price <= 0): ?><span class="tag tag-free">Free</span><?php endif; ?>
      <?php endif; ?>
      <?php if (in_array($course['delivery_mode'], ['live', 'both'], true)): ?><span class="tag tag-live">Live cohorts</span><?php endif; ?>
      <?php if ($course['delivery_mode'] === 'live'): ?><span class="text-sm font-bold text-bolt-dark">See dates</span><?php endif; ?>
    </div>
  </div>
</a>
