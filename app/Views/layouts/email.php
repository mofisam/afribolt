<?php
/**
 * Shared email layout. Table-based and inline-styled so it works in Gmail, Outlook and phone mail apps.
 * A template can set the inbox preview line with <!--preheader:Your text--> anywhere in its body.
 */
$pre = trim(preg_match('/<!--preheader:(.*?)-->/s', $content, $pm) ? $pm[1] : ($subject ?? ''));
$content = preg_replace('/<!--preheader:.*?-->/s', '', $content);
$site = rtrim((string) url('/'), '/');
$to = trim((string) ($recipient ?? ''));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light only">
<meta name="supported-color-schemes" content="light only">
<title><?= e($subject ?? 'Afribolt') ?></title>
<style>
  body { margin: 0; padding: 0; background: #EAF5FC; }
  a { color: #0F6BA0; }
  @media (max-width: 620px) {
    .container { width: 100% !important; border-radius: 0 !important; }
    .px { padding-left: 20px !important; padding-right: 20px !important; }
    .btn, .btn a { display: block !important; width: 100% !important; box-sizing: border-box; text-align: center !important; }
    h1 { font-size: 22px !important; }
  }
</style>
</head>
<body style="margin:0;padding:0;background:#EAF5FC;font-family:Arial,Helvetica,sans-serif;color:#0A2540;-webkit-text-size-adjust:100%">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all"><?= e($pre) ?>&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;&#8199;&#847;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EAF5FC"><tr><td align="center" style="padding:24px 12px">
  <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:10px;overflow:hidden">
    <tr><td class="px" style="background:#0A2540;padding:22px 32px">
      <a href="<?= e($site) ?>" style="text-decoration:none;font-size:26px;font-weight:bold;letter-spacing:-0.5px;color:#ffffff">Afri<span style="color:#FFC800">bolt</span></a>
    </td></tr>
    <tr><td style="height:4px;line-height:4px;font-size:0;background:#F26B21">&nbsp;</td></tr>
    <tr><td class="px" style="padding:32px;font-size:16px;line-height:1.65;color:#0A2540"><?= $content ?></td></tr>
    <tr><td class="px" style="padding:0 32px"><div style="border-top:1px solid #e2e8f0"></div></td></tr>
    <tr><td class="px" style="padding:20px 32px 28px;font-size:13px;line-height:1.6;color:#475569">
      <p style="margin:0 0 8px"><strong style="color:#0A2540">Afribolt</strong> &middot; Learn it. Build it. Get hired.</p>
      <p style="margin:0 0 8px">
        <a href="<?= e($site) ?>/courses" style="color:#0F6BA0">Courses</a> &nbsp;&middot;&nbsp;
        <a href="<?= e($site) ?>/jobs" style="color:#0F6BA0">Jobs</a> &nbsp;&middot;&nbsp;
        <a href="<?= e($site) ?>/career-accelerator" style="color:#0F6BA0">Career Launch</a> &nbsp;&middot;&nbsp;
        <a href="<?= e($site) ?>/contact" style="color:#0F6BA0">Contact us</a>
      </p>
      <p style="margin:0;color:#64748b">This is an automated message, so please do not reply to it. Need help? <a href="<?= e($site) ?>/contact" style="color:#0F6BA0">Contact us</a>.<?= $to !== '' ? ' We sent this to ' . e($to) . ' because of activity on your Afribolt account.' : '' ?></p>
    </td></tr>
  </table>
  <p style="margin:16px 0 0;font-size:12px;color:#64748b;font-family:Arial,Helvetica,sans-serif">&copy; <?= date('Y') ?> Afribolt Technologies. &hellip;Dream big, Achieve big</p>
</td></tr></table>
</body>
</html>
