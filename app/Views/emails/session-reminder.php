<!--preheader:Your class <?= e($session['title']) ?> is coming up.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Your class starts soon</h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, this is a reminder about your upcoming class.</p>
<?= App\Core\View::partial('emails/_details', ['rows' => [
    'Class' => $session['title'],
    'Starts' => local_date($session['starts_at'], $session['timezone']),
]]) ?>
<p style="margin:0 0 16px">The <strong>Join Class</strong> button on your dashboard opens <?= (int) $session['join_early_minutes'] ?> minutes before the start.</p>
<?= App\Core\View::partial('emails/_button', ['url' => url('/student/dashboard'), 'label' => 'Open my dashboard']) ?>
