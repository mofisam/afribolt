<h1 class="text-2xl font-extrabold">Register your company</h1>
<p class="mt-1 text-sm text-slate-600">Create an account to post jobs on Afribolt. After signing up you will upload your company documents so we can verify you.</p>
<form method="post" action="<?= e(url('/employer/register')) ?>" class="mt-5 space-y-4" novalidate>
  <?= csrf_field() ?>
  <div class="hidden" aria-hidden="true"><label>Leave empty <input type="text" name="website_url" tabindex="-1" autocomplete="off"></label></div>
  <div><label class="label" for="company_name">Company name</label><input class="field" id="company_name" name="company_name" value="<?= e(old('company_name')) ?>" autocomplete="organization" required><?= field_error('company_name') ?></div>
  <div class="grid sm:grid-cols-2 gap-4">
    <div><label class="label" for="cfn">Your first name</label><input class="field" id="cfn" name="contact_first_name" value="<?= e(old('contact_first_name')) ?>" autocomplete="given-name" required><?= field_error('contact_first_name') ?></div>
    <div><label class="label" for="cln">Your last name</label><input class="field" id="cln" name="contact_last_name" value="<?= e(old('contact_last_name')) ?>" autocomplete="family-name" required><?= field_error('contact_last_name') ?></div>
  </div>
  <div><label class="label" for="email">Work email</label><input class="field" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required><?= field_error('email') ?></div>
  <div><label class="label" for="phone">Phone or WhatsApp</label><input class="field" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" required><?= field_error('phone') ?></div>
  <div class="grid sm:grid-cols-2 gap-4">
    <div><label class="label" for="password">Password</label><input class="field" type="password" id="password" name="password" autocomplete="new-password" required><?= field_error('password') ?></div>
    <div><label class="label" for="pc">Confirm password</label><input class="field" type="password" id="pc" name="password_confirmation" autocomplete="new-password" required><?= field_error('password_confirmation') ?></div>
  </div>
  <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="agree" value="1" class="mt-1" required><span>I am authorised to post jobs for this company, and the information I give is true.</span></label>
  <button class="btn btn-primary w-full" type="submit">Create company account</button>
</form>
<p class="mt-4 text-sm text-slate-700">Already registered? <a class="underline text-bolt-dark font-bold" href="<?= e(url('/employer/login')) ?>">Log in</a></p>
