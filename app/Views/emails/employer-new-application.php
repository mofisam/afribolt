<!--preheader:<?= e($applicant) ?> applied for <?= e($jobTitle) ?>.-->
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540">New applicant</h1>
<p style="margin:0 0 16px">Hi <?= e($company) ?> team, <strong><?= e($applicant) ?></strong> just applied for <strong><?= e($jobTitle) ?></strong> on Afribolt.</p>
<?= App\Core\View::partial('emails/_button', ['url' => $link, 'label' => 'View applicants']) ?>
<p style="margin:0;font-size:14px;color:#475569">You can read their note, download their CV and update their status from your employer dashboard.</p>
