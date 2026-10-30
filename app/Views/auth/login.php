<?php
use App\Core\{Session, View};
$intended = (string) Session::get('intended', '');
$fromCohort = str_starts_with($intended, '/cohorts/');
?>
<h1 class="text-3xl font-extrabold">Welcome back</h1>
<p class="mt-1 text-slate-600">Log in to see your classes and countdowns.</p>
<?php if ($fromCohort): ?>
  <p class="mt-4 rounded-lg bg-bolt-tint p-3 text-sm" role="status">Log in to continue your registration. We'll take you straight back to the cohort.</p>
<?php endif; ?>

<form method="post" action="<?= e(url('/login')) ?>" class="mt-6 space-y-5" novalidate>
  <?= csrf_field() ?>
  <div>
    <label class="label" for="email">Email address</label>
    <input class="field <?= \App\Core\Session::error('email') ? 'invalid' : '' ?>" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required autofocus>
    <?= field_error('email') ?>
  </div>
  <div>
    <div class="flex items-baseline justify-between"><label class="label" for="password">Password</label>
      <a class="text-sm underline text-bolt-dark" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a></div>
    <?= View::partial('partials/password-field', ['id' => 'password', 'autocomplete' => 'current-password', 'caps' => true]) ?>
    <?= field_error('password') ?>
  </div>
  <button class="btn btn-primary w-full !py-3" type="submit">Log in</button>
</form>

<div class="mt-8 border-t pt-6 text-center">
  <p class="text-slate-600">New to AfriBolt?</p>
  <a class="btn btn-ghost w-full mt-2" href="<?= e(url('/register')) ?>">Create a free account</a>
</div>
