<?php
// Small view helpers shared across pages.

/** Opening <form> tag for an action, with CSRF token and the page to return to. */
function form_open(string $action, array $attrs = [], ?string $back = null): string {
    $html = '<form method="post" action="' . e(action_url($action)) . '"';
    foreach ($attrs as $name => $value) {
        $html .= ' ' . e($name) . '="' . e($value) . '"';
    }
    return $html . '>' . csrf_field()
        . '<input type="hidden" name="_back" value="' . e($back ?? current_url()) . '">';
}

function flash_messages(): void {
    $styles = [
        'success' => ['border-emerald-200 bg-emerald-50 text-emerald-900', 'check-circle', 'text-emerald-600'],
        'error'   => ['border-red-200 bg-red-50 text-red-900', 'warning', 'text-red-600'],
        'info'    => ['border-sky-200 bg-sky-50 text-sky-900', 'info', 'text-sky-600'],
    ];
    foreach (take_flashes() as $flash):
        [$box, $iconName, $iconColor] = $styles[$flash['type']] ?? $styles['info']; ?>
        <div class="mb-4 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm shadow-sm <?= $box ?>" role="status" data-flash>
            <span class="<?= $iconColor ?>"><?= icon($iconName) ?></span>
            <p class="flex-1 pt-0.5"><?= e($flash['message']) ?></p>
            <button type="button" class="opacity-60 hover:opacity-100" data-dismiss aria-label="Dismiss"><?= icon('x', 'h-4 w-4') ?></button>
        </div>
    <?php endforeach;
}

function empty_state(string $iconName, string $title, string $text, string $actions = ''): void { ?>
    <div class="card flex flex-col items-center px-6 py-14 text-center">
        <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand-600"><?= icon($iconName, 'h-7 w-7') ?></div>
        <h3 class="font-display text-xl font-semibold text-stone-900"><?= e($title) ?></h3>
        <p class="mt-2 max-w-md text-sm leading-relaxed text-stone-600"><?= e($text) ?></p>
        <?php if ($actions): ?><div class="mt-6 flex flex-wrap justify-center gap-3"><?= $actions ?></div><?php endif; ?>
    </div>
<?php }

function pagination(array $pg): void {
    if ($pg['pages'] <= 1) {
        return;
    }
    $current = $pg['current'];
    $window = array_unique(array_filter(
        [1, $current - 1, $current, $current + 1, $pg['pages']],
        fn($n) => $n >= 1 && $n <= $pg['pages']
    ));
    sort($window);
    $base = 'inline-flex h-10 min-w-10 items-center justify-center rounded-full px-3 text-sm font-medium transition'; ?>
    <nav class="mt-10 flex items-center justify-center gap-1.5" aria-label="Pagination">
        <?php if ($current > 1): ?>
            <a href="<?= e(url_with(['p' => $current - 1])) ?>" class="<?= $base ?> text-stone-600 hover:bg-white" aria-label="Previous page"><?= icon('arrow-left', 'h-4 w-4') ?></a>
        <?php endif; ?>
        <?php $previous = 0; foreach ($window as $n): ?>
            <?php if ($n - $previous > 1): ?><span class="px-1 text-stone-400">…</span><?php endif; ?>
            <?php if ($n === $current): ?>
                <span class="<?= $base ?> bg-stone-900 text-white" aria-current="page"><?= $n ?></span>
            <?php else: ?>
                <a href="<?= e(url_with(['p' => $n])) ?>" class="<?= $base ?> text-stone-700 hover:bg-white"><?= $n ?></a>
            <?php endif; ?>
        <?php $previous = $n; endforeach; ?>
        <?php if ($current < $pg['pages']): ?>
            <a href="<?= e(url_with(['p' => $current + 1])) ?>" class="<?= $base ?> text-stone-600 hover:bg-white" aria-label="Next page"><?= icon('arrow-right', 'h-4 w-4') ?></a>
        <?php endif; ?>
    </nav>
<?php }

function wishlist_button(int $productId, string $class = ''): void {
    $saved = in_array($productId, wishlist_ids(), true); ?>
    <?= form_open('wishlist_toggle', ['class' => $class]) ?>
        <input type="hidden" name="product_id" value="<?= $productId ?>">
        <button type="submit"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-white/95 shadow-sm ring-1 ring-stone-200 transition hover:scale-105 <?= $saved ? 'text-brand-600' : 'text-stone-500 hover:text-brand-600' ?>"
            aria-label="<?= $saved ? 'Remove from wishlist' : 'Save to wishlist' ?>" title="<?= $saved ? 'Saved to wishlist' : 'Save to wishlist' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="<?= $saved ? 'currentColor' : 'none' ?>" class="h-5 w-5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
        </button>
    </form>
<?php }

