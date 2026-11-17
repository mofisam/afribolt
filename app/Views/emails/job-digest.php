<!--preheader:<?= count($jobs) ?> new job<?= count($jobs) === 1 ? '' : 's' ?> picked for you.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">New jobs picked for you</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, here are new jobs that match your interests and learning.</p>
<?php foreach ($jobs as $j): ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 14px;border:1px solid #e2e8f0;border-radius:8px"><tr><td style="padding:16px">
  <p style="margin:0 0 2px;font-size:17px;font-weight:bold"><a href="<?= e(url('/jobs/' . $j['slug'])) ?>" style="color:#0F6BA0;text-decoration:none"><?= e($j['title']) ?></a></p>
  <p style="margin:0 0 8px;font-size:14px;color:#475569"><?= e(trim(($j['company'] ?? '') . ($j['state'] ? ' · ' . $j['state'] : ''))) ?></p>
  <?php if (!empty($j['reason'])): ?><p style="margin:0 0 10px;font-size:14px;color:#334155"><?= e($j['reason']) ?></p><?php endif; ?>
  <a href="<?= e(url('/jobs/' . $j['slug'])) ?>" style="font-size:14px;font-weight:bold;color:#0F6BA0">View job &rarr;</a>
</td></tr></table>
<?php endforeach; ?>
<?= App\Core\View::partial('emails/_button', ['url' => url('/student/jobs'), 'label' => 'See all jobs for you']) ?>
<p style="margin:0 0 8px;font-size:14px;color:#475569"><strong>Stay safe:</strong> never pay money to get a job.</p>
<p style="margin:0;font-size:13px;color:#64748b">To stop these emails, <a href="<?= e(url('/student/jobs/preferences')) ?>" style="color:#0F6BA0">change your job preferences</a>.</p>
