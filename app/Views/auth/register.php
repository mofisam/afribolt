<?php
use App\Core\{Session, View};
$min = (int) config('security.password_min_length', 8);
$fromCohort = str_starts_with((string) Session::get('intended', ''), '/cohorts/');
$bad = fn(string $k) => Session::error($k) ? 'invalid' : '';
?>
<h1 class="text-3xl font-extrabold">Create your account</h1>
<p class="mt-1 text-slate-600">It takes a minute. You can browse courses without one, but you need an account to register for a cohort.</p>
<?php if ($fromCohort): ?>
  <p class="mt-4 rounded-lg bg-bolt-tint p-3 text-sm" role="status">Create your account to continue. We'll take you straight back to the cohort you were viewing.</p>
<?php endif; ?>

<form method="post" action="<?= e(url('/register')) ?>" class="mt-6 space-y-5" novalidate>
  <?= csrf_field() ?>
  <div class="grid sm:grid-cols-2 gap-4">
    <div><label class="label" for="first_name">First name</label>
      <input class="field <?= $bad('first_name') ?>" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" autocomplete="given-name" required autofocus><?= field_error('first_name') ?></div>
    <div><label class="label" for="last_name">Last name</label>
      <input class="field <?= $bad('last_name') ?>" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" autocomplete="family-name" required><?= field_error('last_name') ?></div>
  </div>
  <div><label class="label" for="email">Email address</label>
    <input class="field <?= $bad('email') ?>" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" required>
    <?= field_error('email') ?><p class="hint">We email your payment receipt and class updates here.</p></div>
  <div><label class="label" for="phone">Phone <span class="font-normal text-slate-500">(optional)</span></label>
    <input class="field <?= $bad('phone') ?>" type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" inputmode="tel" placeholder="+234 ...">
    <?= field_error('phone') ?></div>

  <div>
    <label class="label" for="password">Password</label>
    <?= View::partial('partials/password-field', ['id' => 'password', 'autocomplete' => 'new-password', 'caps' => true, 'extra' => 'minlength="' . $min . '" data-strength-input data-min="' . $min . '"']) ?>
    <div class="pw-meter mt-2" aria-hidden="true"><span data-strength-bar></span></div>
    <p class="hint" data-strength-text role="status">At least <?= $min ?> characters. A short phrase works well.</p>
    <?= field_error('password') ?>
  </div>
  <div>
    <label class="label" for="password_confirmation">Confirm password</label>
    <?= View::partial('partials/password-field', ['id' => 'password_confirmation', 'autocomplete' => 'new-password', 'extra' => 'data-match-input="password"']) ?>
    <p class="hint" data-match-text role="status"></p>
    <?= field_error('password_confirmation') ?>
  </div>

  <div>
    <label class="flex items-start gap-3"><input type="checkbox" name="terms" value="1" class="mt-1 w-5 h-5" required <?= old('terms') ? 'checked' : '' ?>>
      <span class="text-sm">I agree to the <a class="underline text-bolt-dark" href="<?= e(url('/terms')) ?>" target="_blank" rel="noopener">terms of use</a>,
        <a class="underline text-bolt-dark" href="<?= e(url('/privacy')) ?>" target="_blank" rel="noopener">privacy policy</a> and
        <a class="underline text-bolt-dark" href="<?= e(url('/refund-policy')) ?>" target="_blank" rel="noopener">refund policy</a>.</span></label>
    <?= field_error('terms') ?>
  </div>
  <button class="btn btn-primary w-full !py-3" type="submit">Create account</button>
  <p class="text-xs text-slate-500 text-center">We never see or store your card details. Payments are handled securely by our payment partner.</p>
</form>

<p class="mt-6 text-center text-slate-600">Already registered? <a class="underline font-bold text-bolt-dark" href="<?= e(url('/login')) ?>">Log in</a></p>
