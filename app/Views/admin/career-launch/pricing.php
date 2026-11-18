<?php /** @var array $p current pricing @var string $tz @var string $endsInput */ ?>
<div class="max-w-xl">
  <a class="text-sm underline text-bolt-dark" href="<?= e(url('/admin/career-launch')) ?>">&larr; Career Launch registrations</a>
  <h1 class="mt-1 text-3xl font-extrabold">Price &amp; offer timer</h1>

  <div class="mt-4 rounded-lg border p-4 <?= $p['active'] ? 'bg-green-50 border-green-300' : 'bg-amber-50 border-amber-300' ?>" role="status">
    <?php if ($p['active']): ?>
      <p class="font-bold text-green-800">Founding offer is LIVE at <?= e(\App\Services\CareerLaunchPricing::naira($p['offer_price'])) ?></p>
      <p class="text-sm text-green-900"><?= $p['ends_at'] ? 'Ends ' . e(local_date($p['ends_at'], $tz, 'D j M Y, g:i A')) . ' (' . e($tz) . ').' : 'No end time is set, so it stays live until the places run out.' ?>
        <?= (int) $p['seats_left'] ?> of <?= (int) $p['seats'] ?> places left.</p>
    <?php else: ?>
      <p class="font-bold text-amber-900">Founding offer is OFF. Students pay <?= e(\App\Services\CareerLaunchPricing::naira($p['regular_price'])) ?></p>
      <p class="text-sm text-amber-900"><?= $p['ended_by'] === 'seats' ? 'All founding places have been paid for.' : 'The timer has run out, or the offer price is 0.' ?></p>
    <?php endif; ?>
  </div>

  <form method="post" action="<?= e(url('/admin/career-launch-pricing')) ?>" class="mt-5 bg-white border rounded-lg p-5 space-y-4">
    <?= csrf_field() ?>
    <div><label class="label" for="offer_price">Founding offer price (&#8358;)</label>
      <input class="field" type="number" min="0" step="1" id="offer_price" name="offer_price" value="<?= e(old('offer_price', (string) (int) $p['offer_price'])) ?>" required><?= field_error('offer_price') ?></div>

    <div><label class="label" for="offer_ends">Offer ends on (date and time)</label>
      <input class="field" type="datetime-local" id="offer_ends" name="offer_ends" value="<?= e(old('offer_ends', $endsInput)) ?>"><?= field_error('offer_ends') ?>
      <p class="mt-1 text-sm text-slate-600">Time zone: <strong><?= e($tz) ?></strong>. Students see a live countdown. When it reaches zero the price switches to the regular price by itself.</p>
      <label class="mt-2 inline-flex items-center gap-2 text-sm"><input type="checkbox" name="clear_timer" value="1"> Remove the timer (offer has no end time)</label></div>

    <div><label class="label" for="seats">Founding places</label>
      <input class="field" type="number" min="0" step="1" id="seats" name="seats" value="<?= e(old('seats', (string) (int) $p['seats'])) ?>" required><?= field_error('seats') ?>
      <p class="mt-1 text-sm text-slate-600">The offer also ends once this many students have paid. Currently <?= (int) $p['paid'] ?> paid.</p></div>

    <div><label class="label" for="regular_price">Regular price after the offer (&#8358;)</label>
      <input class="field" type="number" min="0" step="1" id="regular_price" name="regular_price" value="<?= e(old('regular_price', (string) (int) $p['regular_price'])) ?>" required><?= field_error('regular_price') ?></div>

    <div class="flex flex-wrap gap-3"><button class="btn btn-primary" type="submit">Save</button><a class="btn btn-ghost" href="<?= e(url('/admin/career-launch')) ?>">Cancel</a></div>
  </form>
  <p class="mt-3 text-sm text-slate-600">A student's price is worked out at the moment they press <em>Pay now</em>. A payment already started keeps its amount.</p>
</div>
