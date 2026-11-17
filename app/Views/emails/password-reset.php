<!--preheader:Use this link to choose a new password. It works for one hour.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Reset your password</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, we received a request to reset your Afribolt password. Press the button to choose a new one.</p>
<?= App\Core\View::partial('emails/_button', ['url' => $link, 'label' => 'Choose a new password']) ?>
<?= App\Core\View::partial('emails/_fallback', ['url' => $link]) ?>
<p style="margin:0;font-size:14px;color:#475569">This link works for one hour. If you did not ask for this, ignore this email. Your password will stay the same. Never share this link with anyone.</p>
