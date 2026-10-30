<?php
/** @var string $title @var string $intro @var array $summary @var array $sections [id => [title, html]] @var string $updated @var bool $draft */
?>
<section class="bg-bolt-tint border-b border-slate-200">
  <div class="max-w-5xl mx-auto px-4 py-10 md:py-14">
    <h1 class="text-3xl md:text-5xl font-extrabold"><?= e($title) ?></h1>
    <p class="mt-3 text-lg text-slate-700 max-w-2xl"><?= e($intro) ?></p>
    <p class="mt-4 text-sm text-slate-600 flex flex-wrap items-center gap-x-4 gap-y-2">
      <span>Last updated: <strong><?= e($updated) ?></strong></span>
      <button type="button" class="underline text-bolt-dark no-print" data-print>Print or save as PDF</button>
    </p>
  </div>
</section>

<?php if ($draft): ?>
<div class="bg-yellow-50 border-b border-yellow-300 no-print" role="note">
  <p class="max-w-5xl mx-auto px-4 py-3 text-sm"><strong>Draft for review.</strong> This page has not been approved by the business or a lawyer yet. <mark class="ph">Highlighted terms</mark> need to be confirmed.</p>
</div>
<?php endif; ?>

<div class="max-w-5xl mx-auto px-4 py-10 grid lg:grid-cols-[15rem_minmax(0,1fr)] gap-10">
  <aside class="toc-wrap">
    <details class="lg:hidden border rounded-lg bg-white">
      <summary class="cursor-pointer font-bold p-3">On this page</summary>
      <nav class="toc pb-2" aria-label="Contents"><?php foreach ($sections as $id => [$t]): ?><a href="#<?= e($id) ?>"><?= e($t) ?></a><?php endforeach; ?></nav>
    </details>
    <nav class="toc hidden lg:block sticky top-24" aria-label="Contents">
      <p class="font-bold text-sm uppercase tracking-wide text-slate-500 px-3 mb-2">On this page</p>
      <?php foreach ($sections as $id => [$t]): ?><a href="#<?= e($id) ?>"><?= e($t) ?></a><?php endforeach; ?>
    </nav>
  </aside>

  <article class="legal min-w-0">
    <?php if ($summary): ?>
    <div class="rounded-lg border-l-4 border-bolt bg-white border border-slate-200 p-5" id="summary">
      <p class="font-display font-bold text-lg">The short version</p>
      <ul class="!mt-2"><?php foreach ($summary as $s): ?><li><?= $s /* trusted HTML */ ?></li><?php endforeach; ?></ul>
      <p class="text-sm text-slate-600 !mt-3">This summary is for convenience. The full policy below is what applies.</p>
    </div>
    <?php endif; ?>
    <?php foreach ($sections as $id => [$t, $html]): ?>
      <h2 id="<?= e($id) ?>"><?= e($t) ?></h2>
      <?= $html ?>
    <?php endforeach; ?>
    <p class="mt-12 pt-6 border-t text-sm text-slate-600 no-print">Questions about this page? <a href="<?= e(url('/contact')) ?>">Contact us</a> or email <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>.
      <a class="ml-2" href="#main">Back to top</a></p>
  </article>
</div>
<script src="<?= e(asset('js/pages.js')) ?>" defer></script>
