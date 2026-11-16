<?php
/** Notification bell. Optional: $count (already computed), $onDark (white icon for dark headers). */
use App\Core\Auth;
$bellCount = $count ?? null;
if ($bellCount === null) {
    $bellCount = 0;
    try { $bellCount = (new App\Services\NotificationService())->badgeCount((int) Auth::id('student')); } catch (\Throwable $e) {}
}
$bellCount = (int) $bellCount;
?>
<a class="bell<?= !empty($onDark) ? ' bell-dark' : '' ?>" href="<?= e(url('/student/notifications')) ?>" title="Notifications"
   aria-label="Notifications<?= $bellCount ? ', ' . $bellCount . ' new' : ', none new' ?>">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
  <?php if ($bellCount): ?><span class="bell-dot" aria-hidden="true"><?= $bellCount > 9 ? '9+' : $bellCount ?></span><?php endif; ?>
</a>
