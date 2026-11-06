<p>Hi <?= e($name) ?>,</p>
<p>Here are new jobs that match your interests and learning:</p>
<?php foreach ($jobs as $j): ?>
  <div style="border:1px solid #e2e8f0;border-radius:6px;padding:12px;margin:10px 0">
    <p style="margin:0;font-weight:bold"><a href="<?= e(url('/jobs/' . $j['slug'])) ?>" style="color:#0F6BA0"><?= e($j['title']) ?></a></p>
    <p style="margin:2px 0;color:#475569"><?= e(trim(($j['company'] ?? '') . ($j['state'] ? ' · ' . $j['state'] : ''))) ?></p>
    <?php if (!empty($j['reason'])): ?><p style="margin:6px 0 0;font-size:14px"><?= e($j['reason']) ?></p><?php endif; ?>
  </div>
<?php endforeach; ?>
<p><a href="<?= e(url('/student/jobs')) ?>" style="background:#1B8CCB;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;font-weight:bold">See all jobs for you</a></p>
<p style="font-size:12px;color:#64748b">Never pay money to get a job. To stop these emails, <a href="<?= e(url('/student/jobs/preferences')) ?>">change your job preferences</a>.</p>
