<?php
/** Crops the whitespace out of logo.jpeg (wordmark sits at ~x165-1140, y445-760 of a 1280px square). $width in px. */
$w = $width ?? 150;
?>
<span class="inline-block overflow-hidden align-middle" style="width:<?= $w ?>px;height:<?= round($w * 320 / 975) ?>px">
  <img src="<?= e(asset('img/logo.jpeg')) ?>" alt="AfriBolt Technologies" style="max-width:none;width:<?= round($w * 1280 / 975) ?>px;margin-left:-<?= round($w * 165 / 975) ?>px;margin-top:-<?= round($w * 440 / 975) ?>px">
</span>
