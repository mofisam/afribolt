<?php if (($pager['pages'] ?? 1) > 1):
  $link = fn(int $p) => '?' . http_build_query(array_merge($_GET, ['page' => $p])); ?>
<nav class="mt-4 flex items-center justify-between text-sm" aria-label="Pagination">
  <span><?= (int) $pager['total'] ?> result<?= $pager['total'] == 1 ? '' : 's' ?></span>
  <span class="flex gap-2 items-center">
    <?php if ($pager['page'] > 1): ?><a class="btn btn-ghost !py-1" href="<?= e($link($pager['page'] - 1)) ?>">Previous</a><?php endif; ?>
    <span>Page <?= (int) $pager['page'] ?> of <?= (int) $pager['pages'] ?></span>
    <?php if ($pager['page'] < $pager['pages']): ?><a class="btn btn-ghost !py-1" href="<?= e($link($pager['page'] + 1)) ?>">Next</a><?php endif; ?>
  </span>
</nav>
<?php endif; ?>
