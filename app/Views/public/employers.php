<?php $loggedIn = \App\Core\Auth::check('employer'); ?>
<section class="bg-ink text-white">
  <div class="max-w-5xl mx-auto px-4 py-14 grid md:grid-cols-2 gap-10 items-center">
    <div>
      <p class="font-bold text-volt">Afribolt for employers</p>
      <h1 class="mt-2 text-4xl md:text-5xl font-extrabold">Hire people who are ready to work.</h1>
      <p class="mt-4 text-lg text-slate-200 max-w-prose">Post your vacancies for free and receive applications from Afribolt students who are learning real, job-ready skills.</p>
      <div class="mt-6 flex flex-wrap gap-3">
        <?php if ($loggedIn): ?><a class="btn btn-accent" href="<?= e(url('/employer')) ?>">Go to my employer dashboard</a>
        <?php else: ?><a class="btn btn-accent" href="<?= e(url('/employer/register')) ?>">Register your company</a><a class="btn btn-ghost !text-white !border-white" href="<?= e(url('/employer/login')) ?>">Employer login</a><?php endif; ?>
      </div>
    </div>
    <div class="bg-white text-ink rounded-lg p-6">
      <p class="font-bold text-lg">Why companies use Afribolt</p>
      <ul class="mt-3 space-y-2 list-disc pl-5"><li>Reach students who are building skills in Afribolt courses</li><li>A &ldquo;Verified employer&rdquo; badge builds trust with applicants</li><li>Read cover notes, download CVs and track every applicant in one place</li><li>Post whenever you have a vacancy, no contract needed</li></ul>
    </div>
  </div>
</section>

<section class="max-w-5xl mx-auto px-4 py-12" aria-labelledby="how-h">
  <h2 id="how-h" class="text-3xl font-bold">How it works</h2>
  <ol class="mt-6 grid md:grid-cols-2 gap-5">
    <?php foreach ([
      ['Register your company', 'Create an account with your company name and work email. It takes a minute.'],
      ['Upload your documents', 'Add your CAC certificate or business registration and an ID of a director or authorised person. Only our verification team can see them.'],
      ['Get verified', 'Our team reviews your company and emails you the result. We do this to protect job seekers from scams.'],
      ['Post jobs and hire', 'Once verified, post jobs whenever you like. Students apply on Afribolt and you manage applicants from your dashboard.'],
    ] as $i => [$t, $d]): ?>
      <li class="border border-slate-200 rounded-lg p-5 bg-white"><p class="flex items-center gap-3"><span class="inline-flex w-8 h-8 rounded-full bg-bolt text-white font-bold items-center justify-center" aria-hidden="true"><?= $i + 1 ?></span><span class="font-bold text-xl"><?= e($t) ?></span></p><p class="mt-2 text-slate-700"><?= e($d) ?></p></li>
    <?php endforeach; ?>
  </ol>
  <p class="mt-8 text-sm text-slate-600">Afribolt will never ask applicants to pay anything, and neither should you. Jobs that ask applicants for money are removed and the company is suspended.</p>
</section>
