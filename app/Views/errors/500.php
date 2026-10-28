<section class="max-w-xl mx-auto px-4 py-24 text-center">
  <p class="font-display font-extrabold text-bolt text-6xl">500</p>
  <h1 class="mt-2 text-3xl font-bold">Something went wrong</h1>
  <p class="mt-3 text-slate-600"><?= e($message ?? "We've logged the problem. Please try again shortly.") ?></p>
  <a class="btn btn-primary mt-6" href="<?= e(url('/')) ?>">Back to home</a>
</section>
