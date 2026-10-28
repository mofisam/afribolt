<h1 class="text-2xl font-extrabold">Choose a new password</h1>
<form method="post" action="<?= e(url('/reset-password/' . $token)) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="password">New password</label><input class="field" type="password" id="password" name="password" autocomplete="new-password" required><?= field_error('password') ?></div>
  <div><label class="label" for="password_confirmation">Confirm password</label><input class="field" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required><?= field_error('password_confirmation') ?></div>
  <button class="btn btn-primary w-full" type="submit">Update password</button>
</form>
