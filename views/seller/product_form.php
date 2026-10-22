<?php
$sellerId = (int) $user['id'];
$productId = query_int('id');
$product = $productId ? seller_product_or_404($productId, $sellerId) : null;
$images = $product ? product_images($productId) : [];
$page_title = $product ? 'Edit ' . $product['name'] : 'Add product';
$categories = all_categories();

$value = fn(string $key, $default = '') => old($key, (string) ($product[$key] ?? $default));
$status = $value('status', 'active');
?>

<a href="<?= e(url('seller_products')) ?>" class="inline-flex items-center gap-1.5 text-sm text-stone-600 hover:text-stone-900"><?= icon('arrow-left', 'h-4 w-4') ?> Products</a>
<div class="mt-3 flex flex-wrap items-center justify-between gap-4">
    <h1 class="page-title"><?= $product ? 'Edit product' : 'Add a product' ?></h1>
    <?php if ($product && $product['status'] === 'active'): ?>
        <a href="<?= e(url('product', ['id' => $productId])) ?>" class="btn btn-outline btn-sm"><?= icon('eye', 'h-4 w-4') ?> View on storefront</a>
    <?php endif; ?>
</div>

<?= form_open('seller_product_save', ['class' => 'mt-8 grid gap-6 lg:grid-cols-3', 'enctype' => 'multipart/form-data'],
    url('seller_product', ['id' => $productId ?: null])) ?>
    <?php if ($product): ?><input type="hidden" name="product_id" value="<?= $productId ?>"><?php endif; ?>

    <div class="space-y-6 lg:col-span-2">
        <section class="card space-y-5 p-6">
            <h2 class="font-semibold text-stone-900">Details</h2>
            <div>
                <label class="label" for="name">Product name</label>
                <input class="input" id="name" name="name" value="<?= e($value('name')) ?>" maxlength="120" required placeholder="e.g. Hand-thrown ceramic mug">
            </div>
            <div>
                <label class="label" for="description">Description</label>
                <textarea class="input min-h-40" id="description" name="description" maxlength="5000" required
                    placeholder="What makes it special? Materials, size, care instructions, personalisation..."><?= e($value('description')) ?></textarea>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="label" for="category">Category</label>
                    <input class="input" id="category" name="category" list="category-options" maxlength="40"
                        value="<?= e(old('category', (string) ($product['category_name'] ?? ''))) ?>" placeholder="Choose or type a new one">
                    <datalist id="category-options">
                        <?php foreach ($categories as $category): ?><option value="<?= e($category['name']) ?>"><?php endforeach; ?>
                    </datalist>
                    <p class="help">Pick an existing category or type a new one.</p>
                </div>
                <div>
                    <label class="label" for="sku">SKU <span class="font-normal text-stone-400">(optional)</span></label>
                    <input class="input" id="sku" name="sku" value="<?= e($value('sku')) ?>" maxlength="40">
                </div>
            </div>
        </section>

        <section class="card space-y-5 p-6">
            <div>
                <h2 class="font-semibold text-stone-900">Photos</h2>
                <p class="mt-1 text-sm text-stone-500">Up to <?= MAX_PRODUCT_IMAGES ?> JPG, PNG, WebP or GIF images, <?= MAX_UPLOAD_BYTES / 1024 / 1024 ?> MB each. Square photos on a plain background look best (and work great in the 3D viewer).</p>
            </div>
            <?php if ($images): ?>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <?php foreach ($images as $i => $image): ?>
                        <div class="overflow-hidden rounded-xl ring-1 ring-stone-200">
                            <img src="<?= e($image['path']) ?>" alt="" class="aspect-square w-full object-cover">
                            <div class="flex items-center justify-between gap-2 bg-stone-50 px-3 py-2 text-xs">
                                <label class="flex items-center gap-1.5"><input type="radio" name="cover_image_id" value="<?= (int) $image['id'] ?>" class="radio" <?= $i === 0 ? 'checked' : '' ?>> Cover</label>
                                <label class="flex items-center gap-1.5 text-red-700"><input type="checkbox" name="remove_images[]" value="<?= (int) $image['id'] ?>" class="checkbox text-red-600 focus:ring-red-500"> Remove</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <label class="flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-stone-300 px-6 py-8 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
                <span class="text-stone-400"><?= icon('photo', 'h-10 w-10') ?></span>
                <span class="mt-2 text-sm font-medium text-stone-800">Click to upload photos</span>
                <span class="text-xs text-stone-500">You can select several at once</span>
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple class="sr-only" data-image-input="#image-preview">
            </label>
            <div id="image-preview" class="grid grid-cols-3 gap-3 sm:grid-cols-6"></div>
        </section>
    </div>

    <div class="space-y-6">
        <section class="card space-y-5 p-6">
            <h2 class="font-semibold text-stone-900">Pricing</h2>
            <div>
                <label class="label" for="price">Price</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm text-stone-500"><?= e(APP_CURRENCY) ?></span>
                    <input class="input pl-8" id="price" name="price" inputmode="decimal" required placeholder="0.00"
                        value="<?= e(old('price', $product ? money_input($product['price_cents']) : '')) ?>">
                </div>
            </div>
            <div>
                <label class="label" for="compare_at">Compare-at price <span class="font-normal text-stone-400">(optional)</span></label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-sm text-stone-500"><?= e(APP_CURRENCY) ?></span>
                    <input class="input pl-8" id="compare_at" name="compare_at" inputmode="decimal" placeholder="0.00"
                        value="<?= e(old('compare_at', $product && $product['compare_at_cents'] !== null ? money_input($product['compare_at_cents']) : '')) ?>">
                </div>
                <p class="help">Shows a "sale" badge when higher than the price.</p>
            </div>
        </section>

        <section class="card space-y-5 p-6">
            <h2 class="font-semibold text-stone-900">Inventory</h2>
            <div>
                <label class="label" for="stock">Quantity in stock</label>
                <input class="input" type="number" id="stock" name="stock" min="0" max="100000" required value="<?= e($value('stock', '1')) ?>">
            </div>
        </section>

        <section class="card space-y-4 p-6">
            <h2 class="font-semibold text-stone-900">Visibility</h2>
            <?php foreach (['active' => ['Active', 'Visible and purchasable on the marketplace.'], 'draft' => ['Draft', 'Hidden from buyers while you work on it.']] as $option => [$label, $help]): ?>
                <label class="flex cursor-pointer gap-3 rounded-xl border border-stone-200 p-3.5 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/50">
                    <input type="radio" name="status" value="<?= $option ?>" class="radio mt-0.5" <?= $status === $option || ($option === 'active' && $status === 'archived') ? 'checked' : '' ?>>
                    <span class="text-sm"><span class="block font-medium text-stone-900"><?= $label ?></span><span class="block text-stone-500"><?= $help ?></span></span>
                </label>
            <?php endforeach; ?>
            <?php if ($status === 'archived'): ?>
                <p class="text-xs text-stone-500">This product is archived. Saving it will restore it.</p>
            <?php endif; ?>
        </section>

        <button type="submit" class="btn btn-primary btn-lg w-full"><?= $product ? 'Save changes' : 'Publish product' ?></button>
    </div>
</form>

<?php if ($product && $product['status'] !== 'archived'): ?>
    <section class="card mt-8 flex flex-wrap items-center justify-between gap-4 border-red-200 p-6">
        <div>
            <h2 class="font-semibold text-stone-900">Remove this product</h2>
            <p class="mt-1 text-sm text-stone-500">Products that have been ordered are archived so order history stays intact; others are deleted.</p>
        </div>
        <?= form_open('seller_product_delete', ['data-confirm' => 'Remove "' . $product['name'] . '" from your shop?'], url('seller_products')) ?>
            <input type="hidden" name="product_id" value="<?= $productId ?>">
            <button type="submit" class="btn btn-danger"><?= icon('trash', 'h-4 w-4') ?> Remove product</button>
        </form>
    </section>
<?php endif; ?>
