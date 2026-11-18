<!--preheader:<?= e($title) ?>-->
<p style="margin:0 0 6px;font-size:12px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase;color:#C2410C"><?= $isAnnouncement ? 'Announcement' : 'New notification' ?></p>
<h1 style="margin:0 0 14px;font-size:26px;line-height:1.3;color:#0A2540"><?= e($title) ?></h1>
<p style="margin:0 0 16px">Hi <?= e($name) ?>, <?= $isAnnouncement ? 'you have one announcement from Afribolt.' : 'you have a new notification from Afribolt.' ?></p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 8px;background:#F1F7FB;border-left:4px solid #1B8CCB;border-radius:4px"><tr><td style="padding:14px 16px;font-size:16px;line-height:1.6;color:#0A2540"><?= nl2br(e($message)) ?></td></tr></table>
<?= App\Core\View::partial('emails/_button', ['url' => $link, 'label' => $isAnnouncement ? 'Read the announcement' : 'View notification']) ?>
<p style="margin:0;font-size:13px;color:#475569">Prefer not to get these emails? You can turn them off any time in your <a href="<?= e(url('/student/profile')) ?>" style="color:#0F6BA0">profile</a>. You will still see everything in the bell on your dashboard.</p>