function price_tag(array $product, string $size = 'text-base'): void { ?>
    <span class="<?= $size ?> font-semibold text-stone-900"><?= money($product['price_cents']) ?></span>
    <?php if (discount_percent($product)): ?>
        <span class="text-xs text-stone-400 line-through"><?= money($product['compare_at_cents']) ?></span>
    <?php endif;
}

function product_card(array $p): void {
    $href = url('product', ['id' => $p['id']]);
    $stock = (int) $p['stock'];
    $discount = discount_percent($p); ?>
    <article class="group relative flex flex-col overflow-hidden rounded-2xl border border-stone-200/80 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:shadow-lg">
        <a href="<?= e($href) ?>" class="relative block aspect-square overflow-hidden bg-stone-100">
            <img src="<?= e(image_url($p['image_path'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute left-3 top-3 flex flex-col items-start gap-1.5">
                <?php if ($discount): ?><span class="chip bg-brand-600 text-white">-<?= $discount ?>%</span><?php endif; ?>
                <?php if ($stock <= 0): ?>
                    <span class="chip bg-stone-900/85 text-white">Sold out</span>
                <?php elseif ($stock <= LOW_STOCK_THRESHOLD): ?>
                    <span class="chip bg-white/95 text-stone-800 ring-1 ring-stone-200">Only <?= $stock ?> left</span>
                <?php endif; ?>
            </span>
        </a>
        <?php if (is_buyer()) wishlist_button((int) $p['id'], 'absolute right-3 top-3'); ?>
        <div class="flex flex-1 flex-col p-4">
            <?php if ($p['store_name']): ?>
                <a href="<?= e(url('store', ['s' => $p['store_slug']])) ?>" class="truncate text-xs font-medium text-stone-500 hover:text-brand-700"><?= e($p['store_name']) ?></a>
            <?php endif; ?>
            <h3 class="mt-1 line-clamp-2 text-sm font-semibold leading-snug text-stone-900">
                <a href="<?= e($href) ?>" class="hover:text-brand-700"><?= e($p['name']) ?></a>
            </h3>
            <?php if ((int) $p['review_count'] > 0): ?>
                <div class="mt-1.5 flex items-center gap-1 text-xs text-stone-500">
                    <?= stars($p['avg_rating'], 'h-3.5 w-3.5') ?><span>(<?= (int) $p['review_count'] ?>)</span>
                </div>
            <?php endif; ?>
            <div class="mt-auto flex items-end justify-between gap-2 pt-3">
                <div class="flex flex-wrap items-baseline gap-x-2"><?php price_tag($p); ?></div>
                <?php if (!is_seller() && $stock > 0): ?>
                    <?= form_open('cart_add') ?>
                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-900 text-white transition hover:bg-brand-600" aria-label="Add <?= e($p['name']) ?> to cart" title="Add to cart">
                            <?= icon('plus', 'h-4 w-4') ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </article>
<?php }

function product_grid(array $products, string $cols = 'sm:grid-cols-2 lg:grid-cols-4'): void { ?>
    <div class="grid grid-cols-2 gap-4 sm:gap-6 <?= $cols ?>">
        <?php foreach ($products as $product) product_card($product); ?>
    </div>
<?php }

function address_fields(array $values = [], string $prefix = '', string $idPrefix = ''): void {
    $field = fn($name) => old($prefix . $name, (string) ($values[$name] ?? '')); ?>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>full_name">Full name</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>full_name" name="<?= $prefix ?>full_name" value="<?= e($field('full_name')) ?>" autocomplete="name" required>
        </div>
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>phone">Phone</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>phone" name="<?= $prefix ?>phone" value="<?= e($field('phone')) ?>" autocomplete="tel" required>
        </div>
        <div class="sm:col-span-2">
            <label class="label" for="<?= $idPrefix . $prefix ?>line1">Address</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>line1" name="<?= $prefix ?>line1" value="<?= e($field('line1')) ?>" placeholder="House number and street" autocomplete="address-line1" required>
        </div>
        <div class="sm:col-span-2">
            <input class="input" name="<?= $prefix ?>line2" value="<?= e($field('line2')) ?>" placeholder="Apartment, suite, landmark (optional)" autocomplete="address-line2" aria-label="Address line 2">
        </div>
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>city">City</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>city" name="<?= $prefix ?>city" value="<?= e($field('city')) ?>" autocomplete="address-level2" required>
        </div>
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>state">State / region</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>state" name="<?= $prefix ?>state" value="<?= e($field('state')) ?>" autocomplete="address-level1">
        </div>
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>postal_code">Postal code</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>postal_code" name="<?= $prefix ?>postal_code" value="<?= e($field('postal_code')) ?>" autocomplete="postal-code" required>
        </div>
        <div>
            <label class="label" for="<?= $idPrefix . $prefix ?>country">Country</label>
            <input class="input" id="<?= $idPrefix . $prefix ?>country" name="<?= $prefix ?>country" value="<?= e($field('country')) ?>" autocomplete="country-name" required>
        </div>
    </div>
<?php }
