<?php
$product = find_product(query_int('id'));
$isOwner = $product && $user && (int) $product['seller_id'] === (int) $user['id'];
if (!$product || ($product['status'] !== 'active' && !$isOwner)) {
    abort(404, 'This product is no longer available.');
}

$productId = (int) $product['id'];
$page_title = $product['name'];
$images = product_images($productId);
$reviews = product_reviews($productId);
$breakdown = rating_breakdown($reviews);
$store = seller_store((int) $product['seller_id']);
$stock = (int) $product['stock'];
$discount = discount_percent($product);

$myReview = null;
if (is_buyer()) {
    foreach ($reviews as $review) {
        if ((int) $review['buyer_id'] === (int) $user['id']) {
            $myReview = $review;
        }
    }
}
$canReview = is_buyer() && can_review((int) $user['id'], $productId);

$related = $product['category_slug']
    ? search_products(['category' => $product['category_slug'], 'exclude_id' => $productId], 1, 4)['items']
    : [];
if (!$related) {
    $related = search_products(['seller_id' => $product['seller_id'], 'exclude_id' => $productId], 1, 4)['items'];
}
?>

<nav class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-stone-500" aria-label="Breadcrumb">
    <a href="index.php" class="hover:text-stone-900">Home</a> <?= icon('chevron-right', 'h-3.5 w-3.5') ?>
    <a href="<?= e(url('shop')) ?>" class="hover:text-stone-900">Shop</a>
    <?php if ($product['category_slug']): ?>
        <?= icon('chevron-right', 'h-3.5 w-3.5') ?>
        <a href="<?= e(url('shop', ['category' => $product['category_slug']])) ?>" class="hover:text-stone-900"><?= e($product['category_name']) ?></a>
    <?php endif; ?>
</nav>

<?php if ($product['status'] !== 'active'): ?>
    <div class="mb-6 flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        <?= icon('eye') ?> This product is <strong><?= e($product['status']) ?></strong> - only you can see this page.
        <a href="<?= e(url('seller_product', ['id' => $productId])) ?>" class="link ml-auto">Edit product</a>
    </div>
<?php endif; ?>

