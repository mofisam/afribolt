<h1 class="text-3xl font-extrabold">Payment gateways</h1>
<div class="mt-4 bg-bolt-tint rounded-lg p-4 text-sm max-w-3xl">
  <p><strong>Webhook URL</strong> to give your payment provider: <code class="break-all"><?= e($webhookUrl) ?></code></p>
  <p class="mt-1">API secret and webhook secret live in the server's <code>.env</code> file (<code>PAYSTACK_SECRET_KEY</code>), never in the database or this screen.</p>
</div>
<div class="mt-5 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Gateway</th><th class="p-3">Code</th><th class="p-3">Currencies</th><th class="p-3">Enabled</th><th class="p-3">Mode</th><th></th></tr></thead><tbody>
  <?php foreach ($gateways as $g): ?>
    <tr class="border-t"><td class="p-3"><?= e($g['display_name']) ?></td><td class="p-3"><?= e($g['provider_code']) ?></td>
      <td class="p-3"><?= e(implode(', ', (array) json_decode((string) $g['supported_currencies'], true))) ?></td>
      <td class="p-3"><span class="badge <?= $g['is_enabled'] ? 'badge-open' : 'badge-closed' ?>"><?= $g['is_enabled'] ? 'Enabled' : 'Disabled' ?></span></td>
      <td class="p-3"><?= $g['is_test_mode'] ? 'Test' : 'Live' ?></td>
      <td class="p-3"><a class="underline text-bolt-dark" href="<?= e(url('/admin/gateways/' . $g['id'] . '/edit')) ?>">Edit</a></td></tr>
  <?php endforeach; if (!$gateways): ?><tr><td class="p-4 text-slate-500" colspan="6">No gateway row yet. Run <code>php database/seed.php</code>.</td></tr><?php endif; ?></tbody></table></div>
<h2 class="mt-8 text-xl font-bold">Recent webhook events</h2>
<div class="mt-3 bg-white border rounded-lg overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Received</th><th class="p-3">Event</th><th class="p-3">Event ID</th><th class="p-3">Status</th><th class="p-3">Error</th></tr></thead><tbody>
  <?php foreach ($events as $w): ?>
    <tr class="border-t"><td class="p-3"><?= e(local_date($w['received_at'], null, 'j M, g:i:s A')) ?></td><td class="p-3"><?= e($w['event_type']) ?></td><td class="p-3 break-all"><?= e($w['provider_event_id']) ?></td>
      <td class="p-3"><span class="badge <?= $w['processing_status'] === 'processed' ? 'badge-open' : ($w['processing_status'] === 'failed' ? 'badge-closed' : '') ?>"><?= e($w['processing_status']) ?></span></td><td class="p-3"><?= e($w['processing_error'] ?? '') ?></td></tr>
  <?php endforeach; if (!$events): ?><tr><td class="p-4 text-slate-500" colspan="5">No webhook events received yet.</td></tr><?php endif; ?></tbody></table></div>
