<h1 class="text-2xl font-extrabold">Log in</h1>
<form method="post" action="<?= e(url('/login')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="email">Email</label><input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus><?= field_error('email') ?></div>
  <div><label class="label" for="password">Password</label><input class="field" type="password" id="password" name="password" autocomplete="current-password" required><?= field_error('password') ?></div>
  <button class="btn btn-primary w-full" type="submit">Log in</button>
</form>
<p class="mt-4 text-sm"><a class="underline" href="<?= e(url('/forgot-password')) ?>">Forgot your password?</a></p>
<p class="mt-2 text-sm">New here? <a class="underline font-semibold" href="<?= e(url('/register')) ?>">Create an account</a></p>
