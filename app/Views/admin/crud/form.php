<?php /** @var string $title @var string $action @var array $fields @var array $values @var string $cancelUrl */ ?>
<div class="max-w-3xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e($cancelUrl) ?>">&larr; Back</a>
  <h1 class="mt-1 text-3xl font-extrabold"><?= e($title) ?></h1>
  <?php if (!empty($intro)): ?><div class="mt-3 text-slate-700"><?= $intro /* trusted HTML from controller */ ?></div><?php endif; ?>
  <form method="post" action="<?= e($action) ?>" <?= !empty($multipart) ? 'enctype="multipart/form-data"' : '' ?> class="mt-6 bg-white border rounded-lg p-6 grid sm:grid-cols-2 gap-5" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($fields as $f):
      if (($f['type'] ?? '') === 'heading'): ?>
        <h2 class="sm:col-span-2 font-display font-bold text-xl border-t pt-4 first:border-0 first:pt-0"><?= e($f['label']) ?></h2>
      <?php else: echo App\Core\View::partial('admin/partials/field', ['f' => $f, 'values' => $values]); endif;
    endforeach; ?>
    <div class="sm:col-span-2 flex gap-3">
      <button class="btn btn-primary" type="submit"><?= e($submitLabel ?? 'Save') ?></button>
      <a class="btn btn-ghost" href="<?= e($cancelUrl) ?>">Cancel</a>
    </div>
  </form>
</div>
