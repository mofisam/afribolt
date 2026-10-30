<?php
use App\Core\View;
$min = (int) config('security.password_min_length', 8);
?>
<h1 class="text-3xl font-extrabold">Choose a new password</h1>
<p class="mt-1 text-slate-600">Pick something you don't use anywhere else.</p>
<form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="mt-6 space-y-5" novalidate>
  <?= csrf_field() ?>
  <div>
    <label class="label" for="password">New password</label>
    <?= View::partial('partials/password-field', ['id' => 'password', 'autocomplete' => 'new-password', 'caps' => true, 'extra' => 'minlength="' . $min . '" data-strength-input data-min="' . $min . '"']) ?>
    <div class="pw-meter mt-2" aria-hidden="true"><span data-strength-bar></span></div>
    <p class="hint" data-strength-text role="status"></p>
    <?= field_error('password') ?>
  </div>
  <div>
    <label class="label" for="password_confirmation">Confirm new password</label>
    <?= View::partial('partials/password-field', ['id' => 'password_confirmation', 'autocomplete' => 'new-password', 'extra' => 'data-match-input="password"']) ?>
    <p class="hint" data-match-text role="status"></p>
    <?= field_error('password_confirmation') ?>
  </div>
  <button class="btn btn-primary w-full !py-3" type="submit">Update password</button>
</form>
