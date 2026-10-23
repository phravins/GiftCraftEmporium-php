<?php
$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND buyer_id = ?');
$stmt->execute([query_int('id'), $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    abort(404, 'We could not find that order.');
}
$page_title = 'Order ' . $order['order_number'];
$packages = packages_for_orders([$order['id']])[(int) $order['id']] ?? [];
$status = overall_order_status(array_column($packages, 'status'));
$steps = ['pending' => 'Placed', 'processing' => 'Preparing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
?>

<a href="<?= e(url('orders')) ?>" class="inline-flex items-center gap-1.5 text-sm text-stone-600 hover:text-stone-900"><?= icon('arrow-left', 'h-4 w-4') ?> All orders</a>
<div class="mt-3 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Order <?= e($order['order_number']) ?></h1>
        <p class="mt-1 text-sm text-stone-500">Placed on <?= e(fmt_date($order['created_at'], 'F j, Y \a\t g:i a')) ?></p>
    </div>
    <?= status_badge($status) ?>
</div>

<div class="mt-8 grid gap-8 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <?php foreach ($packages as $index => $package):
            $stepKeys = array_keys($steps);
            $reached = $package['status'] === 'cancelled' ? -1 : array_search($package['status'], $stepKeys, true); ?>
            <section class="card overflow-hidden">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 bg-stone-50/70 px-5 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500">Package <?= $index + 1 ?> of <?= count($packages) ?></p>
                        <a href="<?= e(url('store', ['s' => $package['store_slug']])) ?>" class="mt-0.5 flex items-center gap-1.5 font-semibold text-stone-900 hover:text-brand-700"><?= icon('store', 'h-4 w-4') ?> <?= e($package['store_name']) ?></a>
                    </div>
                    <?= status_badge($package['status']) ?>
                </header>

                <div class="px-5 py-5">
                    <?php if ($package['status'] === 'cancelled'): ?>
                        <p class="flex items-center gap-2 rounded-xl bg-stone-100 px-4 py-3 text-sm text-stone-700"><?= icon('x-circle') ?> This package was cancelled. You will not be charged for it.</p>
                    <?php else: ?>
                        <ol class="grid grid-cols-4 gap-2" aria-label="Delivery progress">
                            <?php foreach ($stepKeys as $i => $key): $done = $i <= $reached; ?>
                                <li class="text-center">
                                    <span class="block h-1.5 rounded-full <?= $done ? 'bg-brand-600' : 'bg-stone-200' ?>"></span>
                                    <span class="mt-2 block text-xs <?= $done ? 'font-semibold text-stone-900' : 'text-stone-400' ?>"><?= $steps[$key] ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>

                    <?php if ($package['tracking_number'] || $package['carrier']): ?>
                        <p class="mt-4 flex flex-wrap items-center gap-2 rounded-xl bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
                            <?= icon('truck') ?>
                            <span><?= e($package['carrier'] ?: 'Carrier') ?></span>
                            <?php if ($package['tracking_number']): ?>· Tracking <span class="font-mono font-semibold"><?= e($package['tracking_number']) ?></span><?php endif; ?>
                        </p>
                    <?php endif; ?>

                    <ul class="mt-5 divide-y divide-stone-100">
                        <?php foreach ($package['items'] as $item): ?>
                            <li class="flex items-center gap-4 py-3">
                                <img src="<?= e(image_url($item['image_path'])) ?>" alt="" class="h-16 w-16 rounded-lg object-cover ring-1 ring-stone-200">
                                <div class="min-w-0 flex-1">
                                    <?php if ($item['product_id']): ?>
                                        <a href="<?= e(url('product', ['id' => $item['product_id']])) ?>" class="line-clamp-1 font-medium text-stone-900 hover:text-brand-700"><?= e($item['product_name']) ?></a>
                                    <?php else: ?>
                                        <p class="line-clamp-1 font-medium text-stone-900"><?= e($item['product_name']) ?></p>
                                    <?php endif; ?>
                                    <p class="text-sm text-stone-500"><?= (int) $item['quantity'] ?> × <?= money($item['unit_price_cents']) ?></p>
                                    <?php if ($package['status'] === 'delivered' && $item['product_id']): ?>
                                        <a href="<?= e(url('product', ['id' => $item['product_id']])) ?>#reviews" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-brand-700 hover:underline"><?= star_icon('h-3.5 w-3.5', true) ?> Rate this gift</a>
                                    <?php endif; ?>
                                </div>
                                <p class="font-medium text-stone-900"><?= money($item['line_total_cents']) ?></p>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <details class="mt-4 group">
                        <summary class="flex cursor-pointer list-none items-center gap-1 text-sm font-medium text-stone-600 hover:text-stone-900">
                            <?= icon('clock', 'h-4 w-4') ?> Package history <?= icon('chevron-down', 'h-4 w-4 transition group-open:rotate-180') ?>
                        </summary>
                        <ol class="mt-4 space-y-4 border-l-2 border-stone-200 pl-5">
                            <?php foreach (array_reverse($package['events']) as $event): ?>
                                <li class="relative">
                                    <span class="absolute -left-[1.6rem] top-1 h-3 w-3 rounded-full border-2 border-white bg-brand-600 ring-1 ring-brand-200"></span>
                                    <p class="text-sm font-medium text-stone-900"><?= e(status_meta($event['status'])[0]) ?></p>
                                    <?php if ($event['note']): ?><p class="text-sm text-stone-600"><?= e($event['note']) ?></p><?php endif; ?>
                                    <p class="text-xs text-stone-400"><?= e(fmt_date($event['created_at'], 'M j, Y · g:i a')) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </details>
                </div>

                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 px-5 py-4 text-sm">
                    <span class="text-stone-600">Package total <span class="font-semibold text-stone-900"><?= money($package['total_cents']) ?></span>
                        <span class="text-stone-400">(incl. <?= $package['shipping_cents'] ? money($package['shipping_cents']) . ' shipping' : 'free shipping' ?>)</span></span>
                    <?php if (in_array('cancelled', SELLER_ORDER_FLOW[$package['status']], true)): ?>
                        <?= form_open('order_cancel', ['data-confirm' => 'Cancel this package from ' . $package['store_name'] . '?']) ?>
                            <input type="hidden" name="seller_order_id" value="<?= (int) $package['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm"><?= icon('x-circle', 'h-4 w-4') ?> Cancel package</button>
                        </form>
                    <?php endif; ?>
                </footer>
            </section>
        <?php endforeach; ?>
    </div>

    <aside class="space-y-6">
        <div class="card p-6">
            <h2 class="font-semibold text-stone-900">Summary</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-stone-600">Subtotal</dt><dd><?= money($order['subtotal_cents']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Shipping</dt><dd><?= $order['shipping_cents'] ? money($order['shipping_cents']) : 'Free' ?></dd></div>
                <div class="flex justify-between border-t border-stone-200 pt-2 text-base font-semibold"><dt>Total</dt><dd><?= money($order['total_cents']) ?></dd></div>
            </dl>
            <p class="mt-4 flex items-center gap-2 text-sm text-stone-600"><?= icon('cash', 'h-4 w-4') ?> <?= e(PAYMENT_METHODS[$order['payment_method']] ?? $order['payment_method']) ?></p>
        </div>
        <div class="card p-6">
            <h2 class="flex items-center gap-2 font-semibold text-stone-900"><?= icon('map-pin', 'h-4 w-4') ?> Shipping to</h2>
            <p class="mt-3 text-sm font-medium text-stone-900"><?= e($order['ship_name']) ?></p>
            <p class="mt-1 whitespace-pre-line text-sm text-stone-600"><?= e(format_address($order)) ?></p>
            <p class="mt-1 text-sm text-stone-500"><?= e($order['ship_phone']) ?></p>
        </div>
        <?php if ($order['notes']): ?>
            <div class="card p-6">
                <h2 class="font-semibold text-stone-900">Your note</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-stone-600"><?= e($order['notes']) ?></p>
            </div>
        <?php endif; ?>
    </aside>
</div>
