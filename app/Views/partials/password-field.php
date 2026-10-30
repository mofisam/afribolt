<?php
/** @var string $id  @var string $label  @var string $autocomplete  @var bool $caps  @var string $extra (extra attrs) */
?>
<div class="pw-wrap">
  <input class="field" type="password" id="<?= e($id) ?>" name="<?= e($name ?? $id) ?>" autocomplete="<?= e($autocomplete ?? 'current-password') ?>" required <?= $extra ?? '' ?>>
  <button type="button" class="pw-toggle" data-toggle-password="<?= e($id) ?>" aria-pressed="false" aria-label="Show password">Show</button>
</div>
<?php if (!empty($caps)): ?><p class="hint bad" data-caps-for="<?= e($id) ?>" hidden role="status">Caps Lock is on</p><?php endif; ?>
