<?php
use App\Core\{Auth, View};
$L = job_labels();
$sal = job_salary($job);
$when = $job['posted_at'] ?: $job['published_at'];
$palette = ['#DDEFFB', '#FFE9C7', '#E3F6E8', '#F3E4FB', '#FDE4E4', '#E0F2F1'];
$bg = $palette[crc32((string) ($job['company'] ?: $job['title'])) % count($palette)];
$risky = (int) $job['scam_risk'] >= 40 || $flags;
$daysLeft = $job['deadline_at'] ? (int) ceil((strtotime($job['deadline_at'] . ' UTC') - time()) / 86400) : null;
?>
<div class="max-w-6xl mx-auto px-4 py-8">
  <nav class="text-sm" aria-label="Breadcrumb"><a class="underline text-bolt-dark" href="<?= e(url('/jobs')) ?>">Jobs</a>
    <?php if ($job['category_name']): ?> &rsaquo; <a class="underline text-bolt-dark" href="<?= e(url('/jobs?category=' . $job['category_slug'])) ?>"><?= e($job['category_name']) ?></a><?php endif; ?></nav>

  <header class="mt-4 bg-white border rounded-lg p-5 md:p-7 flex flex-col md:flex-row gap-5 md:items-center justify-between">
    <div class="flex gap-4 min-w-0">
      <div class="jlogo !w-16 !h-16 !text-2xl" style="background:<?= $bg ?>" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) ($job['company'] ?: $job['title']), 0, 1))) ?></div>
      <div class="min-w-0"><h1 class="text-2xl md:text-3xl font-extrabold leading-tight"><?= e($job['title']) ?></h1>
        <p class="text-lg text-slate-700"><?= e((string) $job['company']) ?></p>
        <p class="text-slate-600"><?= e($job['state'] ? $job['state'] . ($job['city'] ? ' (' . $job['city'] . ')' : '') : (string) $job['location_text']) ?><?= $job['work_mode'] === 'remote' ? ' &middot; Remote' : '' ?></p>
        <div class="jchips"><?php foreach ([$L['type'][$job['employment_type']], $L['mode'][$job['work_mode']], $L['level'][$job['experience_level']]] as $c): if ($c): ?><span class="jchip"><?= e($c) ?></span><?php endif; endforeach; ?>
          <?php if ($sal): ?><span class="jchip pay"><?= e($sal) ?></span><?php endif; ?></div></div>
    </div>
    <div class="flex flex-wrap gap-2 shrink-0">
      <a class="btn btn-accent !py-3 !px-6" href="<?= e(url('/jobs/' . $job['slug'] . '/apply')) ?>" target="_blank" rel="nofollow noopener noreferrer">Apply on <?= e($job['source_name'] ?: 'the employer\'s site') ?> &#8599;</a>
      <?php if (Auth::check('student')): ?>
        <form method="post" action="<?= e(url('/student/jobs/' . $job['id'] . '/save')) ?>"><?= csrf_field() ?><button class="btn btn-ghost !py-3" type="submit"><?= $isSaved ? '&#9829; Saved' : '&#9825; Save' ?></button></form>
      <?php else: ?><a class="btn btn-ghost !py-3" href="<?= e(url('/login')) ?>">&#9825; Save</a><?php endif; ?>
    </div>
  </header>

  <div class="mt-6 grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-8">
    <div class="space-y-8">
      <?php if ($risky): ?>
        <div class="safety" role="alert"><p class="font-bold">&#9888; Be extra careful with this posting</p>
          <p class="text-sm mt-1">Our checks flagged it<?= $flags ? ': ' . e(implode('; ', $flags)) : '' ?>. Real employers do not ask you to pay. Do not share bank, BVN or card details.</p></div>
      <?php endif; ?>

      <section aria-labelledby="sum-h"><h2 id="sum-h" class="text-2xl font-extrabold">About this job</h2>
        <p class="mt-3 text-lg leading-relaxed"><?= e($job['summary'] ?: 'See the full posting on the employer\'s page for details.') ?></p>
        <?php if ($job['ai_status'] === 'done'): ?><p class="mt-2 text-xs text-slate-500">Summary written by AI from the original posting. Check the full details on the employer's page before you apply.</p><?php endif; ?>
        <a class="btn btn-ghost mt-4" href="<?= e(url('/jobs/' . $job['slug'] . '/apply')) ?>" target="_blank" rel="nofollow noopener noreferrer">Read the full posting &#8599;</a></section>

      <?php if ($skills): ?><section><h2 class="text-xl font-extrabold">Skills mentioned</h2><div class="jchips"><?php foreach ($skills as $s): ?><span class="jchip"><?= e($s) ?></span><?php endforeach; ?></div></section><?php endif; ?>

      <?php if ($courses): ?>
      <section aria-labelledby="learn-h" class="rounded-lg bg-bolt-tint p-5"><h2 id="learn-h" class="text-xl font-extrabold">Build the skills for this job</h2>
        <p class="text-slate-700">Learn at your own pace with these courses, then apply with confidence.</p>
        <div class="mt-4 grid sm:grid-cols-2 gap-4"><?php foreach ($courses as $course): ?><?= View::partial('partials/course-card', ['course' => $course]) ?><?php endforeach; ?></div></section>
      <?php endif; ?>

      <section class="safety" aria-labelledby="safe-h"><h2 id="safe-h" class="font-bold">Apply safely</h2>
        <ul class="mt-1 text-sm list-disc pl-5 space-y-1"><li>You should never pay a fee to apply, interview or &ldquo;secure&rdquo; a job.</li><li>Do not share your BVN, card, OTP or bank login with an employer.</li><li>Be careful with WhatsApp-only offers and pay that sounds too good to be true.</li><li>AfriBolt does not charge for job applications and is not the employer.</li></ul></section>

      <section aria-labelledby="rep-h"><h2 id="rep-h" class="font-bold">Something wrong with this job?</h2>
        <?php if (Auth::check('student')): ?>
          <details class="mt-2"><summary class="cursor-pointer underline text-bolt-dark">Report this job</summary>
            <form method="post" action="<?= e(url('/student/jobs/' . $job['id'] . '/report')) ?>" class="mt-3 space-y-3 max-w-md"><?= csrf_field() ?>
              <div><label class="label" for="reason">What is the problem?</label><select class="field" id="reason" name="reason"><option value="scam">Looks like a scam</option><option value="expired">Already closed</option><option value="duplicate">Duplicate</option><option value="inappropriate">Inappropriate</option><option value="other">Something else</option></select></div>
              <div><label class="label" for="details">Details (optional)</label><input class="field" id="details" name="details" maxlength="500"></div>
              <button class="btn btn-ghost" type="submit">Send report</button></form></details>
        <?php else: ?><p class="mt-1 text-sm"><a class="underline text-bolt-dark" href="<?= e(url('/login')) ?>">Log in</a> to report a job.</p><?php endif; ?></section>
    </div>

    <aside class="lg:sticky lg:top-24 self-start space-y-4">
      <div class="bg-white border rounded-lg p-5"><h2 class="font-bold text-lg">Quick facts</h2>
        <dl class="mt-3 space-y-2 text-sm">
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Posted</dt><dd class="font-semibold text-right"><?= e(time_ago($when)) ?></dd></div>
          <?php if ($job['deadline_at']): ?><div class="flex justify-between gap-3"><dt class="text-slate-500">Closes</dt><dd class="font-semibold text-right"><?= e(local_date($job['deadline_at'], null, 'j M Y')) ?><?= $daysLeft !== null && $daysLeft >= 0 ? ' (' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's') . ' left)' : '' ?></dd></div><?php endif; ?>
          <?php if ($sal): ?><div class="flex justify-between gap-3"><dt class="text-slate-500">Pay</dt><dd class="font-semibold text-right"><?= e($sal) ?></dd></div><?php endif; ?>
          <?php if ($L['level'][$job['experience_level']]): ?><div class="flex justify-between gap-3"><dt class="text-slate-500">Level</dt><dd class="font-semibold text-right"><?= e($L['level'][$job['experience_level']]) ?></dd></div><?php endif; ?>
          <?php if ($L['type'][$job['employment_type']]): ?><div class="flex justify-between gap-3"><dt class="text-slate-500">Type</dt><dd class="font-semibold text-right"><?= e($L['type'][$job['employment_type']]) ?></dd></div><?php endif; ?>
          <div class="flex justify-between gap-3"><dt class="text-slate-500">Source</dt><dd class="font-semibold text-right"><?= e($job['source_name'] ?: 'AfriBolt') ?></dd></div></dl>
        <a class="btn btn-accent w-full mt-4" href="<?= e(url('/jobs/' . $job['slug'] . '/apply')) ?>" target="_blank" rel="nofollow noopener noreferrer">Apply now &#8599;</a></div>
      <?php if ($similar): ?><div><h2 class="font-bold text-lg mb-2">Similar jobs</h2><div class="space-y-3"><?php foreach ($similar as $s): ?>
        <a class="block bg-white border rounded-lg p-3 hover:border-bolt" href="<?= e(url('/jobs/' . $s['slug'])) ?>"><p class="font-bold leading-snug"><?= e($s['title']) ?></p><p class="text-sm text-slate-600"><?= e((string) $s['company']) ?><?= $s['state'] ? ' &middot; ' . e($s['state']) : '' ?></p></a><?php endforeach; ?></div></div><?php endif; ?>
    </aside>
  </div>
</div>
