<?php $page_title = 'Sign in'; ?>

<h1 class="page-title">Welcome back</h1>
<p class="mt-2 text-sm text-stone-600">Sign in to shop, track your orders or manage your store.</p>

<?= form_open('login', ['class' => 'mt-8 space-y-5'], url('login')) ?>
    <div>
        <label class="label" for="email">Email</label>
        <input class="input" type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required autofocus>
    </div>
    <div>
        <label class="label" for="password">Password</label>
        <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-lg w-full">Sign in</button>
</form>

<div class="mt-8 grid gap-3 rounded-2xl border border-stone-200 bg-white p-5 text-sm text-stone-600">
    <p>New to GiftCraft?</p>
    <div class="flex flex-wrap gap-2">
        <a href="<?= e(url('register')) ?>" class="btn btn-outline btn-sm"><?= icon('bag', 'h-4 w-4') ?> Create a buyer account</a>
        <a href="<?= e(url('register', ['role' => 'seller'])) ?>" class="btn btn-outline btn-sm"><?= icon('store', 'h-4 w-4') ?> Open a shop</a>
    </div>
</div>
