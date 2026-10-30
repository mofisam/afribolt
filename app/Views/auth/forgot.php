<h1 class="text-3xl font-extrabold">Reset your password</h1>
<p class="mt-1 text-slate-600">Enter the email you registered with and we'll send you a reset link. It works for one hour.</p>
<form method="post" action="<?= e(url('/forgot-password')) ?>" class="mt-6 space-y-5" novalidate>
  <?= csrf_field() ?>
  <div><label class="label" for="email">Email address</label>
    <input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" required autofocus><?= field_error('email') ?></div>
  <button class="btn btn-primary w-full !py-3" type="submit">Send reset link</button>
</form>
<p class="mt-6 text-center"><a class="underline font-bold text-bolt-dark" href="<?= e(url('/login')) ?>">&larr; Back to log in</a></p>
