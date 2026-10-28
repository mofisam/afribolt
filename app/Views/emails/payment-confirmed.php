<p>Hi <?= e($student['first_name']) ?>,</p>
<p>We received your payment of <strong><?= e(money($payment['amount'], $payment['currency'])) ?></strong> (ref <?= e($payment['internal_reference']) ?>). Your seat is confirmed.</p>
<p>Enrolment number: <strong><?= e($enrolment['enrolment_number']) ?></strong></p>
<p><a href="<?= e(url('/student/dashboard')) ?>" style="color:#1B8CCB;font-weight:bold">Open my dashboard</a></p>
