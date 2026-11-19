<?php /** @var array $job @var string $action @var array $categories @var array $states @var bool $approval */
$v = fn(string $k, $d = '') => old($k, $job[$k] ?? $d);
$sel = fn($cur, $val) => (string) $cur === (string) $val ? 'selected' : '';
$isEdit = !empty($job['id']);
?>
<a class="text-sm underline text-bolt-dark" href="<?= e(url('/employer/jobs')) ?>">&larr; My jobs</a>
<h1 class="mt-1 text-3xl font-extrabold"><?= $isEdit ? 'Edit job' : 'Post a job' ?></h1>
<p class="text-slate-600">Be clear and honest. Never ask applicants to pay anything. <?= $approval ? 'New jobs are checked by our team before they go live.' : 'Your job goes live straight away.' ?></p>

<form method="post" action="<?= e($action) ?>" class="mt-5 bg-white border rounded-lg p-5 grid sm:grid-cols-2 gap-4" novalidate>
  <?= csrf_field() ?>
  <div class="sm:col-span-2"><label class="label" for="title">Job title</label><input class="field" id="title" name="title" value="<?= e($v('title')) ?>" required><?= field_error('title') ?></div>
  <div><label class="label" for="cat">Category</label><select class="field" id="cat" name="category_id"><option value="">Choose</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $sel($v('category_id'), $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
  <div><label class="label" for="type">Job type</label><select class="field" id="type" name="employment_type"><?php foreach (['full_time' => 'Full time', 'part_time' => 'Part time', 'contract' => 'Contract', 'internship' => 'Internship', 'temporary' => 'Temporary', 'volunteer' => 'Volunteer'] as $k => $l): ?><option value="<?= $k ?>" <?= $sel($v('employment_type', 'full_time'), $k) ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div><label class="label" for="mode">Work mode</label><select class="field" id="mode" name="work_mode"><?php foreach (['onsite' => 'On site', 'hybrid' => 'Hybrid', 'remote' => 'Remote', 'unspecified' => 'Not specified'] as $k => $l): ?><option value="<?= $k ?>" <?= $sel($v('work_mode', 'onsite'), $k) ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div><label class="label" for="lvl">Experience level</label><select class="field" id="lvl" name="experience_level"><?php foreach (['entry' => 'Entry level', 'mid' => 'Mid level', 'senior' => 'Senior', 'executive' => 'Executive', 'unspecified' => 'Not specified'] as $k => $l): ?><option value="<?= $k ?>" <?= $sel($v('experience_level', 'entry'), $k) ?>><?= $l ?></option><?php endforeach; ?></select></div>
  <div><label class="label" for="state">State</label><select class="field" id="state" name="state"><option value="">Choose</option><?php foreach ($states as $s): ?><option <?= $sel($v('state'), $s) ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <div><label class="label" for="city">City or area</label><input class="field" id="city" name="city" value="<?= e($v('city')) ?>"></div>
  <div class="sm:col-span-2 grid grid-cols-3 gap-3">
    <div><label class="label" for="smin">Pay from (optional)</label><input class="field" type="number" min="0" id="smin" name="salary_min" value="<?= e($v('salary_min')) ?>"><?= field_error('salary_min') ?></div>
    <div><label class="label" for="smax">Pay up to</label><input class="field" type="number" min="0" id="smax" name="salary_max" value="<?= e($v('salary_max')) ?>"><?= field_error('salary_max') ?></div>
    <div><label class="label" for="cur">Currency</label><select class="field" id="cur" name="salary_currency"><?php foreach (['NGN', 'USD', 'GBP', 'EUR'] as $c): ?><option <?= $sel($v('salary_currency', 'NGN'), $c) ?>><?= $c ?></option><?php endforeach; ?></select></div>
  </div>
  <div class="sm:col-span-2"><label class="label" for="sum">Short summary (shown in search results)</label><textarea class="field" id="sum" name="summary" rows="2" maxlength="600" required><?= e($v('summary')) ?></textarea><?= field_error('summary') ?></div>
  <div class="sm:col-span-2"><label class="label" for="desc">Full job description</label><textarea class="field" id="desc" name="description" rows="10" maxlength="8000" required placeholder="Responsibilities, requirements, how to stand out&hellip;"><?= e($v('description')) ?></textarea><?= field_error('description') ?>
    <p class="mt-1 text-sm text-slate-600">Tip: start a line with <code>- </code> for bullet points and <code>## </code> for headings.</p></div>
  <div><label class="label" for="skills">Skills (comma separated)</label><input class="field" id="skills" name="skills_text" value="<?= e($v('skills_text')) ?>" placeholder="Excel, Customer service"><?= field_error('skills_text') ?></div>
  <div><label class="label" for="dl">Application deadline (optional)</label><input class="field" type="date" id="dl" name="deadline" value="<?= e($v('deadline')) ?>"><?= field_error('deadline') ?></div>
  <div class="sm:col-span-2 flex flex-wrap gap-3"><button class="btn btn-primary" type="submit"><?= $isEdit ? 'Save changes' : 'Post job' ?></button><a class="btn btn-ghost" href="<?= e(url('/employer/jobs')) ?>">Cancel</a></div>
</form>