<div class="grid gap-10 lg:grid-cols-2 lg:gap-14">
    <div>
        <div class="relative overflow-hidden rounded-3xl bg-white ring-1 ring-stone-200">
            <img src="<?= e(image_url($images[0]['path'] ?? null)) ?>" alt="<?= e($product['name']) ?>" class="aspect-square w-full object-cover" data-gallery-main>
            <?php if ($discount): ?><span class="chip absolute left-4 top-4 bg-brand-600 px-3 py-1 text-white">Save <?= $discount ?>%</span><?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
            <div class="mt-4 grid grid-cols-5 gap-3 sm:grid-cols-6">
                <?php foreach ($images as $i => $image): ?>
                    <button type="button" data-gallery-thumb data-src="<?= e($image['path']) ?>"
                        class="overflow-hidden rounded-xl ring-2 transition <?= $i === 0 ? 'ring-brand-600' : 'ring-transparent hover:ring-stone-300' ?>"
                        aria-label="Show image <?= $i + 1 ?>">
                        <img src="<?= e($image['path']) ?>" alt="" class="aspect-square w-full object-cover">
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div>
        <?php if ($store): ?>
            <a href="<?= e(url('store', ['s' => $store['slug']])) ?>" class="inline-flex items-center gap-2 text-sm font-medium text-brand-700 hover:underline">
                <?= icon('store', 'h-4 w-4') ?> <?= e($store['name']) ?>
            </a>
        <?php endif; ?>
        <h1 class="page-title mt-2"><?= e($product['name']) ?></h1>

        <a href="#reviews" class="mt-3 inline-flex items-center gap-2 text-sm text-stone-600 hover:text-stone-900">
            <?php if ($reviews): ?>
                <?= stars($product['avg_rating']) ?>
                <span class="font-medium text-stone-900"><?= number_format((float) $product['avg_rating'], 1) ?></span>
                <span>(<?= plural(count($reviews), 'review') ?>)</span>
            <?php else: ?>
                <?= stars(0) ?> <span>No reviews yet</span>
            <?php endif; ?>
        </a>

        <div class="mt-6 flex flex-wrap items-baseline gap-3">
            <span class="text-3xl font-semibold text-stone-900"><?= money($product['price_cents']) ?></span>
            <?php if ($discount): ?>
                <span class="text-lg text-stone-400 line-through"><?= money($product['compare_at_cents']) ?></span>
                <span class="chip bg-brand-50 text-brand-800 ring-1 ring-brand-200">You save <?= money((int) $product['compare_at_cents'] - (int) $product['price_cents']) ?></span>
            <?php endif; ?>
        </div>

        <p class="mt-3 flex items-center gap-2 text-sm font-medium">
            <?php if ($stock <= 0): ?>
                <span class="h-2 w-2 rounded-full bg-stone-400"></span><span class="text-stone-600">Sold out</span>
            <?php elseif ($stock <= LOW_STOCK_THRESHOLD): ?>
                <span class="h-2 w-2 rounded-full bg-amber-500"></span><span class="text-amber-800">Only <?= $stock ?> left - order soon</span>
            <?php else: ?>
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span><span class="text-emerald-800">In stock</span>
            <?php endif; ?>
        </p>

        <div class="mt-8 border-t border-stone-200 pt-8">
            <?php if ($isOwner): ?>
                <div class="flex flex-wrap gap-3">
                    <a href="<?= e(url('seller_product', ['id' => $productId])) ?>" class="btn btn-dark btn-lg"><?= icon('pencil', 'h-5 w-5') ?> Edit product</a>
                    <a href="<?= e(url('seller_products')) ?>" class="btn btn-outline btn-lg">Back to Seller Hub</a>
                </div>
            <?php elseif (is_seller()): ?>
                <p class="rounded-xl bg-stone-100 px-4 py-3 text-sm text-stone-600">You are signed in as a seller. Use a buyer account to purchase gifts.</p>
            <?php elseif ($stock > 0): ?>
                <?= form_open('cart_add', ['class' => 'flex flex-wrap items-center gap-3']) ?>
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <div class="flex items-center rounded-full border border-stone-300 bg-white" data-stepper>
                        <button type="button" class="p-3 text-stone-600 hover:text-stone-900" data-step="-1" aria-label="Decrease quantity"><?= icon('minus', 'h-4 w-4') ?></button>
                        <input type="number" name="quantity" value="1" min="1" max="<?= min($stock, MAX_CART_QTY) ?>"
                            class="w-12 border-0 p-0 text-center text-sm font-semibold focus:ring-0" aria-label="Quantity">
                        <button type="button" class="p-3 text-stone-600 hover:text-stone-900" data-step="1" aria-label="Increase quantity"><?= icon('plus', 'h-4 w-4') ?></button>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg flex-1"><?= icon('bag', 'h-5 w-5') ?> Add to cart</button>
                </form>
            <?php else: ?>
                <button type="button" class="btn btn-lg w-full bg-stone-200 text-stone-500" disabled>Sold out</button>
            <?php endif; ?>

            <?php if (is_buyer()): ?>
                <div class="mt-4 flex items-center gap-3 text-sm text-stone-600">
                    <?php wishlist_button($productId); ?>
                    <span><?= in_array($productId, wishlist_ids(), true) ? 'Saved to your wishlist' : 'Save for later' ?></span>
                </div>
            <?php endif; ?>
        </div>

        <ul class="mt-8 grid gap-3 rounded-2xl bg-white p-5 text-sm text-stone-600 ring-1 ring-stone-200">
            <li class="flex items-center gap-3"><?= icon('truck', 'h-5 w-5 text-brand-600') ?>
                Ships from <?= e($store['name'] ?? 'the maker') ?> · <?= money(SHIPPING_FLAT_CENTS) ?> shipping, free over <?= money(FREE_SHIPPING_THRESHOLD_CENTS) ?> from this shop</li>
            <li class="flex items-center gap-3"><?= icon('cash', 'h-5 w-5 text-brand-600') ?> Pay on delivery</li>
            <?php if ($images): ?>
                <li class="flex items-center gap-3"><?= icon('cube', 'h-5 w-5 text-brand-600') ?>
                    <a href="<?= e(url('visualize', ['id' => $productId])) ?>" class="link">View this gift in 3D</a></li>
            <?php endif; ?>
        </ul>

        <div class="mt-8">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-stone-500">About this gift</h2>
            <div class="mt-3 whitespace-pre-line text-[15px] leading-relaxed text-stone-700"><?= e($product['description']) ?></div>
            <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                <?php if ($product['category_name']): ?>
                    <div><dt class="text-stone-500">Category</dt><dd class="mt-0.5 font-medium text-stone-900"><?= e($product['category_name']) ?></dd></div>
                <?php endif; ?>
                <?php if ($product['sku']): ?>
                    <div><dt class="text-stone-500">SKU</dt><dd class="mt-0.5 font-medium text-stone-900"><?= e($product['sku']) ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</div>

