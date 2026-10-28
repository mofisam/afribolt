<?php
/**
 * @var string $title
 * @var array $columns  [['label'=>, 'render'=>fn($row)=>string, 'html'=>bool], ...]
 * @var array $rows
 * @var array|null $pager
 * @var array $filters  [['name','label','type'=>text|select,'options'=>[]], ...]
 * @var callable|null $actions fn($row) => [['label','url','post'=>bool,'confirm'=>string], ...]
 */
$filters = $filters ?? []; $actions = $actions ?? null;
?>
<div class="flex flex-wrap items-center justify-between gap-3">
  <h1 class="text-3xl font-extrabold"><?= e($title) ?></h1>
  <?php if (!empty($createUrl)): ?><a class="btn btn-primary" href="<?= e($createUrl) ?>"><?= e($createLabel ?? 'Add new') ?></a><?php endif; ?>
</div>
<?php if (!empty($intro)): ?><div class="mt-3 text-slate-700 max-w-3xl"><?= $intro /* trusted HTML from controller */ ?></div><?php endif; ?>

<?php if ($filters): ?>
<form method="get" class="mt-5 bg-white border rounded-lg p-4 flex flex-wrap gap-3 items-end">
  <?php foreach ($filters as $f): $cur = (string) ($_GET[$f['name']] ?? ''); ?>
    <div><label class="label text-sm" for="flt_<?= e($f['name']) ?>"><?= e($f['label']) ?></label>
    <?php if (($f['type'] ?? 'text') === 'select'): ?>
      <select class="field" id="flt_<?= e($f['name']) ?>" name="<?= e($f['name']) ?>"><option value="">All</option>
        <?php foreach ($f['options'] as $v => $l): ?><option value="<?= e($v) ?>" <?= $cur === (string) $v ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
    <?php else: ?>
      <input class="field" id="flt_<?= e($f['name']) ?>" name="<?= e($f['name']) ?>" value="<?= e($cur) ?>" type="<?= e($f['type'] ?? 'text') ?>">
    <?php endif; ?></div>
  <?php endforeach; ?>
  <button class="btn btn-primary" type="submit">Filter</button>
  <a class="btn btn-ghost" href="?">Reset</a>
</form>
<?php endif; ?>

<div class="mt-5 bg-white border rounded-lg overflow-x-auto">
  <table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr>
      <?php foreach ($columns as $c): ?><th class="p-3 font-bold"><?= e($c['label']) ?></th><?php endforeach; ?>
      <?php if ($actions): ?><th class="p-3"><span class="sr-only">Actions</span></th><?php endif; ?>
    </tr></thead>
    <tbody>
    <?php foreach ($rows as $row): ?>
      <tr class="border-t align-top">
        <?php foreach ($columns as $c): $out = $c['render']($row); ?>
          <td class="p-3"><?= !empty($c['html']) ? $out : e($out) ?></td>
        <?php endforeach; ?>
        <?php if ($actions): ?><td class="p-3 whitespace-nowrap text-right">
          <?php foreach ($actions($row) as $a): ?>
            <?php if (!empty($a['post'])): ?>
              <form method="post" action="<?= e($a['url']) ?>" class="inline" <?= !empty($a['confirm']) ? 'onsubmit="return confirm(' . e(json_encode($a['confirm'])) . ')"' : '' ?>>
                <?= csrf_field() ?><?php foreach (($a['fields'] ?? []) as $fk => $fv): ?><input type="hidden" name="<?= e($fk) ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                <button class="underline text-bolt-dark mr-3" type="submit"><?= e($a['label']) ?></button></form>
            <?php else: ?><a class="underline text-bolt-dark mr-3" href="<?= e($a['url']) ?>"><?= e($a['label']) ?></a><?php endif; ?>
          <?php endforeach; ?></td><?php endif; ?>
      </tr>
    <?php endforeach; if (!$rows): ?>
      <tr><td class="p-6 text-slate-500" colspan="<?= count($columns) + ($actions ? 1 : 0) ?>">Nothing here yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?= App\Core\View::partial('admin/partials/pager', ['pager' => $pager ?? null]) ?>
