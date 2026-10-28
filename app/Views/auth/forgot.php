<h1 class="text-2xl font-extrabold">Reset your password</h1>
<p class="mt-2 text-slate-600">Enter your email and we'll send you a link.</p>
<form method="post" action="<?= e(url('/forgot-password')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="email">Email</label><input class="field" type="email" id="email" name="email" required><?= field_error('email') ?></div>
  <button class="btn btn-primary w-full" type="submit">Send reset link</button>
</form>
<p class="mt-4 text-sm"><a class="underline" href="<?= e(url('/login')) ?>">Back to log in</a></p>
