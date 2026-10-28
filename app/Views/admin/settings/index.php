<h1 class="text-3xl font-extrabold">Settings</h1>
<p class="mt-1 text-slate-600 max-w-2xl">Default timezone and currency pre-fill new cohorts and sessions. Existing cohorts keep their own values.</p>
<form method="post" action="<?= e(url('/admin/settings')) ?>" class="mt-5 bg-white border rounded-lg p-6 space-y-5 max-w-3xl">
  <?= csrf_field() ?>
  <?php foreach ($settings as $s): $id = (int) $s['id']; $v = (string) $s['setting_value']; ?>
    <div>
      <?php if ($s['value_type'] === 'boolean'): ?>
        <label class="flex items-start gap-2"><input type="hidden" name="s[<?= $id ?>]" value="0"><input type="checkbox" name="s[<?= $id ?>]" value="1" class="mt-1" <?= $v === '1' ? 'checked' : '' ?>>
          <span><span class="font-bold"><?= e($s['setting_key']) ?></span><?php if ($s['description']): ?><span class="block text-sm text-slate-600"><?= e($s['description']) ?></span><?php endif; ?></span></label>
      <?php else: ?>
        <label class="label" for="s_<?= $id ?>"><?= e($s['setting_key']) ?></label>
        <?php if ($s['setting_key'] === 'default_timezone'): ?>
          <select class="field" id="s_<?= $id ?>" name="s[<?= $id ?>]"><?php foreach ($timezones as $tz): ?><option <?= $v === $tz ? 'selected' : '' ?>><?= e($tz) ?></option><?php endforeach; ?>
            <?php if ($v && !in_array($v, $timezones, true)): ?><option selected><?= e($v) ?></option><?php endif; ?></select>
        <?php elseif ($s['value_type'] === 'json'): ?><textarea class="field font-mono text-sm" id="s_<?= $id ?>" name="s[<?= $id ?>]" rows="3"><?= e($v) ?></textarea>
        <?php else: ?><input class="field" id="s_<?= $id ?>" name="s[<?= $id ?>]" value="<?= e($v) ?>" <?= $s['value_type'] === 'number' ? 'type="number" step="any"' : '' ?>><?php endif; ?>
        <?php if ($s['description']): ?><p class="mt-1 text-sm text-slate-600"><?= e($s['description']) ?></p><?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
  <button class="btn btn-primary" type="submit">Save settings</button>
</form>

<details class="mt-8 max-w-3xl"><summary class="cursor-pointer font-bold underline text-bolt-dark">Add a new setting</summary>
  <form method="post" action="<?= e(url('/admin/settings/add')) ?>" class="mt-3 bg-white border rounded-lg p-6 grid sm:grid-cols-2 gap-4">
    <?= csrf_field() ?>
    <div><label class="label" for="setting_key">Key</label><input class="field" id="setting_key" name="setting_key" placeholder="e.g. join_early_minutes_default" required></div>
    <div><label class="label" for="value_type">Type</label><select class="field" id="value_type" name="value_type"><option>string</option><option>number</option><option>boolean</option><option>json</option></select></div>
    <div class="sm:col-span-2"><label class="label" for="setting_value">Value</label><input class="field" id="setting_value" name="setting_value"></div>
    <div class="sm:col-span-2"><label class="label" for="description">Description</label><input class="field" id="description" name="description"></div>
    <label class="flex gap-2"><input type="hidden" name="is_public" value="0"><input type="checkbox" name="is_public" value="1"> Public (safe to show on the website)</label>
    <div><button class="btn btn-primary" type="submit">Add setting</button></div>
  </form></details>
