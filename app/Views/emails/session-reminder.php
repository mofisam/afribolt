<p>Hi <?= e($name) ?>,</p>
<p>Your class <strong><?= e($session['title']) ?></strong> starts at <?= e(local_date($session['starts_at'], $session['timezone'])) ?>.</p>
<p>The Join Class button on your dashboard opens <?= (int) $session['join_early_minutes'] ?> minutes before the start.</p>
<p><a href="<?= e(url('/student/dashboard')) ?>" style="color:#1B8CCB;font-weight:bold">Open my dashboard</a></p>
