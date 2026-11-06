<?php
/** @var array $job  @var bool|null $saved  @var array|null $rec (job row with score/reason/tip) */
$L = job_labels();
$palette = ['#DDEFFB', '#FFE9C7', '#E3F6E8', '#F3E4FB', '#FDE4E4', '#E0F2F1'];
$bg = $palette[crc32((string) ($job['company'] ?: $job['title'])) % count($palette)];
$sal = job_salary($job);
$loggedIn = App\Core\Auth::check('student');
$when = $job['posted_at'] ?: $job['published_at'];
?>
<article class="jcard">
  <div class="jlogo" style="background:<?= $bg ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($job['company'] ?: $job['title']), 0, 1))) ?></div>
  <div class="min-w-0 flex-1">
    <h3 class="jtitle"><a href="<?= e(url('/jobs/' . $job['slug'])) ?>"><?= e($job['title']) ?></a></h3>
    <p class="jmeta"><?= e((string) $job['company']) ?><?= $job['company'] && ($job['state'] || $job['location_text']) ? ' &middot; ' : '' ?><?= e($job['state'] ? $job['state'] . ($job['city'] ? ' (' . $job['city'] . ')' : '') : (string) $job['location_text']) ?></p>
    <?php if (!empty($job['summary'])): ?><p class="mt-1 text-sm text-slate-700 line-clamp-2"><?= e($job['summary']) ?></p><?php endif; ?>
    <div class="jchips">
      <?php if (!empty($job['category_name'])): ?><span class="jchip"><?= e($job['category_name']) ?></span><?php endif; ?>
      <?php foreach ([$L['type'][$job['employment_type']], $L['mode'][$job['work_mode']], $L['level'][$job['experience_level']]] as $c): if ($c): ?><span class="jchip"><?= e($c) ?></span><?php endif; endforeach; ?>
      <?php if ($sal): ?><span class="jchip pay"><?= e($sal) ?></span><?php endif; ?>
      <?php if ($job['deadline_at']): ?><span class="jchip warn">Closes <?= e(local_date($job['deadline_at'], null, 'j M')) ?></span><?php endif; ?>
    </div>
    <p class="mt-2 text-xs text-slate-500"><?= e(time_ago($when)) ?><?= $job['source_name'] ? ' &middot; via ' . e($job['source_name']) : '' ?></p>
    <?php if (!empty($rec['reason'])): ?>
      <div class="why"><span class="match"><?= (int) $rec['score'] ?>% match</span> &middot; <?= e($rec['reason']) ?><?= !empty($rec['tip']) ? '<br><span class="text-slate-600"><strong>Tip:</strong> ' . e($rec['tip']) . '</span>' : '' ?></div>
    <?php endif; ?>
  </div>
  <div class="jsave">
    <?php if ($loggedIn): ?>
      <form method="post" action="<?= e(url('/student/jobs/' . $job['id'] . '/save')) ?>"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '/jobs') ?>">
        <button type="submit" aria-pressed="<?= !empty($saved) ? 'true' : 'false' ?>" aria-label="<?= !empty($saved) ? 'Remove from saved jobs' : 'Save job' ?>" title="<?= !empty($saved) ? 'Saved' : 'Save' ?>"><?= !empty($saved) ? '&#9829;' : '&#9825;' ?></button></form>
    <?php else: ?><a class="btn btn-ghost !p-0 w-10 h-10 !rounded-md" href="<?= e(url('/login')) ?>" aria-label="Log in to save this job" title="Log in to save">&#9825;</a><?php endif; ?>
  </div>
</article>
