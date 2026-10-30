<?php
$intro = 'The rules for using AfriBolt Technologies and joining our live online programmes. Please read them before you register.';
$summary = [
  'Your seat is confirmed only after your payment is verified.',
  'Class links are for you alone. Do not share them.',
  'Be respectful in class. We can remove people who are not.',
  'We teach and support you, but we cannot guarantee a job or specific result.',
  'Refunds are covered in our <a href="' . e(url('/refund-policy')) . '">refund and cancellation policy</a>.',
];
$sections = [];

ob_start(); ?>
<p>By creating an account, registering for a cohort or using this site, you agree to these terms, our <a href="<?= e(url('/privacy')) ?>">privacy policy</a> and our <a href="<?= e(url('/refund-policy')) ?>">refund and cancellation policy</a>. If you do not agree, please do not use the platform.</p>
<?php $sections['accept'] = ['Accepting these terms', ob_get_clean()];

ob_start(); ?>
<ul>
  <li>You must be at least <?= $ph('18') ?> years old and give accurate details.</li>
  <li>Keep your password private. You are responsible for activity on your account.</li>
  <li>One account per person. Tell us right away if you think someone else has access.</li>
</ul>
<?php $sections['account'] = ['Your account', ob_get_clean()];

ob_start(); ?>
<p>A cohort is a group of students taking a programme together on fixed dates.</p>
<ul>
  <li>Each cohort has a registration window, a limited number of seats and sometimes a waitlist. These are shown on the cohort page.</li>
  <li>Registering reserves a seat for a short time while you pay. If payment is not completed within <?= $ph('60 minutes') ?>, the seat is released.</li>
  <li><strong>Your seat is confirmed only when your payment has been verified.</strong> Returning from the payment page is not enough on its own.</li>
  <li>If you join a waitlist and a seat opens, you will have a limited time to pay.</li>
  <li>We may cancel or postpone a cohort, for example if too few students register. See the refund policy for what happens then.</li>
</ul>
<?php $sections['cohorts'] = ['Cohorts, registration and seats', ob_get_clean()];

ob_start(); ?>
<ul>
  <li>Fees are shown on each cohort page in the stated currency and are payable in full at registration unless the page says otherwise.</li>
  <li>Payments are processed by a third-party payment provider. We do not see or store your card details.</li>
  <li>The fee shown when you register is the fee you pay for that seat, even if we change it later. <?= $todo('Confirm.') ?></li>
  <li>Bank or provider charges, if any, are shown at checkout.</li>
</ul>
<?php $sections['fees'] = ['Fees and payment', ob_get_clean()];

ob_start(); ?>
<ul>
  <li>Live classes are held on Google Meet, Zoom or Microsoft Teams. The <strong>Join Class</strong> button on your dashboard becomes available shortly before each class, for confirmed students only.</li>
  <li>Meeting links and passcodes are personal to you. Do not share, post or forward them.</li>
  <li>Class dates and times can change. We will notify you, and your dashboard updates automatically.</li>
  <li>Recordings: <?= $ph('available only for some programmes, as stated on the course page') ?>. Do not record or capture classes without permission.</li>
</ul>
<?php $sections['classes'] = ['Live classes and joining', ob_get_clean()];

ob_start(); ?>
<p>Be respectful to instructors and other students. You must not:</p>
<ul>
  <li>harass, threaten, discriminate against or disrupt others;</li>
  <li>share your account, meeting links or course materials with people who have not paid;</li>
  <li>use the platform to break the law, spread malware, or try to access data or accounts that are not yours;</li>
  <li>submit false information or abuse the payment system.</li>
</ul>
<p>We may warn, suspend or remove anyone who breaks these rules. Whether a refund is due in that case is covered by the refund policy.</p>
<?php $sections['conduct'] = ['Behaviour and acceptable use', ob_get_clean()];

ob_start(); ?>
<p>Course content, slides, recordings, logos and the platform itself belong to AfriBolt Technologies or our instructors and licensors. You get a personal, non-transferable licence to use materials for your own learning. You may not copy, sell or distribute them without written permission.</p>
<?php $sections['ip'] = ['Course materials and intellectual property', ob_get_clean()];

ob_start(); ?>
<p>We design our programmes and Career Accelerator support to help you build skills and compete well for opportunities. <strong>We do not guarantee any job, internship, salary, grade or other outcome.</strong> Results depend on many things, including your own effort and decisions made by employers.</p>
<p>Career Accelerator services (such as CV reviews, coaching and reference-letter support) are provided on the scope agreed with you, and you are responsible for the accuracy of what you tell us.</p>
<?php $sections['results'] = ['Results and the Career Accelerator', ob_get_clean()];

ob_start(); ?>
<p>We may suspend or close an account that breaks these terms or puts others at risk. You can ask us to close your account at any time, subject to the refund policy and any records we must keep by law.</p>
<?php $sections['termination'] = ['Suspension and closing accounts', ob_get_clean()];

ob_start(); ?>
<p>We work hard to keep the platform available, but we cannot promise it will always be uninterrupted or error-free, for example during maintenance or problems with our providers or your internet connection.</p>
<p>To the extent the law allows, we are not liable for indirect or consequential losses, and our total liability to you for any claim is limited to the fees you paid for the programme the claim relates to. Nothing in these terms limits liability that cannot be limited by law.</p>
<?php $sections['liability'] = ['Availability and liability', ob_get_clean()];

ob_start(); ?>
<p>We may update these terms. We will change the date at the top and tell you about important changes. If you keep using the platform after a change takes effect, you accept the new terms. Changes do not affect a seat you have already paid for in a way that makes your position worse.</p>
<?php $sections['changes'] = ['Changes to these terms', ob_get_clean()];

ob_start(); ?>
<p>These terms are governed by the laws of <?= $ph('the Federal Republic of Nigeria') ?>, and disputes will be handled by the courts of <?= $ph('Lagos State') ?>, unless the law says otherwise. We encourage you to <a href="<?= e(url('/contact')) ?>">talk to us</a> first. Most problems can be solved quickly.</p>
<?php $sections['law'] = ['Governing law and contact', ob_get_clean()];

echo App\Core\View::partial('public/pages/_legal', compact('title', 'updated', 'draft', 'intro', 'summary', 'sections', 'contact'));
