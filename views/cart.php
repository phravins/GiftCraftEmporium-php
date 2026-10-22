<?php
$page_title = 'Your cart';
$summary = summarize_cart(cart_lines((int) $user['id']));
?>

<h1 class="page-title">Your cart</h1>

<?php if (!$summary['groups']): ?>
    <div class="mt-8">
        <?php empty_state('bag', 'Your cart is empty', 'Find something special from our makers - it will wait for you here.',
            '<a href="' . e(url('shop')) . '" class="btn btn-primary">Start shopping</a><a href="' . e(url('wishlist')) . '" class="btn btn-outline">View wishlist</a>'); ?>
    </div>
<?php else: ?>
    <p class="mt-2 text-sm text-stone-500"><?= plural($summary['item_count'], 'item') ?> from <?= plural(count($summary['groups']), 'shop') ?></p>

    <div class="mt-8 grid gap-8 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <?php foreach ($summary['groups'] as $group): ?>
                <section class="card overflow-hidden">
                    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-stone-100 bg-stone-50/70 px-5 py-3">
                        <a href="<?= e(url('store', ['s' => $group['store_slug']])) ?>" class="flex items-center gap-2 text-sm font-semibold text-stone-900 hover:text-brand-700">
                            <?= icon('store', 'h-4 w-4') ?> <?= e($group['store_name']) ?>
                        </a>
                        <span class="text-xs text-stone-500">
                            <?php if ($group['shipping'] === 0): ?>
                                <span class="font-medium text-emerald-700">Free shipping</span>
                            <?php else: ?>
                                Add <?= money(FREE_SHIPPING_THRESHOLD_CENTS - $group['subtotal']) ?> more from this shop for free shipping
                            <?php endif; ?>
                        </span>
                    </header>
                    <ul class="divide-y divide-stone-100">
                        <?php foreach ($group['lines'] as $line): ?>
                            <li class="flex gap-4 p-5">
                                <a href="<?= e(url('product', ['id' => $line['product_id']])) ?>" class="shrink-0">
                                    <img src="<?= e(image_url($line['image_path'])) ?>" alt="" class="h-24 w-24 rounded-xl object-cover ring-1 ring-stone-200">
                                </a>
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <a href="<?= e(url('product', ['id' => $line['product_id']])) ?>" class="line-clamp-2 font-medium text-stone-900 hover:text-brand-700"><?= e($line['name']) ?></a>
                                            <p class="mt-1 text-sm text-stone-500"><?= money($line['price_cents']) ?> each</p>
                                        </div>
                                        <p class="font-semibold text-stone-900"><?= money($line['line_total']) ?></p>
                                    </div>
                                    <?php if ($line['issue']): ?>
                                        <p class="mt-2 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('warning', 'h-4 w-4') ?> <?= e($line['issue']) ?></p>
                                    <?php endif; ?>
                                    <div class="mt-auto flex flex-wrap items-center gap-3 pt-3">
                                        <?php if ($line['status'] === 'active' && (int) $line['stock'] > 0): ?>
                                            <?= form_open('cart_update', ['class' => 'flex items-center gap-2']) ?>
                                                <input type="hidden" name="cart_item_id" value="<?= (int) $line['cart_item_id'] ?>">
                                                <label class="sr-only" for="qty-<?= (int) $line['cart_item_id'] ?>">Quantity</label>
                                                <select id="qty-<?= (int) $line['cart_item_id'] ?>" name="quantity" class="input w-20 rounded-full py-1.5" data-autosubmit>
                                                    <?php for ($q = 1; $q <= max((int) $line['quantity'], min((int) $line['stock'], MAX_CART_QTY)); $q++): ?>
                                                        <option value="<?= $q ?>" <?= $q === (int) $line['quantity'] ? 'selected' : '' ?>><?= $q ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                                <noscript><button class="btn btn-outline btn-sm">Update</button></noscript>
                                            </form>
                                        <?php endif; ?>
                                        <?= form_open('cart_remove') ?>
                                            <input type="hidden" name="cart_item_id" value="<?= (int) $line['cart_item_id'] ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 text-sm text-stone-500 hover:text-red-700"><?= icon('trash', 'h-4 w-4') ?> Remove</button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>

        <aside>
            <div class="card sticky top-36 p-6">
                <h2 class="font-semibold text-stone-900">Order summary</h2>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-stone-600">Subtotal</dt><dd class="font-medium"><?= money($summary['subtotal']) ?></dd></div>
                    <div class="flex justify-between">
                        <dt class="text-stone-600">Shipping <span class="text-stone-400">(<?= plural(count($summary['groups']), 'package') ?>)</span></dt>
                        <dd class="font-medium"><?= $summary['shipping'] ? money($summary['shipping']) : 'Free' ?></dd>
                    </div>
                    <div class="flex justify-between border-t border-stone-200 pt-3 text-base"><dt class="font-semibold">Total</dt><dd class="font-semibold"><?= money($summary['total']) ?></dd></div>
                </dl>
                <?php if ($summary['has_issues']): ?>
                    <p class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">Some items need your attention before you can check out.</p>
                    <button class="btn btn-primary btn-lg mt-4 w-full" disabled>Checkout</button>
                <?php else: ?>
                    <a href="<?= e(url('checkout')) ?>" class="btn btn-primary btn-lg mt-6 w-full">Checkout <?= icon('arrow-right', 'h-5 w-5') ?></a>
                <?php endif; ?>
                <p class="mt-4 flex items-center justify-center gap-2 text-xs text-stone-500"><?= icon('cash', 'h-4 w-4') ?> Pay on delivery</p>
            </div>
            <a href="<?= e(url('shop')) ?>" class="mt-4 flex items-center justify-center gap-1.5 text-sm text-stone-600 hover:text-stone-900"><?= icon('arrow-left', 'h-4 w-4') ?> Continue shopping</a>
        </aside>
    </div>
<?php endif; ?>
