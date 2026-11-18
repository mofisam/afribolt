<?php
/** Amount + countdown + Pay button. Vars: $pricing, $payOnline (bool), $registration. Shown while the student has not paid. */
use App\Services\CareerLaunchPricing;
?>
<div class="rounded-lg bg-bolt-tint p-4 text-ink">
  <p class="font-bold text-xl"><?= e(CareerLaunchPricing::naira((float) $pricing['price'])) ?> <span class="text-sm font-semibold text-slate-600">one-time</span></p>
  <?php if ($pricing['active']): ?>
    <p class="text-sm text-slate-700">Founding price<?= $pricing['seats_left'] > 0 ? ' &middot; ' . (int) $pricing['seats_left'] . ' places left' : '' ?></p>
    <?= \App\Core\View::partial('partials/countdown', ['pricing' => $pricing]) ?>
  <?php else: ?>
    <p class="text-sm text-slate-700">The founding offer has ended, so the regular price applies.</p>
  <?php endif; ?>

  <?php if ($payOnline): ?>
    <form method="post" action="<?= e(url('/student/career-launch/pay')) ?>" class="mt-3"><?= csrf_field() ?>
      <button class="btn btn-accent" type="submit">Pay <?= e(CareerLaunchPricing::naira((float) $pricing['price'])) ?> now</button>
    </form>
    <p class="mt-2 text-sm text-slate-600">Secure payment by Paystack. Your stages unlock automatically as soon as the payment is confirmed.</p>
  <?php elseif (!empty($registration['payment_link'])): ?>
    <a class="btn btn-accent mt-3" href="<?= e($registration['payment_link']) ?>" target="_blank" rel="noopener noreferrer">Pay now<span class="sr-only"> (opens in a new tab)</span></a>
    <p class="mt-2 text-sm text-slate-600">We confirm payments by hand, so your stages unlock once the team marks you paid.</p>
  <?php else: ?>
    <p class="mt-3 text-sm text-slate-700">Your payment link will appear here as soon as it is ready, and we will email and notify you.</p>
  <?php endif; ?>
</div>
