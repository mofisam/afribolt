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
// Optional 'icon' on an action turns it into a compact icon button (label stays as tooltip + screen-reader text).
$actIcons = [
  'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>',
  'curriculum' => '<path d="M8 6h13M8 12h13M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/>',
  'view' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
  'unpublish' => '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/>',
  'publish' => '<path d="M20 6L9 17l-5-5"/>',
  'duplicate' => '<rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>',
  'archive' => '<path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/>',
  'send' => '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
  'delete' => '<path d="M3 6h18"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>',
];
$actBtn = function (array $a) use ($actIcons): string {
    $label = e($a['label']);
    if (empty($a['icon']) || !isset($actIcons[$a['icon']])) { return $label; }
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $actIcons[$a['icon']] . '</svg>' . (!empty($a['show_label']) ? '<span>' . $label . '</span>' : '<span class="sr-only">' . $label . '</span>');
};
$actClass = fn(array $a) => !empty($a['icon']) && isset($actIcons[$a['icon']]) ? 'act' . (!empty($a['show_label']) ? ' act-label' : '') . (in_array($a['icon'], ['archive', 'delete', 'unpublish'], true) ? ' act-warn' : '') : 'underline text-bolt-dark mr-3';
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
        <?php if ($actions): ?><td class="p-3 whitespace-nowrap text-right"><div class="inline-flex items-center justify-end gap-1.5 flex-wrap">
          <?php foreach ($actions($row) as $a): ?>
            <?php if (!empty($a['post'])): ?>
              <form method="post" action="<?= e($a['url']) ?>" class="inline" <?= !empty($a['confirm']) ? 'onsubmit="return confirm(' . e(json_encode($a['confirm'])) . ')"' : '' ?>>
                <?= csrf_field() ?><?php foreach (($a['fields'] ?? []) as $fk => $fv): ?><input type="hidden" name="<?= e($fk) ?>" value="<?= e($fv) ?>"><?php endforeach; ?>
                <button class="<?= $actClass($a) ?>" type="submit" title="<?= e($a['label']) ?>"><?= $actBtn($a) ?></button></form>
            <?php else: ?><a class="<?= $actClass($a) ?>" href="<?= e($a['url']) ?>" title="<?= e($a['label']) ?>"><?= $actBtn($a) ?></a><?php endif; ?>
          <?php endforeach; ?></div></td><?php endif; ?>
      </tr>
    <?php endforeach; if (!$rows): ?>
      <tr><td class="p-6 text-slate-500" colspan="<?= count($columns) + ($actions ? 1 : 0) ?>">Nothing here yet.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</div>
<?= App\Core\View::partial('admin/partials/pager', ['pager' => $pager ?? null]) ?>
