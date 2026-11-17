<?php /** Bulletproof button. Vars: $url, $label, optional $color (default blue, passes contrast with white text). */ $bg = $color ?? '#0F6BA0'; ?>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0"><tr>
  <td class="btn" align="center" bgcolor="<?= e($bg) ?>" style="border-radius:6px;background:<?= e($bg) ?>">
    <a href="<?= e($url) ?>" style="display:inline-block;padding:14px 30px;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;line-height:1.2;color:#ffffff;text-decoration:none;border-radius:6px"><?= e($label) ?></a>
  </td>
</tr></table>
