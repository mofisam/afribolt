<section class="max-w-2xl mx-auto px-4 py-12">
  <h1 class="text-4xl font-extrabold">Contact us</h1>
  <p class="mt-2 text-slate-700">Questions about a course, a cohort date, or the Career Accelerator? Send us a note.</p>
  <form method="post" action="<?= e(url('/contact')) ?>" class="mt-8 space-y-4" novalidate>
    <?= csrf_field() ?>
    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div><label class="label" for="enquiry_type">What is this about?</label>
      <select id="enquiry_type" name="enquiry_type" class="field">
        <?php foreach (['general' => 'General question', 'course' => 'A course or cohort', 'career_consultation' => 'Free career consultation'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= (old('enquiry_type', $type ?? 'general') === $v) ? 'selected' : '' ?>><?= e($l) ?></option>
        <?php endforeach; ?></select></div>
    <div><label class="label" for="full_name">Full name</label><input class="field" id="full_name" name="full_name" value="<?= e(old('full_name')) ?>" required><?= field_error('full_name') ?></div>
    <div class="grid sm:grid-cols-2 gap-4">
      <div><label class="label" for="email">Email</label><input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" required><?= field_error('email') ?></div>
      <div><label class="label" for="phone">Phone (optional)</label><input class="field" id="phone" name="phone" value="<?= e(old('phone')) ?>"><?= field_error('phone') ?></div>
    </div>
    <div><label class="label" for="subject">Subject (optional)</label><input class="field" id="subject" name="subject" value="<?= e(old('subject')) ?>"></div>
    <div><label class="label" for="message">Message</label><textarea class="field" id="message" name="message" rows="6" required><?= e(old('message')) ?></textarea><?= field_error('message') ?></div>
    <button class="btn btn-primary" type="submit">Send message</button>
  </form>
</section>
