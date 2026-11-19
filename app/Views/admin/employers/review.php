<?php /** @var array $e @var array $docs @var array $jobs @var array $labels @var array $vlabel */
$vs = $e['verification_status'];
$row = fn(string $k, ?string $v) => '<div class="flex justify-between gap-4 py-1.5 border-b last:border-0"><dt class="text-slate-500">' . e($k) . '</dt><dd class="font-semibold text-right">' . ($v !== null && $v !== '' ? e($v) : '<span class="text-slate-400">Not given</span>') . '</dd></div>';
?>
<div class="max-w-4xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/employers')) ?>">&larr; Employers</a>
  <div class="mt-1 flex flex-wrap items-center gap-3"><h1 class="text-3xl font-extrabold"><?= e($e['company_name']) ?></h1>
    <span class="badge <?= $vs === 'verified' ? 'badge-open' : ($vs === 'rejected' ? 'badge-closed' : '') ?>"><?= e($vlabel[$vs]) ?></span>
    <?php if ($e['account_status'] !== 'active'): ?><span class="badge badge-closed">Suspended</span><?php endif; ?></div>
  <p class="text-slate-600">Registered <?= e(local_date($e['created_at'], null, 'j M Y')) ?><?= $e['submitted_at'] ? ' &middot; submitted for review ' . e(local_date($e['submitted_at'], null, 'j M Y')) : '' ?></p>

  <div class="mt-5 grid md:grid-cols-2 gap-5">
    <section class="bg-white border rounded-lg p-4" aria-labelledby="co-h"><h2 id="co-h" class="font-bold text-lg mb-2">Company</h2>
      <dl class="text-sm"><?= $row('RC number', $e['rc_number']) ?><?= $row('Website', $e['website']) ?><?= $row('Industry', $e['industry']) ?><?= $row('Size', $e['company_size']) ?><?= $row('Address', $e['address']) ?><?= $row('State', $e['state']) ?></dl>
      <?php if (!empty($e['description'])): ?><p class="mt-3 text-sm whitespace-pre-line"><?= e($e['description']) ?></p><?php endif; ?>
      <?php if (!empty($e['website'])): ?><p class="mt-2 text-sm"><a class="underline text-bolt-dark" href="<?= e($e['website']) ?>" target="_blank" rel="noopener nofollow">Open website</a></p><?php endif; ?></section>
    <section class="bg-white border rounded-lg p-4" aria-labelledby="ct-h"><h2 id="ct-h" class="font-bold text-lg mb-2">Contact person</h2>
      <dl class="text-sm"><?= $row('Name', $e['contact_first_name'] . ' ' . $e['contact_last_name']) ?><?= $row('Email', $e['email']) ?><?= $row('Phone', $e['phone']) ?></dl></section>
  </div>

  <section class="mt-5 bg-white border rounded-lg p-4" aria-labelledby="dc-h"><h2 id="dc-h" class="font-bold text-lg">Documents</h2>
    <?php if (!$docs): ?><p class="text-slate-600 mt-2">Nothing uploaded.</p><?php else: ?>
      <ul class="mt-2 divide-y">
        <?php foreach ($docs as $d): ?>
          <li class="py-2 flex flex-wrap items-center justify-between gap-2 text-sm"><span><strong><?= e(explode(' (', $labels[$d['doc_type']])[0]) ?></strong><br><span class="text-slate-600"><?= e($d['original_name']) ?> &middot; <?= e(number_format($d['size_bytes'] / 1024)) ?> KB &middot; <?= e(local_date($d['created_at'], null, 'j M Y')) ?></span></span>
            <a class="btn btn-ghost !py-1.5" href="<?= e(url('/admin/employers/documents/' . (int) $d['id'])) ?>" target="_blank" rel="noopener">Open</a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p class="mt-2 text-xs text-slate-500">Opening a document is recorded in the audit log.</p></section>

  <section class="mt-5 bg-white border rounded-lg p-4" aria-labelledby="jb-h"><h2 id="jb-h" class="font-bold text-lg">Jobs</h2>
    <?php if (!$jobs): ?><p class="text-slate-600 mt-2">No jobs posted yet.</p><?php else: ?>
      <ul class="mt-2 text-sm divide-y"><?php foreach ($jobs as $j): ?><li class="py-1.5 flex justify-between gap-3"><a class="underline" href="<?= e(url('/admin/jobs/' . (int) $j['id'] . '/review')) ?>"><?= e($j['title']) ?></a><span class="badge"><?= e($j['status']) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?></section>

  <section class="mt-5 bg-white border rounded-lg p-4" aria-labelledby="dec-h"><h2 id="dec-h" class="font-bold text-lg">Decision</h2>
    <?php if (!empty($e['review_note'])): ?><p class="mt-1 text-sm"><strong>Last note sent:</strong> <?= e($e['review_note']) ?></p><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/employers/' . (int) $e['id'] . '/decision')) ?>" class="mt-3 space-y-3"><?= csrf_field() ?>
      <div><label class="label" for="note">Note to the company (required when asking for changes or rejecting)</label>
        <textarea class="field" id="note" name="note" rows="3" maxlength="1000" placeholder="e.g. The CAC certificate is unreadable. Please upload a clearer scan."></textarea></div>
      <div class="flex flex-wrap gap-3">
        <button class="btn btn-primary" type="submit" name="decision" value="approve" onclick="return confirm('Approve this company? It can post jobs straight away.')">Approve</button>
        <button class="btn btn-ghost" type="submit" name="decision" value="changes">Ask for changes</button>
        <button class="btn btn-ghost !text-red-700" type="submit" name="decision" value="reject" onclick="return confirm('Reject this company?')">Reject</button>
      </div>
      <p class="text-sm text-slate-600">The company is emailed the result. &ldquo;Ask for changes&rdquo; lets them fix and resubmit.</p>
    </form></section>
</div>
