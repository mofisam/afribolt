<?php
/** @var string $target UTC ISO  @var string $label  @var bool $dark  @var bool $reload */
$dark = $dark ?? false;
?>
<div <?= $dark ? 'class="text-white"' : '' ?>>
  <p class="font-semibold mb-2"><?= e($label ?? 'Starts in') ?></p>
  <div class="cd" data-countdown="<?= e($target) ?>" data-server-now="<?= e(utc_iso(utc_now())) ?>" <?= !empty($reload) ? 'data-reload-on-zero' : '' ?> role="timer">
    <div class="cd-unit"><div class="cd-num" data-unit="days">--</div><div class="cd-lab">days</div></div>
    <div class="cd-unit"><div class="cd-num" data-unit="hours">--</div><div class="cd-lab">hours</div></div>
    <div class="cd-unit"><div class="cd-num" data-unit="minutes">--</div><div class="cd-lab">minutes</div></div>
    <div class="cd-unit"><div class="cd-num" data-unit="seconds">--</div><div class="cd-lab">seconds</div></div>
  </div>
</div>
