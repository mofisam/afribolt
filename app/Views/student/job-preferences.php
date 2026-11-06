<a class="text-sm underline text-bolt-dark" href="<?= e(url('/student/jobs')) ?>">&larr; Jobs for you</a>
<h1 class="mt-1 text-3xl font-extrabold">Job preferences</h1>
<p class="text-slate-600 max-w-2xl">The more you tell us, the better your matches. We also use the topics of the courses you take.</p>
<form method="post" action="<?= e(url('/student/jobs/preferences')) ?>" class="mt-6 max-w-3xl space-y-7 bg-white border rounded-lg p-6">
  <?= csrf_field() ?>
  <fieldset><legend class="font-bold text-lg">What kind of work interests you?</legend>
    <div class="mt-3 flex flex-wrap gap-2"><?php foreach ($categories as $slug => $name): ?><label class="pill"><input type="checkbox" name="categories[]" value="<?= e($slug) ?>" <?= in_array($slug, $p['categories'], true) ? 'checked' : '' ?>> <?= e($name) ?></label><?php endforeach; ?></div></fieldset>
  <div><label class="font-bold text-lg" for="skills">Your skills and tools</label><p class="text-sm text-slate-600">Separate with commas, e.g. <em>excel, customer service, php, graphic design</em></p>
    <textarea class="field mt-2" id="skills" name="skills" rows="2" maxlength="500"><?= e($p['skills']) ?></textarea></div>
  <fieldset><legend class="font-bold text-lg">Where do you want to work?</legend>
    <details class="mt-2" <?= $p['states'] ? 'open' : '' ?>><summary class="cursor-pointer underline text-bolt-dark">Choose states (<?= count($p['states']) ?> selected)</summary>
      <div class="mt-3 flex flex-wrap gap-2"><?php foreach ($states as $s): ?><label class="pill"><input type="checkbox" name="states[]" value="<?= e($s) ?>" <?= in_array($s, $p['states'], true) ? 'checked' : '' ?>> <?= e($s) ?></label><?php endforeach; ?></div></details>
    <div class="mt-3 flex flex-wrap gap-2"><?php foreach (['remote' => 'Remote', 'hybrid' => 'Hybrid', 'onsite' => 'On-site'] as $k => $l): ?><label class="pill"><input type="checkbox" name="work_modes[]" value="<?= $k ?>" <?= in_array($k, $p['work_modes'], true) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div></fieldset>
  <fieldset><legend class="font-bold text-lg">Experience level</legend>
    <div class="mt-3 flex flex-wrap gap-2"><?php foreach (['entry' => 'Entry level / graduate', 'mid' => 'Mid level', 'senior' => 'Senior', 'executive' => 'Executive'] as $k => $l): ?><label class="pill"><input type="checkbox" name="levels[]" value="<?= $k ?>" <?= in_array($k, $p['levels'], true) ? 'checked' : '' ?>> <?= e($l) ?></label><?php endforeach; ?></div></fieldset>
  <label class="flex items-start gap-3"><input type="hidden" name="alerts_enabled" value="0"><input type="checkbox" name="alerts_enabled" value="1" class="mt-1 w-5 h-5" <?= $p['alerts_enabled'] ? 'checked' : '' ?>>
    <span><span class="font-bold">Email me new matching jobs</span><span class="block text-sm text-slate-600">At most one short email every few days, only when there is something good.</span></span></label>
  <p class="text-xs text-slate-500">Privacy: only these choices and your course topics are used to recommend jobs. If AI explanations are on, your name and email are never sent to the AI provider.</p>
  <button class="btn btn-primary" type="submit">Save preferences</button>
</form>
