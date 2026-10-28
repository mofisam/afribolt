<?php
use App\Core\Session;
$styles = ['success' => 'bg-green-50 border-green-600 text-green-900', 'error' => 'bg-red-50 border-red-600 text-red-900', 'info' => 'bg-bolt-tint border-bolt text-ink'];
foreach (Session::flashes() as $type => $messages):
    foreach ($messages as $m): ?>
<div role="alert" class="mb-4 flex items-start justify-between gap-4 border-l-4 px-4 py-3 rounded <?= $styles[$type] ?? $styles['info'] ?>">
  <p><?= e($m) ?></p>
  <button type="button" data-dismiss class="font-bold" aria-label="Dismiss">&times;</button>
</div>
<?php endforeach; endforeach; ?>
