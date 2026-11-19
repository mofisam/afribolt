<?php /** @var array $apps */
$label = ['submitted' => 'Sent', 'viewed' => 'Viewed', 'shortlisted' => 'Shortlisted', 'interview' => 'Interview', 'rejected' => 'Not selected', 'hired' => 'Hired'];
$tone = ['shortlisted' => 'badge-open', 'interview' => 'badge-open', 'hired' => 'badge-open', 'rejected' => 'badge-closed'];
?>
<div class="max-w-3xl mx-auto">
  <h1 class="text-3xl font-extrabold">My applications</h1>
  <p class="text-slate-600">Jobs you applied for on Afribolt, and what the employer has done with them.</p>
  <?php if (!$apps): ?>
    <div class="mt-6 bg-white border border-dashed rounded-lg p-8 text-center"><p class="font-bold text-lg">No applications yet</p><p class="text-slate-600">Find a job and press Apply.</p><a class="btn btn-primary mt-3" href="<?= e(url('/jobs')) ?>">Browse jobs</a></div>
  <?php else: ?>
    <ul class="mt-6 space-y-3">
      <?php foreach ($apps as $a): ?>
        <li class="bg-white border rounded-lg p-4">
          <div class="flex flex-wrap items-start justify-between gap-2">
            <div><p class="font-bold text-lg"><a class="hover:underline" href="<?= e(url('/jobs/' . $a['slug'])) ?>"><?= e($a['title']) ?></a></p>
              <p class="text-sm text-slate-600"><?= e($a['company']) ?><?= $a['location_text'] ? ' &middot; ' . e($a['location_text']) : '' ?> &middot; applied <?= e(local_date($a['created_at'], null, 'j M Y')) ?></p></div>
            <span class="badge <?= $tone[$a['status']] ?? '' ?>"><?= e($label[$a['status']]) ?></span>
          </div>
          <?php if (!empty($a['employer_note'])): ?><p class="mt-2 text-sm rounded bg-bolt-tint p-2"><strong>Message from the employer:</strong> <?= e($a['employer_note']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
