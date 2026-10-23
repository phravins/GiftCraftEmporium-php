<?php
$page_title = 'Create account';
$role = old('role', query('role') === 'seller' ? 'seller' : 'buyer');
$roles = [
    'buyer'  => ['bag', 'I want to shop', 'Buy gifts from independent makers.'],
    'seller' => ['store', 'I want to sell', 'Open a shop and list your products.'],
];
?>

<h1 class="page-title">Create your account</h1>
<p class="mt-2 text-sm text-stone-600">Already have one? <a href="<?= e(url('login')) ?>" class="link">Sign in</a></p>

<?= form_open('register', ['class' => 'mt-8 space-y-5'], url('register')) ?>
    <fieldset>
        <legend class="label">Account type</legend>
        <div class="grid grid-cols-2 gap-3">
            <?php foreach ($roles as $value => [$iconName, $title, $text]): ?>
                <label class="cursor-pointer">
                    <input type="radio" name="role" value="<?= $value ?>" class="peer sr-only" <?= $role === $value ? 'checked' : '' ?> data-role-choice>
                    <span class="block h-full rounded-2xl border-2 border-stone-200 bg-white p-4 transition hover:border-stone-300 peer-checked:border-brand-600 peer-checked:bg-brand-50 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-900 text-white"><?= icon($iconName, 'h-5 w-5') ?></span>
                        <span class="mt-3 block text-sm font-semibold text-stone-900"><?= $title ?></span>
                        <span class="mt-0.5 block text-xs text-stone-500"><?= $text ?></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>

    <div class="<?= $role === 'seller' ? '' : 'hidden' ?>" data-seller-only>
        <label class="label" for="store_name">Shop name</label>
        <input class="input" id="store_name" name="store_name" value="<?= e(old('store_name')) ?>" maxlength="60" placeholder="e.g. Willow & Clay Studio" <?= $role === 'seller' ? 'required' : '' ?>>
        <p class="help">This is how buyers will find you. You can change it later.</p>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="name">Your name</label>
            <input class="input" id="name" name="name" value="<?= e(old('name')) ?>" autocomplete="name" maxlength="80" required>
        </div>
        <div>
            <label class="label" for="phone">Phone <span class="font-normal text-stone-400">(optional)</span></label>
            <input class="input" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" maxlength="30">
        </div>
    </div>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="120" required>
    </div>
    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <label class="label" for="password">Password</label>
            <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
            <p class="help">At least 8 characters.</p>
        </div>
        <div>
            <label class="label" for="password_confirm">Confirm password</label>
            <input class="input" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg w-full">Create account</button>
</form>
