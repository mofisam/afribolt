<h1 class="text-3xl font-extrabold">My profile</h1>
<div class="mt-6 grid md:grid-cols-2 gap-6">
  <form method="post" action="<?= e(url('/student/profile')) ?>" class="bg-white border rounded-lg p-6 space-y-4" novalidate>
    <?= csrf_field() ?><h2 class="font-bold text-xl">Details</h2>
    <div><label class="label" for="first_name">First name</label><input class="field" id="first_name" name="first_name" value="<?= e(old('first_name', $student['first_name'])) ?>" required><?= field_error('first_name') ?></div>
    <div><label class="label" for="last_name">Last name</label><input class="field" id="last_name" name="last_name" value="<?= e(old('last_name', $student['last_name'])) ?>" required><?= field_error('last_name') ?></div>
    <div><label class="label" for="phone">Phone</label><input class="field" id="phone" name="phone" value="<?= e(old('phone', $student['phone'])) ?>"><?= field_error('phone') ?></div>
    <p class="text-sm text-slate-500">Email: <?= e($student['email']) ?></p>
    <button class="btn btn-primary" type="submit">Save changes</button>
  </form>
  <form method="post" action="<?= e(url('/student/profile/password')) ?>" class="bg-white border rounded-lg p-6 space-y-4" novalidate>
    <?= csrf_field() ?><h2 class="font-bold text-xl">Change password</h2>
    <div><label class="label" for="current_password">Current password</label><input class="field" type="password" id="current_password" name="current_password" autocomplete="current-password" required><?= field_error('current_password') ?></div>
    <div><label class="label" for="password">New password</label><input class="field" type="password" id="password" name="password" autocomplete="new-password" required><?= field_error('password') ?></div>
    <div><label class="label" for="password_confirmation">Confirm new password</label><input class="field" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required><?= field_error('password_confirmation') ?></div>
    <button class="btn btn-primary" type="submit">Update password</button>
  </form>
</div>