<section id="reviews" class="mt-20 scroll-mt-32">
    <h2 class="section-title">Customer reviews</h2>
    <div class="mt-8 grid gap-10 lg:grid-cols-3">
        <div>
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <span class="font-display text-5xl font-semibold text-stone-900"><?= $reviews ? number_format((float) $product['avg_rating'], 1) : '–' ?></span>
                    <div>
                        <?= stars($product['avg_rating'], 'h-5 w-5') ?>
                        <p class="mt-1 text-sm text-stone-500"><?= plural(count($reviews), 'review') ?></p>
                    </div>
                </div>
                <div class="mt-6 space-y-2">
                    <?php foreach ($breakdown as $rating => $count): $share = $reviews ? round($count / count($reviews) * 100) : 0; ?>
                        <div class="flex items-center gap-3 text-sm" title="<?= plural($count, 'review') ?>">
                            <span class="w-3 text-stone-600"><?= $rating ?></span>
                            <?= star_icon('h-4 w-4', true) ?>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-stone-100"><span class="block h-full rounded-full bg-amber-400" style="width: <?= $share ?>%"></span></span>
                            <span class="w-8 text-right text-xs text-stone-500"><?= $count ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if ($canReview): ?>
                <?= form_open('review_save', ['class' => 'card mt-6 space-y-4 p-6'], url('product', ['id' => $productId]) . '#reviews') ?>
                    <h3 class="font-semibold text-stone-900"><?= $myReview ? 'Update your review' : 'Write a review' ?></h3>
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    <fieldset>
                        <legend class="label">Your rating</legend>
                        <div class="star-input flex flex-row-reverse justify-end gap-1">
                            <?php $current = (int) old('rating', (string) ($myReview['rating'] ?? 0));
                            for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" id="rating-<?= $i ?>" name="rating" value="<?= $i ?>" class="peer sr-only" <?= $current === $i ? 'checked' : '' ?> required>
                                <label for="rating-<?= $i ?>" class="cursor-pointer" title="<?= plural($i, 'star') ?>"><?= star_icon('h-7 w-7') ?></label>
                            <?php endfor; ?>
                        </div>
                    </fieldset>
                    <div>
                        <label class="label" for="review-title">Headline <span class="font-normal text-stone-400">(optional)</span></label>
                        <input class="input" id="review-title" name="title" maxlength="100" value="<?= e(old('title', $myReview['title'] ?? '')) ?>">
                    </div>
                    <div>
                        <label class="label" for="review-body">Your review</label>
                        <textarea class="input min-h-28" id="review-body" name="body" maxlength="2000" required><?= e(old('body', $myReview['body'] ?? '')) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-dark w-full"><?= $myReview ? 'Update review' : 'Post review' ?></button>
                </form>
            <?php elseif (is_buyer()): ?>
                <p class="mt-4 flex gap-2 text-sm text-stone-500"><?= icon('shield', 'h-5 w-5 shrink-0') ?> Reviews are open to buyers once their order of this item has been delivered.</p>
            <?php endif; ?>
        </div>

        <div class="lg:col-span-2">
            <?php if (!$reviews): ?>
                <?php empty_state('chat', 'No reviews yet', 'Reviews come from buyers who have received this gift.'); ?>
            <?php else: ?>
                <div class="divide-y divide-stone-200">
                    <?php foreach ($reviews as $review): ?>
                        <article class="py-6 first:pt-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-200 text-sm font-semibold text-stone-700"><?= e(strtoupper(str_cut($review['buyer_name'], 1))) ?></span>
                                    <div>
                                        <p class="text-sm font-semibold text-stone-900"><?= e($review['buyer_name']) ?></p>
                                        <p class="flex items-center gap-1 text-xs text-emerald-700"><?= icon('check-circle', 'h-3.5 w-3.5') ?> Verified buyer</p>
                                    </div>
                                </div>
                                <span class="text-xs text-stone-400"><?= e(fmt_date($review['updated_at'] ?? $review['created_at'])) ?></span>
                            </div>
                            <div class="mt-3"><?= stars($review['rating']) ?></div>
                            <?php if ($review['title']): ?><h4 class="mt-2 font-semibold text-stone-900"><?= e($review['title']) ?></h4><?php endif; ?>
                            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-stone-700"><?= e($review['body']) ?></p>
                            <?php if ($review['seller_reply']): ?>
                                <div class="mt-4 rounded-xl border-l-4 border-brand-300 bg-brand-50/60 px-4 py-3 text-sm">
                                    <p class="font-semibold text-stone-900">Response from <?= e($store['name'] ?? 'the seller') ?></p>
                                    <p class="mt-1 whitespace-pre-line text-stone-700"><?= e($review['seller_reply']) ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php if ($related): ?>
    <section class="mt-20">
        <h2 class="section-title">You may also like</h2>
        <div class="mt-8"><?php product_grid($related); ?></div>
    </section>
<?php endif; ?>
