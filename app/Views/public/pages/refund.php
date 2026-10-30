<?php
$intro = 'What happens to your payment if you cancel, if you miss classes, or if we change or cancel a cohort.';
$summary = [
  'Cancel <strong>before</strong> your cohort starts and you get most or all of your fee back, depending on how early you tell us.',
  'If <strong>we</strong> cancel a cohort, you get a full refund or can move to another cohort.',
  'If we reschedule, you keep your seat. You can ask for a refund if the new dates do not work for you.',
  'Paid twice or charged but not confirmed? We fix it and refund any duplicate.',
  'To ask, email us with your enrolment number.',
];
$sections = [];

ob_start(); ?>
<p>This policy covers fees paid for live cohorts on this platform. Career Accelerator services are covered in the section &ldquo;Career Accelerator services&rdquo; below.</p>
<p>A few words we use: your <strong>enrolment number</strong> is the reference starting with <em>AFB-</em> on your dashboard. A <strong>cohort start date</strong> is the date and time shown on the cohort page.</p>
<?php $sections['scope'] = ['What this policy covers', ob_get_clean()];

ob_start(); ?>
<div class="table-wrap"><table>
  <thead><tr><th>When you tell us</th><th>What you get back</th></tr></thead>
  <tbody>
    <tr><td><?= $ph('7 or more days') ?> before the cohort starts</td><td><?= $ph('Full refund') ?></td></tr>
    <tr><td><?= $ph('1 to 6 days') ?> before the cohort starts</td><td><?= $ph('50% of the fee, or a free move to a later cohort') ?></td></tr>
    <tr><td>After the cohort has started</td><td><?= $ph('No refund, unless you were unable to attend because of a documented emergency, in which case we will consider a move to a later cohort') ?></td></tr>
  </tbody>
</table></div>
<p>We measure the time from when we receive your request to the cohort start date shown on the cohort page. To cancel, follow &ldquo;How to ask for a refund&rdquo; below.</p>
<?php $sections['you-cancel'] = ['If you cancel', ob_get_clean()];

ob_start(); ?>
<p>Missing a class does not on its own entitle you to a refund. Tell your instructor as early as you can, and ask whether the material or a recording is available. Where a class is missed because <em>we</em> had a technical or scheduling problem, we will arrange a make-up session or equivalent access.</p>
<?php $sections['missed'] = ['If you miss classes', ob_get_clean()];

ob_start(); ?>
<ul>
  <li><strong>We cancel a cohort:</strong> you choose between a <strong>full refund</strong> and <strong>moving your seat</strong> to another cohort of the same course, at no extra cost.</li>
  <li><strong>We postpone a cohort or reschedule a class:</strong> you keep your seat, and your dashboard shows the new dates. If the new dates genuinely do not work for you, you may ask for a full refund within <?= $ph('7 days') ?> of being told.</li>
  <li><strong>We cancel a single class:</strong> we will reschedule it. No refund is due unless it cannot be rescheduled.</li>
</ul>
<?php $sections['we-change'] = ['If we cancel or change something', ob_get_clean()];

ob_start(); ?>
<ul>
  <li><strong>Charged twice:</strong> we refund the duplicate in full.</li>
  <li><strong>Charged but your seat is not confirmed:</strong> we check with the payment provider. If the payment is genuine we confirm your seat. If the seat was released and no seats remain, we refund you in full or offer a seat in another cohort.</li>
  <li>Send us the payment reference and your enrolment number. Please do not pay again while we investigate.</li>
</ul>
<?php $sections['payment-problems'] = ['Payment problems', ob_get_clean()];

ob_start(); ?>
<ol>
  <li>Email <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a> from the address on your account, or use the <a href="<?= e(url('/contact')) ?>">contact form</a>.</li>
  <li>Include your <strong>full name</strong>, <strong>enrolment number</strong> and the <strong>reason</strong>.</li>
  <li>We will confirm what you are due within <?= $ph('3 working days') ?>.</li>
</ol>
<p>Approved refunds are paid back to the original payment method, usually within <?= $ph('5 to 10 working days') ?>, although your bank may take longer. <?= $ph('Payment-provider fees are not refundable.') ?></p>
<?php $sections['how'] = ['How to ask for a refund', ob_get_clean()];

ob_start(); ?>
<p>Career Accelerator consultations are free. For any paid career service, the scope, price and refund terms are agreed with you in writing before you pay. <?= $todo('Confirm refund terms for paid career services.') ?> Services that have already been delivered, such as a completed CV review, are not refundable.</p>
<?php $sections['career'] = ['Career Accelerator services', ob_get_clean()];

ob_start(); ?>
<p>Please contact us before disputing a payment with your bank, so we can sort it out quickly. Disputes raised without contacting us first may delay your refund.</p>
<?php $sections['disputes'] = ['Disputing a payment with your bank', ob_get_clean()];

ob_start(); ?>
<p>Nothing in this policy limits any rights you have under the law. Our <a href="<?= e(url('/terms')) ?>">terms of use</a> also apply. If something here is unclear, <a href="<?= e(url('/contact')) ?>">ask us</a> before you pay.</p>
<?php $sections['rights'] = ['Your legal rights', ob_get_clean()];

echo App\Core\View::partial('public/pages/_legal', compact('title', 'updated', 'draft', 'intro', 'summary', 'sections', 'contact'));
