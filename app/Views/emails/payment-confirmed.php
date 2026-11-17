<!--preheader:Payment received. Your seat is confirmed.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">Payment received</h1>
<p style="margin:0 0 16px">Hi <?= e($student['first_name']) ?>, thank you. Your seat is confirmed.</p>
<?= App\Core\View::partial('emails/_details', ['rows' => [
    'Amount paid' => money($payment['amount'], $payment['currency']),
    'Payment reference' => $payment['internal_reference'],
    'Enrolment number' => $enrolment['enrolment_number'],
]]) ?>
<?= App\Core\View::partial('emails/_button', ['url' => url('/student/dashboard'), 'label' => 'Open my dashboard']) ?>
<p style="margin:0;font-size:14px;color:#475569">Keep this email as your receipt.</p>
