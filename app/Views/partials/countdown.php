<?php /** Offer countdown. Var: $pricing (CareerLaunchPricing::current()). Prints nothing when there is no live timer. */
if (empty($pricing['active']) || empty($pricing['ends_iso'])) { return; } ?>
<p class="cd-box"><span>Founding offer ends in</span> <strong class="cd" data-countdown="<?= e($pricing['ends_iso']) ?>"><?= e($pricing['ends_label'] ?? '') ?></strong></p>
