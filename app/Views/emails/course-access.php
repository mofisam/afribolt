<p>Hi <?= e($student['first_name']) ?>,</p>
<p>You now have full access to <strong><?= e($course['title']) ?></strong>.<?= $amount > 0 ? ' We received your payment of <strong>' . e(money($amount, $course['price_currency'])) . '</strong>.' : '' ?></p>
<p>Learn at your own pace, on any device. Your progress is saved automatically.</p>
<p><a href="<?= e(url('/learn/' . $course['slug'])) ?>" style="background:#1B8CCB;color:#fff;padding:10px 18px;border-radius:4px;text-decoration:none;font-weight:bold">Start learning</a></p>
