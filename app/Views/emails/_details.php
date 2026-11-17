<?php /** Key facts box. Var: $rows = ['Label' => 'Value', ...] (values are escaped) */ ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0;background:#F1F7FB;border-left:4px solid #1B8CCB;border-radius:4px">
  <?php foreach ($rows as $label => $value): ?>
    <tr>
      <td style="padding:10px 16px 2px;font-size:12px;font-weight:bold;letter-spacing:.04em;text-transform:uppercase;color:#475569"><?= e($label) ?></td>
    </tr>
    <tr>
      <td style="padding:0 16px 10px;font-size:16px;font-weight:bold;color:#0A2540"><?= e($value) ?></td>
    </tr>
  <?php endforeach; ?>
</table>
