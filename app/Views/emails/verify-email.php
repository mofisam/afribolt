<!--preheader:Confirm your email address to finish setting up your Afribolt account.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Confirm your email address</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, thanks for joining Afribolt. Please confirm your email to finish setting up your account.</p>
<?= App\Core\View::partial('emails/_button', ['url' => $link, 'label' => 'Verify my email']) ?>
<?= App\Core\View::partial('emails/_fallback', ['url' => $link]) ?>
<p style="margin:0;font-size:14px;color:#475569">This link works for 24 hours. If you did not create an account, you can safely ignore this email.</p>
