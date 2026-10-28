<h1 class="text-2xl font-extrabold">Admin login</h1>
<form method="post" action="<?= e(url('/admin/login')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="email">Email</label><input class="field" type="email" id="email" name="email" autocomplete="username" required autofocus><?= field_error('email') ?></div>
  <div><label class="label" for="password">Password</label><input class="field" type="password" id="password" name="password" autocomplete="current-password" required><?= field_error('password') ?></div>
  <button class="btn btn-primary w-full" type="submit">Log in</button>
</form>
