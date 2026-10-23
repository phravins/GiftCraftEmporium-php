<?php
$page_title = 'Store settings';
$store = seller_store((int) $user['id']);
?>

<h1 class="page-title">Store settings</h1>
<p class="mt-1 text-sm text-stone-500">How your shop appears to buyers.</p>

<?= form_open('seller_settings_save', ['class' => 'mt-8 grid gap-6 lg:grid-cols-3', 'enctype' => 'multipart/form-data']) ?>
    <section class="card space-y-5 p-6 lg:col-span-2">
        <div>
            <label class="label" for="store_name">Shop name</label>
            <input class="input" id="store_name" name="store_name" value="<?= e(old('store_name', $store['name'] ?? '')) ?>" maxlength="60" required>
            <?php if ($store): ?>
                <p class="help">Your storefront: <a href="<?= e(url('store', ['s' => $store['slug']])) ?>" class="link"><?= e(url('store', ['s' => $store['slug']])) ?></a></p>
            <?php endif; ?>
        </div>
        <div>
            <label class="label" for="tagline">Tagline</label>
            <input class="input" id="tagline" name="tagline" value="<?= e(old('tagline', $store['tagline'] ?? '')) ?>" maxlength="100" placeholder="e.g. Small-batch candles poured by hand in Pune">
        </div>
        <div>
            <label class="label" for="description">About your shop</label>
            <textarea class="input min-h-40" id="description" name="description" maxlength="3000"
                placeholder="Tell buyers who you are, how you make things and what you care about."><?= e(old('description', $store['description'] ?? '')) ?></textarea>
        </div>
    </section>

    <div class="space-y-6">
        <section class="card space-y-4 p-6">
            <h2 class="font-semibold text-stone-900">Logo</h2>
            <div class="flex items-center gap-4">
                <?php if (!empty($store['logo_path'])): ?>
                    <img src="<?= e($store['logo_path']) ?>" alt="Current logo" class="h-20 w-20 rounded-2xl object-cover ring-1 ring-stone-200" data-logo-preview>
                <?php else: ?>
                    <span class="flex h-20 w-20 items-center justify-center rounded-2xl bg-brand-100 font-display text-3xl font-semibold text-brand-800" data-logo-preview><?= e(strtoupper(str_cut($store['name'] ?? '?', 1))) ?></span>
                <?php endif; ?>
                <label class="btn btn-outline btn-sm cursor-pointer">
                    <?= icon('photo', 'h-4 w-4') ?> Upload
                    <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" data-image-input="#logo-preview">
                </label>
            </div>
            <div id="logo-preview" class="grid grid-cols-3 gap-3"></div>
            <?php if (!empty($store['logo_path'])): ?>
                <label class="flex items-center gap-2 text-sm text-stone-600"><input type="checkbox" name="remove_logo" value="1" class="checkbox"> Remove current logo</label>
            <?php endif; ?>
            <p class="help">Square images work best.</p>
        </section>
        <button type="submit" class="btn btn-primary btn-lg w-full">Save settings</button>
    </div>
</form>
