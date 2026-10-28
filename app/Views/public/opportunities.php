<section class="bg-bolt-tint">
  <div class="max-w-4xl mx-auto px-4 py-14">
    <h1 class="text-4xl md:text-5xl font-extrabold">Your dream job is closer than you think</h1>
    <p class="mt-4 text-lg max-w-prose">Get the support, confidence and tools you need to stand out. The problem usually isn't your potential. It's knowing how to show it.</p>
    <div class="mt-6 flex flex-wrap gap-3">
      <a class="btn btn-accent" href="<?= e(url('/contact?type=career_consultation')) ?>">Book a free consultation</a>
      <a class="btn btn-ghost" href="<?= e(url('/courses')) ?>">See live courses</a>
    </div>
  </div>
</section>
<section class="max-w-5xl mx-auto px-4 py-12">
  <h2 class="text-3xl font-bold">How we help you get noticed</h2>
  <div class="mt-6 grid md:grid-cols-2 gap-5">
    <?php foreach ([
      ['Professional CV & resume revamp', 'ATS-friendly formatting, professional resume writing, LinkedIn profile optimisation, cover-letter support and industry-specific enhancements.'],
      ['Job search support', 'Stop applying blindly. We help you find suitable opportunities, position yourself well and apply with a clear strategy.'],
      ['Internship opportunities', 'Bridge the experience gap with internships that build practical skills, industry exposure and professional networks.'],
      ['Interview preparation & coaching', 'Master common questions, communicate your value, reduce anxiety, prepare for virtual and in-person interviews, and learn salary negotiation.'],
      ['Job reference letter support', 'Professional reference letters that reinforce your credibility for employment, internships, volunteering or academic applications.'],
      ['Career development programmes', 'CV enhancement, interview coaching, LinkedIn optimisation, personal branding, career planning and professional skills development.'],
    ] as [$t, $d]): ?>
      <article class="border border-slate-200 rounded-lg p-5 bg-white"><h3 class="font-bold text-xl"><?= e($t) ?></h3><p class="mt-2 text-slate-700"><?= e($d) ?></p></article>
    <?php endforeach; ?>
  </div>
  <h2 class="mt-14 text-3xl font-bold">Who we help</h2>
  <ul class="mt-4 grid sm:grid-cols-2 gap-x-8 gap-y-1 list-disc pl-5">
    <?php foreach (['Recent graduates', 'College and university students', 'Entry-level professionals', 'Career changers', 'International students', 'New immigrants', 'Experienced professionals', 'Job seekers returning to the workforce'] as $w): ?><li><?= e($w) ?></li><?php endforeach; ?>
  </ul>
  <div class="mt-12 bg-ink text-white rounded-lg p-8">
    <h2 class="text-2xl font-bold">Ready to get started?</h2>
    <p class="mt-1">Together we'll create a clear path toward your next job, internship or career breakthrough.</p>
    <a class="btn btn-accent mt-4" href="<?= e(url('/contact?type=career_consultation')) ?>">Book your free career consultation</a>
  </div>
</section>
