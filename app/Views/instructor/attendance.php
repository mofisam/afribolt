<a class="text-sm underline text-bolt-dark" href="<?= e(url('/instructor/cohorts/' . $session['cohort_id'])) ?>">&larr; Back</a>
<h1 class="mt-1 text-3xl font-extrabold">Attendance</h1>
<p class="text-slate-600"><?= e($session['title']) ?> &middot; <?= e($session['cohort_name']) ?> &middot; <?= e(local_date($session['starts_at'], $session['timezone'])) ?></p>
<form method="post" action="<?= e(url('/instructor/sessions/' . $session['id'] . '/attendance')) ?>" class="mt-5">
  <?= csrf_field() ?>
  <div class="bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-3">Student</th><th class="p-3">Joined via platform</th><th class="p-3">Attendance</th><th class="p-3">Note</th></tr></thead><tbody>
    <?php foreach ($roster as $r): $cur = $r['attendance_status'] ?? ($r['joined_at'] ? 'present' : 'absent'); ?>
      <tr class="border-t"><td class="p-3"><?= e($r['first_name'] . ' ' . $r['last_name']) ?></td><td class="p-3"><?= $r['joined_at'] ? e(local_date($r['joined_at'], $session['timezone'], 'g:i A')) : 'No' ?></td>
        <td class="p-3"><select class="field !py-1" name="status[<?= (int) $r['enrolment_id'] ?>]" aria-label="Attendance for <?= e($r['first_name']) ?>">
          <?php foreach (['present', 'late', 'absent', 'excused'] as $o): ?><option value="<?= $o ?>" <?= $cur === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option><?php endforeach; ?></select></td>
        <td class="p-3"><input class="field !py-1" name="notes[<?= (int) $r['enrolment_id'] ?>]" value="<?= e($r['notes'] ?? '') ?>" maxlength="500"></td></tr>
    <?php endforeach; if (!$roster): ?><tr><td class="p-4 text-slate-500" colspan="4">No confirmed students.</td></tr><?php endif; ?></tbody></table></div>
  <?php if ($roster): ?><button class="btn btn-primary mt-4" type="submit">Save attendance</button><?php endif; ?>
</form>
