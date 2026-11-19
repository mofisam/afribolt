<!--preheader:<?= $decision === 'approved' ? 'Your company is verified. You can post jobs now.' : 'An update on your company verification.' ?>-->
<?php if ($decision === 'approved'): ?>
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Your company is verified</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, good news: <strong><?= e($company) ?></strong> has been verified on Afribolt. You can now post jobs whenever you have a vacancy, and registered students can apply.</p>
<?= App\Core\View::partial('emails/_button', ['url' => url('/employer/jobs/create'), 'label' => 'Post your first job']) ?>
<?php else: ?>
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540"><?= $decision === 'changes' ? 'We need a little more from you' : 'Verification was not approved' ?></h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, we reviewed <strong><?= e($company) ?></strong> and <?= $decision === 'changes' ? 'need a few changes before we can verify it.' : 'could not approve it this time.' ?></p>
<?php if (!empty($note)): ?><?= App\Core\View::partial('emails/_details', ['rows' => ['Note from our team' => $note]]) ?><?php endif; ?>
<?= App\Core\View::partial('emails/_button', ['url' => url('/employer/verification'), 'label' => 'Update and resubmit']) ?>
<?php endif; ?>
