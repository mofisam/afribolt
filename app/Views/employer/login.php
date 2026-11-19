<h1 class="text-2xl font-extrabold">Employer login</h1>
<form method="post" action="<?= e(url('/employer/login')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="email">Work email</label><input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" required autofocus><?= field_error('email') ?></div>
  <div><label class="label" for="password">Password</label><input class="field" type="password" id="password" name="password" autocomplete="current-password" required><?= field_error('password') ?></div>
  <button class="btn btn-primary w-full" type="submit">Log in</button>
</form>
<p class="mt-4 text-sm text-slate-700">New here? <a class="underline text-bolt-dark font-bold" href="<?= e(url('/employer/register')) ?>">Register your company</a></p>
<p class="mt-1 text-sm text-slate-500">Looking for a job instead? <a class="underline" href="<?= e(url('/login')) ?>">Student login</a></p>
