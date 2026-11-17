<!--preheader:You now have full access to <?= e($course['title']) ?>.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">You are in, <?= e($student['first_name']) ?>!</h1>
<p style="margin:0 0 16px">You now have full access to <strong><?= e($course['title']) ?></strong>.</p>
<?php if ($amount > 0): ?>
<?= App\Core\View::partial('emails/_details', ['rows' => ['Payment received' => money($amount, $course['price_currency'])]]) ?>
<?php endif; ?>
<p style="margin:0 0 16px">Learn at your own pace, on any device. Your progress is saved automatically.</p>
<?= App\Core\View::partial('emails/_button', ['url' => url('/learn/' . $course['slug']), 'label' => 'Start learning']) ?>
