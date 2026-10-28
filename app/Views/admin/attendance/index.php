<h1 class="text-3xl font-extrabold">Attendance</h1>
<form method="get" class="mt-5 bg-white border rounded-lg p-4 flex flex-wrap gap-3 items-end">
  <div class="grow max-w-xl"><label class="label text-sm" for="session_id">Class session</label>
    <select class="field" id="session_id" name="session_id"><option value="">Choose a class...</option>
      <?php foreach ($sessions as $s): ?><option value="<?= (int) $s['id'] ?>" <?= $session && (int) $session['id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['cohort_name'] . ' | ' . $s['title'] . ' | ' . local_date($s['starts_at'], $s['timezone'], 'j M Y')) ?></option><?php endforeach; ?>
    </select></div>
  <button class="btn btn-primary" type="submit">Load</button>
</form>
<?php if ($session): ?>
<form method="post" action="<?= e(url('/admin/attendance')) ?>" class="mt-5">
  <?= csrf_field() ?><input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
  <h2 class="font-bold text-xl"><?= e($session['title']) ?> <span class="text-slate-500 font-normal">&middot; <?= e($session['cohort_name']) ?> &middot; <?= e(local_date($session['starts_at'], $session['timezone'])) ?></span></h2>
  <p class="text-sm text-slate-600 mt-1">Students who used the Join Class button already show a join time. Mark everyone, then save.</p>
  <div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-3">Student</th><th class="p-3">Joined via platform</th><th class="p-3">Attendance</th><th class="p-3">Note</th></tr></thead><tbody>
    <?php foreach ($roster as $r): $cur = $r['attendance_status'] ?? ($r['joined_at'] ? 'present' : 'absent'); ?>
      <tr class="border-t"><td class="p-3"><?= e($r['first_name'] . ' ' . $r['last_name']) ?><br><span class="text-slate-500"><?= e($r['enrolment_number']) ?></span></td>
        <td class="p-3"><?= $r['joined_at'] ? e(local_date($r['joined_at'], $session['timezone'], 'g:i A')) : 'No' ?></td>
        <td class="p-3"><select class="field !py-1" name="status[<?= (int) $r['enrolment_id'] ?>]" aria-label="Attendance for <?= e($r['first_name']) ?>">
          <?php foreach (['present', 'late', 'absent', 'excused'] as $o): ?><option value="<?= $o ?>" <?= $cur === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option><?php endforeach; ?></select></td>
        <td class="p-3"><input class="field !py-1" name="notes[<?= (int) $r['enrolment_id'] ?>]" value="<?= e($r['notes'] ?? '') ?>" maxlength="500"></td></tr>
    <?php endforeach; if (!$roster): ?><tr><td class="p-4 text-slate-500" colspan="4">No confirmed students in this cohort.</td></tr><?php endif; ?></tbody></table></div>
  <?php if ($roster): ?><button class="btn btn-primary mt-4" type="submit">Save attendance</button><?php endif; ?>
</form>
<?php endif; ?>
