<?php
/** @var ?int $seatsLeft @var int $seatsTotal */
$n = fn(int $v) => '₦' . number_format($v);
$stages = [
  ['Register', 'Create your account and complete a short intake form: target role, experience, current CV and LinkedIn link.', 'your profile is complete'],
  ['Enrol in the course', 'Learn at your own pace on the Afribolt platform, with a certificate at the end.', 'you finish the course'],
  ['Live projects', 'Work in a team on supervised projects built from real or realistic Nigerian business briefs, and get your work assessed.', 'you complete and pass the assigned projects'],
  ['Full Accelerator', 'Own a project yourself over 3 to 6 months, with mentor check-ins along the way. You leave with a verified project record.', 'your assigned projects are complete. This unlocks reference eligibility'],
  ['Interview Ready', 'Three mock interviews, behavioural and role-specific, with written feedback, plus a job application strategy session.', 'you complete all three mock interviews'],
  ['Job Ready', 'A revamped CV built from your verified project record, a LinkedIn rewrite, a cover letter and your Afribolt reference letter.', 'your CV and reference letter are issued'],
];
$values = [
  'Stage 2: Enrol in the course' => [['Self-paced course and certificate', 20000]],
  'Stage 3: Live projects' => [['Supervised team projects with assessment', 120000]],
  'Stage 4: Full Accelerator' => [['3 to 6 months of project ownership with mentor check-ins', 120000], ['Verified project record', 30000]],
  'Stage 5: Interview Ready' => [['3 mock interviews with written feedback (₦25,000 each)', 75000], ['Job application strategy session', 25000]],
  'Stage 6: Job Ready' => [['CV revamp (2 pages, ATS-friendly, 1 revision)', 40000], ['LinkedIn headline and summary rewrite', 20000], ['Cover letter', 10000], ['Reference letter and job reference support', 40000]],
];
$total = 0; foreach ($values as $rows) { foreach ($rows as $r) { $total += $r[1]; } }
$founding = 50000;
$faqs = [
  ['What happens after the first 100 students?', 'The ₦50,000 founding price is for the first 100 students to pay. Once those places are taken it closes. <a class="underline" href="' . e(url('/contact?type=career_consultation')) . '">Contact us</a> to ask what is available next.'],
  ['Do I need experience to join?', 'The intake form asks for your target role and experience so we can plan your path. If you are not sure the programme fits you, <a class="underline" href="' . e(url('/contact?type=career_consultation')) . '">book a free consultation</a> before you pay.'],
  ['When do I get my reference letter?', 'After you complete your assigned projects. It is issued together with your revamped CV at Stage 6, and it confirms what you did.'],
  ['How long does it take?', 'You study the course at your own pace. The Full Accelerator project runs for 3 to 6 months. Your total time depends on how quickly you move through each stage.'],
  ['Can I get a refund?', 'Refunds are available only before any stage work begins. See the <a class="underline" href="' . e(url('/refund-policy')) . '">refund policy</a>.'],
  ['Does Afribolt guarantee a job?', 'No. Your reference confirms what you did. It is not a guarantee of employment.'],
];
?>
<section class="bg-ink text-white">
  <div class="max-w-5xl mx-auto px-4 py-14 grid md:grid-cols-2 gap-10 items-center">
    <div>
      <p class="font-bold text-volt">Afribolt Career Launch</p>
      <h1 class="mt-2 text-4xl md:text-5xl font-extrabold">Learn it. Build it. Get hired.</h1>
      <p class="mt-4 text-lg text-slate-200 max-w-prose">Afribolt Career Launch takes you from a free account to a job-ready CV backed by real project work, interview practice and a reference from Afribolt.</p>
      <a class="btn btn-accent mt-6" href="#register">Register now</a>
    </div>
    <div class="bg-white text-ink rounded-lg p-6">
      <p class="inline-block bg-volt font-bold text-sm px-3 py-1 rounded">Founding price: first <?= (int) $seatsTotal ?> students</p>
      <p class="mt-3 text-slate-600">Valued at <s><?= e($n($total)) ?></s></p>
      <p class="font-display font-extrabold text-5xl"><?= e($n($founding)) ?> <span class="text-lg font-semibold text-slate-600">one-time</span></p>
      <p class="mt-1 text-slate-700">Limited to the first <?= (int) $seatsTotal ?> students to pay.<?= $seatsLeft !== null ? ' <strong>' . (int) $seatsLeft . ' of ' . (int) $seatsTotal . ' places left.</strong>' : '' ?></p>
      <ul class="mt-4 space-y-1 list-disc pl-5"><?php foreach (['Course and certificate', 'Live projects and the Full Accelerator', 'Interview prep and CV revamp', 'Job reference support'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul>
    </div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-4 py-12" aria-labelledby="stages-h">
  <h2 id="stages-h" class="text-3xl font-bold">Your six stages</h2>
  <p class="mt-2 text-slate-700 max-w-prose">Each stage unlocks the next. Your CV and reference come last because they rest on work you have actually done.</p>
  <ol class="mt-6 grid md:grid-cols-2 gap-5">
    <?php foreach ($stages as $i => [$t, $d, $done]): ?>
      <li class="border border-slate-200 rounded-lg p-5 bg-white">
        <p class="flex items-center gap-3"><span class="inline-flex w-8 h-8 rounded-full bg-bolt text-white font-bold items-center justify-center" aria-hidden="true"><?= $i + 1 ?></span><span class="font-bold text-xl"><span class="sr-only">Stage <?= $i + 1 ?>: </span><?= e($t) ?></span></p>
        <p class="mt-2 text-slate-700"><?= e($d) ?></p>
        <p class="mt-2 text-sm"><strong>Done when:</strong> <?= e($done) ?>.</p>
      </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="bg-bolt-tint">
  <div class="max-w-5xl mx-auto px-4 py-12 grid md:grid-cols-2 gap-8">
    <div><h2 class="text-2xl font-bold">What you get</h2>
      <ul class="mt-3 space-y-2 list-disc pl-5"><?php foreach (['Course certificate', 'A verified project record', '3 mock interviews with written feedback', 'CV revamp: 2 pages, ATS-friendly, 1 revision', 'LinkedIn headline and summary rewrite', 'Afribolt reference letter'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul></div>
    <div><h2 class="text-2xl font-bold">What we ask of you</h2>
      <ul class="mt-3 space-y-2 list-disc pl-5"><?php foreach (['Finish each stage to unlock the next', 'Complete your assigned projects before a reference is issued', 'Attend your scheduled mentor and interview sessions'] as $i): ?><li><?= e($i) ?></li><?php endforeach; ?></ul>
      <p class="mt-3 text-sm text-slate-700">Your reference confirms what you did. It is not a guarantee of employment.</p></div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-4 py-12" aria-labelledby="worth-h">
  <h2 id="worth-h" class="text-3xl font-bold">What it is worth</h2>
  <p class="mt-2 text-slate-700 max-w-prose">Registration is free and Stage 1 carries no charge. Values below are calculated at one-to-one rates, the price of buying each item on its own.</p>
  <div class="mt-5 bg-white border rounded-lg overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-left"><tr><th class="p-3">Item</th><th class="p-3 text-right">Value</th></tr></thead>
      <tbody>
      <?php foreach ($values as $stage => $rows): ?>
        <tr class="border-t bg-slate-50"><th class="p-3 text-left" colspan="2"><?= e($stage) ?></th></tr>
        <?php foreach ($rows as [$label, $v]): ?><tr class="border-t"><td class="p-3"><?= e($label) ?></td><td class="p-3 text-right whitespace-nowrap"><?= e($n($v)) ?></td></tr><?php endforeach; ?>
      <?php endforeach; ?>
        <tr class="border-t font-bold"><td class="p-3">Total value</td><td class="p-3 text-right"><?= e($n($total)) ?></td></tr>
        <tr class="border-t font-bold bg-volt/30"><td class="p-3">Founding price, first <?= (int) $seatsTotal ?> students</td><td class="p-3 text-right"><?= e($n($founding)) ?></td></tr>
      </tbody>
    </table>
  </div>
  <p class="mt-4 text-slate-700">Just want the course? You can take it on its own and pay only for the certificate (<?= e($n(20000)) ?>). <a class="underline text-bolt-dark" href="<?= e(url('/courses')) ?>">Browse courses</a>.</p>
</section>

<section id="register" class="bg-ink text-white" style="scroll-margin-top:5rem">
  <div class="max-w-2xl mx-auto px-4 py-12">
    <h2 class="text-3xl font-bold">Start at Stage 1</h2>
    <p class="mt-2 text-slate-200">Fill in the form and we will send your payment link and next steps.</p>
    <?= App\Core\View::partial('partials/flash') ?>
    <form method="post" action="<?= e(url('/career-accelerator/register')) ?>" class="mt-6 bg-white text-ink rounded-lg p-6 space-y-4" novalidate>
      <?= csrf_field() ?>
      <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <div><label class="label" for="cl_name">Full name</label><input class="field" id="cl_name" name="full_name" value="<?= e(old('full_name')) ?>" autocomplete="name" required><?= field_error('full_name') ?></div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="label" for="cl_email">Email</label><input class="field" type="email" id="cl_email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required><?= field_error('email') ?></div>
        <div><label class="label" for="cl_phone">Phone or WhatsApp</label><input class="field" id="cl_phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" required><?= field_error('phone') ?></div>
      </div>
      <div><label class="label" for="cl_role">Target role</label><input class="field" id="cl_role" name="target_role" value="<?= e(old('target_role')) ?>" placeholder="e.g. Project coordinator" required><?= field_error('target_role') ?></div>
      <div class="grid sm:grid-cols-2 gap-4">
        <div><label class="label" for="cl_li">LinkedIn link (optional)</label><input class="field" type="url" id="cl_li" name="linkedin_url" value="<?= e(old('linkedin_url')) ?>" placeholder="https://www.linkedin.com/in/..."><?= field_error('linkedin_url') ?></div>
        <div><label class="label" for="cl_ref">Referral code (optional)</label><input class="field" id="cl_ref" name="referral_code" value="<?= e(old('referral_code')) ?>"><?= field_error('referral_code') ?></div>
      </div>
      <button class="btn btn-accent" type="submit">Register and get payment link</button>
      <p class="text-sm text-slate-700">Founding price: <?= e($n($founding)) ?> for the first <?= (int) $seatsTotal ?> students to pay. Valued at <?= e($n($total)) ?>. Refunds are available only before any stage work begins.</p>
    </form>
  </div>
</section>

<section class="max-w-3xl mx-auto px-4 py-12 faq" aria-labelledby="faq-h">
  <h2 id="faq-h" class="text-3xl font-bold mb-4">Questions</h2>
  <?php foreach ($faqs as [$q, $a]): ?><details><summary><?= e($q) ?></summary><div class="ans"><?= $a /* trusted copy above */ ?></div></details><?php endforeach; ?>
</section>

<div class="border-t border-slate-200"></div>
<section class="max-w-5xl mx-auto px-4 py-12">
  <h2 class="text-3xl font-bold">More ways we can help</h2>
  <p class="mt-2 text-slate-700 max-w-prose">Not ready for the full programme? These career services are also available. Consultations are free.</p>
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
