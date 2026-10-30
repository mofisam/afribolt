<?php
$ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => []];
foreach ($faqs as $items) { foreach ($items as [$q, $a]) { $ld['mainEntity'][] = ['@type' => 'Question', 'name' => $q, 'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($a))]]; } }
?>
<section class="bg-bolt-tint border-b border-slate-200">
  <div class="max-w-3xl mx-auto px-4 py-10 md:py-14">
    <h1 class="text-3xl md:text-5xl font-extrabold">Frequently asked questions</h1>
    <p class="mt-3 text-lg text-slate-700">Quick answers about classes, payments and your account.</p>
    <div class="mt-6">
      <label class="sr-only" for="faq-search">Search the FAQs</label>
      <input class="field !py-3 !text-base" type="search" id="faq-search" placeholder="Search, e.g. refund, join class, timezone" autocomplete="off">
      <p class="hint" id="faq-count" role="status" aria-live="polite"></p>
    </div>
    <nav class="mt-3 flex flex-wrap gap-2" aria-label="Topics">
      <?php foreach (array_keys($faqs) as $g): ?><a class="badge" href="#<?= e(slugify($g)) ?>"><?= e($g) ?></a><?php endforeach; ?>
    </nav>
  </div>
</section>

<div class="max-w-3xl mx-auto px-4 py-10 faq">
  <?php foreach ($faqs as $group => $items): ?>
    <section data-faq-group class="mb-10" id="<?= e(slugify($group)) ?>" style="scroll-margin-top:5.5rem">
      <h2 class="text-2xl font-extrabold mb-4"><?= e($group) ?></h2>
      <?php foreach ($items as [$q, $a]): ?>
        <details><summary><?= e($q) ?></summary><div class="ans"><?= $a /* trusted HTML from controller */ ?></div></details>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>

  <p id="faq-empty" class="text-center text-slate-600 py-8" hidden>No answers match your search. Try a different word, or ask us directly below.</p>

  <div class="mt-4 rounded-lg bg-ink text-white p-6 md:p-8 md:flex items-center justify-between gap-6">
    <div><h2 class="text-2xl font-extrabold">Still have a question?</h2><p class="mt-1 text-slate-200">We usually reply within one working day.</p></div>
    <div class="mt-4 md:mt-0 flex flex-wrap gap-3">
      <a class="btn btn-accent" href="<?= e(url('/contact')) ?>">Send us a message</a>
      <a class="btn border-white text-white hover:bg-white hover:text-ink" href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a>
    </div>
  </div>
</div>
<script type="application/ld+json"><?= json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<script src="<?= e(asset('js/pages.js')) ?>" defer></script>
