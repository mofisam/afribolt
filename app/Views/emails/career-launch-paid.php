<!--preheader:Payment received. Stage 2 of Career Launch is now unlocked.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Welcome to Career Launch!</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, thank you. Your payment was received and your place is confirmed. <strong>Stage 2, Enrol in the course, is now unlocked.</strong></p>
<?= App\Core\View::partial('emails/_details', ['rows' => [
    'Amount paid' => \App\Services\CareerLaunchPricing::naira((float) $amount),
    'Payment reference' => $reference,
]]) ?>
<?= App\Core\View::partial('emails/_button', ['url' => url('/student/dashboard'), 'label' => 'Start Stage 2']) ?>
<p style="margin:0;font-size:14px;color:#475569">Keep this email as your receipt. Refunds are available only before any stage work begins.</p>
