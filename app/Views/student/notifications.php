<div class="flex items-center justify-between"><h1 class="text-3xl font-extrabold">Notifications</h1>
  <form method="post" action="<?= e(url('/student/notifications/read')) ?>"><?= csrf_field() ?><button class="btn btn-ghost" type="submit">Mark all as read</button></form></div>
<?php if (!$items): ?><p class="mt-6 text-slate-600">Nothing yet.</p><?php endif; ?>
<ul class="mt-6 space-y-3">
  <?php foreach ($items as $n): ?>
    <li class="bg-white border rounded-lg p-4 <?= $n['read_at'] ? '' : 'border-l-4 border-l-bolt' ?>">
      <p class="font-bold"><?= e($n['title']) ?></p><p><?= e($n['message']) ?></p>
      <p class="text-xs text-slate-500 mt-1"><?= e(local_date($n['created_at'])) ?></p>
    </li>
  <?php endforeach; ?>
</ul>
