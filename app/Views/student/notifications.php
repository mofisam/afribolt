<?php
/** @var array $items @var array $announcements @var ?string $seenAt @var int $unread @var string $filter */
$icons = [
  'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
  'megaphone' => '<path d="M3 11v2a1 1 0 0 0 1 1h2l5 4V6L6 10H4a1 1 0 0 0-1 1z"/><path d="M15.5 8.5a5 5 0 0 1 0 7"/><path d="M18.5 5.5a9 9 0 0 1 0 13"/>',
  'award' => '<circle cx="12" cy="8" r="6"/><path d="M15.48 12.89L17 22l-5-3-5 3 1.52-9.11"/>',
  'card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
  'book' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
  'check' => '<path d="M20 6L9 17l-5-5"/>',
];
// type => [icon, colour class]
$look = function (string $type) {
    if (str_starts_with($type, 'announcement')) { return ['megaphone', 'nt-warn']; }
    if ($type === 'course_completed') { return ['award', 'nt-ok']; }
    if (str_starts_with($type, 'payment')) { return ['card', 'nt-ok']; }
    if (str_starts_with($type, 'enrolment') || str_starts_with($type, 'course_access')) { return ['book', '']; }
    return ['bell', ''];
};
$svg = fn(string $k) => '<svg viewBox="0 0 24 24" aria-hidden="true">' . $icons[$k] . '</svg>';
$ago = function (string $utc): string {
    $t = strtotime($utc . ' UTC'); $d = time() - $t;
    if ($d < 60) { return 'Just now'; }
    if ($d < 3600) { $m = (int) floor($d / 60); return $m . ' min' . ($m > 1 ? 's' : '') . ' ago'; }
    if ($d < 86400) { $h = (int) floor($d / 3600); return $h . ' hour' . ($h > 1 ? 's' : '') . ' ago'; }
    return local_date($utc, null, 'j M Y, g:i A');
};
// group by local day
$today = local_date(utc_now(), null, 'Y-m-d');
$yesterday = date('Y-m-d', strtotime($today . ' -1 day'));
$groups = [];
foreach ($items as $n) {
    $day = local_date($n['created_at'], null, 'Y-m-d');
    $label = $day === $today ? 'Today' : ($day === $yesterday ? 'Yesterday' : (strtotime($today) - strtotime($day) < 7 * 86400 ? 'This week' : 'Earlier'));
    $groups[$label][] = $n;
}
$showAnn = $filter === 'all' && !empty($announcements);
?>
<div class="max-w-3xl mx-auto">
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="text-3xl font-extrabold">Notifications</h1>
      <p class="text-slate-600" role="status"><?= $unread ? '<strong>' . (int) $unread . '</strong> unread' : 'You are all caught up.' ?></p>
    </div>
    <?php if ($unread): ?>
      <form method="post" action="<?= e(url('/student/notifications/read')) ?>"><?= csrf_field() ?><button class="btn btn-ghost !py-2" type="submit">Mark all as read</button></form>
    <?php endif; ?>
  </div>

  <nav class="nt-tabs" aria-label="Filter notifications">
    <a href="<?= e(url('/student/notifications')) ?>" <?= $filter === 'all' ? 'aria-current="page"' : '' ?>>All</a>
    <a href="<?= e(url('/student/notifications?filter=unread')) ?>" <?= $filter === 'unread' ? 'aria-current="page"' : '' ?>>Unread<?php if ($unread): ?> <span class="adm-badge"><?= (int) $unread ?></span><?php endif; ?></a>
  </nav>

  <?php if ($showAnn): ?>
    <section aria-labelledby="ann-h" class="mt-6">
      <h2 id="ann-h" class="nt-group">Announcements</h2>
      <ul class="space-y-3">
        <?php foreach ($announcements as $a):
          $when = $a['publish_at'] ?: $a['created_at']; $isNew = $seenAt !== null && $when > $seenAt;
          $long = mb_strlen($a['body']) > 220; ?>
          <li class="nt-card <?= $isNew ? 'nt-unread' : '' ?>">
            <span class="nt-ico nt-warn"><?= $svg('megaphone') ?></span>
            <div class="min-w-0 flex-1">
              <p class="font-bold"><?= e($a['title']) ?><?php if ($isNew): ?> <span class="badge">New</span><?php endif; ?></p>
              <?php if ($long): ?>
                <details class="nt-more"><summary><span class="nt-preview"><?= e(mb_strimwidth($a['body'], 0, 220, '…')) ?></span><span class="nt-link">Read more</span></summary>
                  <div class="whitespace-pre-line mt-1"><?= e($a['body']) ?></div></details>
              <?php else: ?><p class="whitespace-pre-line text-slate-700"><?= e($a['body']) ?></p><?php endif; ?>
              <p class="nt-time" title="<?= e(local_date($when)) ?>"><?= e($ago($when)) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endif; ?>

  <?php if (!$items && !$showAnn): ?>
    <div class="nt-empty">
      <span class="nt-ico big"><?= $svg($filter === 'unread' ? 'check' : 'bell') ?></span>
      <p class="mt-3 font-bold text-lg"><?= $filter === 'unread' ? 'No unread notifications' : 'No notifications yet' ?></p>
      <p class="text-slate-600">
        <?= $filter === 'unread' ? 'You have read everything.' : 'Updates about your courses, payments and announcements will show up here.' ?></p>
      <?php if ($filter === 'unread'): ?><a class="btn btn-ghost mt-4" href="<?= e(url('/student/notifications')) ?>">See all notifications</a><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php foreach ($groups as $label => $list): ?>
    <section class="mt-6" aria-label="<?= e($label) ?>">
      <h2 class="nt-group"><?= e($label) ?></h2>
      <ul class="space-y-3">
        <?php foreach ($list as $n): [$ic, $cls] = $look((string) $n['notification_type']); $isUnread = empty($n['read_at']); $go = !empty($n['action_url']); ?>
          <li class="nt-card <?= $isUnread ? 'nt-unread' : '' ?>">
            <span class="nt-ico <?= $cls ?>"><?= $svg($ic) ?></span>
            <div class="min-w-0 flex-1">
              <p class="font-bold"><?= e($n['title']) ?><?php if ($isUnread): ?> <span class="badge">New</span><?php endif; ?></p>
              <p class="text-slate-700"><?= e($n['message']) ?></p>
              <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1">
                <span class="nt-time" title="<?= e(local_date($n['created_at'])) ?>"><?= e($ago($n['created_at'])) ?></span>
                <?php if ($go): ?>
                  <form method="post" action="<?= e(url('/student/notifications/' . (int) $n['id'] . '/open')) ?>" class="inline"><?= csrf_field() ?><button class="nt-link" type="submit">View<span class="sr-only"> <?= e($n['title']) ?></span> &rsaquo;</button></form>
                <?php endif; ?>
                <?php if ($isUnread): ?>
                  <form method="post" action="<?= e(url('/student/notifications/' . (int) $n['id'] . '/read')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><button class="nt-link nt-muted" type="submit">Mark as read<span class="sr-only">: <?= e($n['title']) ?></span></button></form>
                <?php endif; ?>
              </div>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    </section>
  <?php endforeach; ?>

  <?php if (count($items) >= 50): ?><p class="mt-6 text-center text-sm text-slate-500">Showing your latest 50 notifications.</p><?php endif; ?>
</div>
