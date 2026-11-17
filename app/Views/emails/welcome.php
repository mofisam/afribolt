<!--preheader:Your Afribolt account is ready. Here is how to get started.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Welcome to Afribolt, <?= e($name) ?></h1>
<p style="margin:0 0 16px">Your account is ready. Here are three good ways to get started:</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px">
  <tr><td width="36" valign="top" style="padding:6px 0;font-size:18px;font-weight:bold;color:#F26B21">1</td><td style="padding:6px 0"><strong>Pick a course.</strong> Learn at your own pace and earn a certificate.</td></tr>
  <tr><td width="36" valign="top" style="padding:6px 0;font-size:18px;font-weight:bold;color:#F26B21">2</td><td style="padding:6px 0"><strong>Complete your profile.</strong> It helps us match you to the right jobs.</td></tr>
  <tr><td width="36" valign="top" style="padding:6px 0;font-size:18px;font-weight:bold;color:#F26B21">3</td><td style="padding:6px 0"><strong>Explore jobs.</strong> Browse openings checked by our team.</td></tr>
</table>
<?= App\Core\View::partial('emails/_button', ['url' => url('/courses'), 'label' => 'Browse courses']) ?>
<p style="margin:0;font-size:14px;color:#475569">Want to go further? <a href="<?= e(url('/career-accelerator')) ?>" style="color:#0F6BA0">Career Launch</a> takes you from learning to a job-ready CV.</p>
