<?php
$intro = 'What personal information we collect, why we collect it, who sees it, and the choices you have.';
$summary = [
  'We collect what we need to run your account, your enrolment and your live classes.',
  'We <strong>do not store your card details</strong>. Payments are handled by our payment provider.',
  'We <strong>do not sell your personal data</strong>.',
  'Instructors only see the students in the cohorts they teach.',
  'You can ask to see, correct or delete your data at any time.',
];
$sections = [];

ob_start(); ?>
<p>AfriBolt Technologies (&ldquo;we&rdquo;, &ldquo;us&rdquo;) runs this website and learning platform. For the purposes of data protection law, we are the &ldquo;data controller&rdquo; of the personal information described here.</p>
<p>You can reach us at <a href="mailto:<?= e($contact) ?>"><?= e($contact) ?></a> or through our <a href="<?= e(url('/contact')) ?>">contact page</a>.</p>
<?php $sections['who'] = ['Who we are', ob_get_clean()];

ob_start(); ?>
<div class="table-wrap"><table>
  <thead><tr><th>What</th><th>Examples</th><th>Why</th></tr></thead>
  <tbody>
    <tr><td>Account details</td><td>Name, email, phone (optional), password (stored only as a one-way hash)</td><td>To create and secure your account and contact you</td></tr>
    <tr><td>Enrolment records</td><td>Cohorts you register for, status, fees, notes added by our team</td><td>To manage your seat and your learning</td></tr>
    <tr><td>Payment records</td><td>Amount, currency, reference, status, payment method type, date</td><td>To confirm your seat, keep financial records and handle refunds. <strong>We never receive or store full card numbers.</strong></td></tr>
    <tr><td>Class activity</td><td>When you used the Join Class button, attendance marked by instructors</td><td>To control access to live classes and track attendance</td></tr>
    <tr><td>Messages and notifications</td><td>Emails and in-app notifications we send you, and messages you send us through the contact form</td><td>To keep you informed and answer questions</td></tr>
    <tr><td>Career Accelerator enquiries</td><td>Your message and any details you share when booking a consultation</td><td>To respond and provide career support you ask for</td></tr>
    <tr><td>Technical data</td><td>IP address, browser type, security and error logs, session cookie</td><td>To keep the platform secure, prevent abuse and fix problems</td></tr>
  </tbody>
</table></div>
<h3>Cookies and similar technologies</h3>
<p>We use only the cookies the platform needs to work: a session cookie that keeps you signed in, and a security token that protects forms from forgery. We do not use advertising cookies. Our pages load fonts from Google Fonts, which means your browser makes a request to Google when a page loads. <?= $todo('Update this paragraph if you add analytics or other third-party tools.') ?></p>
<?php $sections['collect'] = ['What we collect', ob_get_clean()];

ob_start(); ?>
<ul>
  <li><strong>To provide the service (contract):</strong> create your account, register you for cohorts, take payment, run live classes and send class updates.</li>
  <li><strong>To keep things safe and working (legitimate interests):</strong> prevent fraud and abuse, secure accounts, keep audit records of important changes, fix bugs.</li>
  <li><strong>To meet legal obligations:</strong> keep financial and tax records, respond to lawful requests.</li>
  <li><strong>With your consent:</strong> any optional marketing messages. You can withdraw consent at any time.</li>
</ul>
<p>Under the Nigeria Data Protection Act 2023 we must have a lawful basis for each use of your data. The list above sets out the bases we rely on.</p>
<?php $sections['use'] = ['How we use your information', ob_get_clean()];

ob_start(); ?>
<p>We share personal information only where needed to run the service:</p>
<ul>
  <li><strong>Payment provider.</strong> Our payment partner processes your payment and tells us whether it succeeded. They have their own privacy policy.</li>
  <li><strong>Meeting platforms.</strong> Live classes happen on Google Meet, Zoom or Microsoft Teams. When you join, that provider processes your data under its own terms.</li>
  <li><strong>Email and hosting providers.</strong> Services that send our emails and host our platform, bound to protect your data.</li>
  <li><strong>Instructors.</strong> Instructors see the name, email and attendance of students in the cohorts they teach, and nothing about other cohorts or your payments.</li>
  <li><strong>Authorities.</strong> If the law requires us to, or to protect people&rsquo;s rights and safety.</li>
</ul>
<p>We do not sell your personal information or share it for third-party advertising.</p>
<?php $sections['share'] = ['Who we share it with', ob_get_clean()];

ob_start(); ?>
<p>Some of our providers store or process data outside Nigeria. Where that happens, we take steps to make sure your data stays protected, as required by data protection law. <?= $todo('List hosting location and any transfer safeguards once confirmed.') ?></p>
<?php $sections['transfers'] = ['Transfers outside Nigeria', ob_get_clean()];

ob_start(); ?>
<p>We keep personal information only as long as we need it for the purposes above.</p>
<ul>
  <li>Account and enrolment records: while your account is open, then up to <?= $ph('2 years') ?> after your last activity.</li>
  <li>Payment and financial records: <?= $ph('6 years') ?> to meet accounting and tax obligations.</li>
  <li>Contact-form messages: up to <?= $ph('12 months') ?>.</li>
  <li>Security and error logs: up to <?= $ph('12 months') ?>.</li>
</ul>
<?php $sections['keep'] = ['How long we keep it', ob_get_clean()];

ob_start(); ?>
<p>We protect your data with measures including encrypted connections (HTTPS), hashed passwords, access limited by role, protected meeting links released only to eligible students, and records of important admin actions. No system is perfectly secure, so please use a strong, unique password and tell us straight away if you suspect your account has been misused.</p>
<?php $sections['security'] = ['How we protect it', ob_get_clean()];

ob_start(); ?>
<p>You have the right to:</p>
<ul>
  <li>ask for a copy of the personal data we hold about you;</li>
  <li>ask us to correct data that is wrong or incomplete;</li>
  <li>ask us to delete your data (we may need to keep some records, such as payments, to meet legal duties);</li>
  <li>object to or ask us to restrict certain uses;</li>
  <li>receive your data in a portable format where it applies;</li>
  <li>withdraw consent you gave us.</li>
</ul>
<p>To use any of these rights, <a href="<?= e(url('/contact')) ?>">contact us</a> from the email on your account. We will respond within <?= $ph('30 days') ?>. If you are unhappy with how we handle your data, you can complain to the Nigeria Data Protection Commission.</p>
<?php $sections['rights'] = ['Your rights', ob_get_clean()];

ob_start(); ?>
<p>Our programmes are for adults aged <?= $ph('18') ?> and over. We do not knowingly collect data from children. If you believe a child has given us personal information, contact us and we will delete it.</p>
<?php $sections['children'] = ['Children', ob_get_clean()];

ob_start(); ?>
<p>We may update this policy as the platform changes. We will change the &ldquo;last updated&rdquo; date above, and tell you by email or notice on the site if the change is significant.</p>
<?php $sections['changes'] = ['Changes to this policy', ob_get_clean()];

echo App\Core\View::partial('public/pages/_legal', compact('title', 'updated', 'draft', 'intro', 'summary', 'sections', 'contact'));
