<h1 class="text-2xl font-extrabold">Create your account</h1>
<form method="post" action="<?= e(url('/register')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div class="grid grid-cols-2 gap-3">
    <div><label class="label" for="first_name">First name</label><input class="field" id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" autocomplete="given-name" required><?= field_error('first_name') ?></div>
    <div><label class="label" for="last_name">Last name</label><input class="field" id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" autocomplete="family-name" required><?= field_error('last_name') ?></div>
  </div>
  <div><label class="label" for="email">Email</label><input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required><?= field_error('email') ?></div>
  <div><label class="label" for="phone">Phone (optional)</label><input class="field" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel"><?= field_error('phone') ?></div>
  <div><label class="label" for="password">Password</label><input class="field" type="password" id="password" name="password" autocomplete="new-password" minlength="<?= (int) config('security.password_min_length', 8) ?>" required><?= field_error('password') ?></div>
  <div><label class="label" for="password_confirmation">Confirm password</label><input class="field" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required><?= field_error('password_confirmation') ?></div>
  <label class="flex gap-2 text-sm"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a class="underline" href="<?= e(url('/terms')) ?>">terms</a> and <a class="underline" href="<?= e(url('/privacy')) ?>">privacy policy</a>.</span></label>
  <?= field_error('terms') ?>
  <button class="btn btn-primary w-full" type="submit">Create account</button>
</form>
<p class="mt-4 text-sm">Already registered? <a class="underline font-semibold" href="<?= e(url('/login')) ?>">Log in</a></p>
