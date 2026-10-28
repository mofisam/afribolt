<?php
/**
 * One form field. $f keys: name, label, type (text|email|url|number|textarea|select|checkbox|datetime|password|file|date),
 * options (select), required, help, half (two-column), rows, step, min, default, disabled
 */
$name = $f['name'];
$type = $f['type'] ?? 'text';
$val = old($name, $values[$name] ?? ($f['default'] ?? ''));
$id = 'f_' . $name;
$req = !empty($f['required']) ? 'required' : '';
$dis = !empty($f['disabled']) ? 'disabled' : '';
?>
<div class="<?= !empty($f['half']) ? '' : 'sm:col-span-2' ?>">
<?php if ($type === 'checkbox'): ?>
  <label class="flex items-start gap-2" for="<?= $id ?>">
    <input type="hidden" name="<?= e($name) ?>" value="0">
    <input type="checkbox" id="<?= $id ?>" name="<?= e($name) ?>" value="1" class="mt-1" <?= (string) $val === '1' ? 'checked' : '' ?> <?= $dis ?>>
    <span><span class="font-bold"><?= e($f['label']) ?></span><?php if (!empty($f['help'])): ?><span class="block text-sm text-slate-600"><?= e($f['help']) ?></span><?php endif; ?></span>
  </label>
<?php else: ?>
  <label class="label" for="<?= $id ?>"><?= e($f['label']) ?><?= $req ? ' <span class="text-red-600" aria-hidden="true">*</span>' : '' ?></label>
  <?php if ($type === 'textarea'): ?>
    <textarea class="field" id="<?= $id ?>" name="<?= e($name) ?>" rows="<?= (int) ($f['rows'] ?? 4) ?>" <?= $req ?> <?= $dis ?>><?= e($val) ?></textarea>
  <?php elseif ($type === 'select'): ?>
    <select class="field" id="<?= $id ?>" name="<?= e($name) ?>" <?= $req ?> <?= $dis ?>>
      <?php foreach ($f['options'] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string) $val === (string) $ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?>
    </select>
  <?php elseif ($type === 'file'): ?>
    <input class="field" type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp">
  <?php else:
      $html = ['datetime' => 'datetime-local', 'password' => 'password'][$type] ?? $type; ?>
    <input class="field" type="<?= e($html) ?>" id="<?= $id ?>" name="<?= e($name) ?>" value="<?= $type === 'password' ? '' : e($val) ?>" <?= $req ?> <?= $dis ?>
      <?= isset($f['step']) ? 'step="' . e($f['step']) . '"' : '' ?> <?= isset($f['min']) ? 'min="' . e($f['min']) . '"' : '' ?> <?= $type === 'password' ? 'autocomplete="new-password"' : '' ?>>
  <?php endif; ?>
  <?php if (!empty($f['help'])): ?><p class="mt-1 text-sm text-slate-600"><?= e($f['help']) ?></p><?php endif; ?>
<?php endif; ?>
  <?= field_error($name) ?>
</div>
