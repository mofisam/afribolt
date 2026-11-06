<?php /** @var float $rating @var int|null $count */ $r = (float) ($rating ?? 0); ?>
<span class="inline-flex items-center gap-1 text-sm" <?= $r > 0 ? 'aria-label="Rated ' . e(number_format($r, 1)) . ' out of 5"' : '' ?>>
  <?php if ($r > 0): ?>
    <strong class="text-amber-700"><?= e(number_format($r, 1)) ?></strong>
    <span class="stars" style="--p:<?= round($r / 5 * 100) ?>%" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
    <?php if (isset($count)): ?><span class="text-slate-500">(<?= number_format((int) $count) ?>)</span><?php endif; ?>
  <?php else: ?><span class="text-slate-500">New</span><?php endif; ?>
</span>
