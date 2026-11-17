<!--preheader:Your Career Launch payment link is ready. Pay to unlock Stage 2.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Your payment link is ready</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, you are one step away from starting Career Launch. Paying unlocks Stage 2 and the rest of the programme.</p>
<?= App\Core\View::partial('emails/_details', ['rows' => ['Founding price' => "\u{20A6}50,000 (one-time)"]]) ?>
<?= App\Core\View::partial('emails/_button', ['url' => $link, 'label' => 'Pay now', 'color' => '#C2410C']) ?>
<?= App\Core\View::partial('emails/_fallback', ['url' => $link]) ?>
<p style="margin:0;font-size:14px;color:#475569">You can also find this link any time on your <a href="<?= e(url('/student/dashboard')) ?>" style="color:#0F6BA0">dashboard</a>. Refunds are available only before any stage work begins. Only pay through links shown on your Afribolt dashboard or sent from this address.</p>
