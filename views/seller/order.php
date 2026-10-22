<?php
$stmt = db()->prepare('SELECT so.*, o.order_number, o.payment_method, o.notes, o.ship_name, o.ship_phone, o.ship_line1, o.ship_line2,
        o.ship_city, o.ship_state, o.ship_postal_code, o.ship_country, u.email AS buyer_email
    FROM seller_orders so JOIN orders o ON o.id = so.order_id JOIN users u ON u.id = o.buyer_id
    WHERE so.id = ? AND so.seller_id = ?');
$stmt->execute([query_int('id'), $user['id']]);
$package = $stmt->fetch();
if (!$package) {
    abort(404, 'We could not find that order.');
}
$page_title = 'Order ' . $package['order_number'];
$details = packages_for_orders([$package['order_id']], (int) $user['id'])[(int) $package['order_id']][0];
$next = SELLER_ORDER_FLOW[$package['status']];
?>

<a href="<?= e(url('seller_orders')) ?>" class="inline-flex items-center gap-1.5 text-sm text-stone-600 hover:text-stone-900"><?= icon('arrow-left', 'h-4 w-4') ?> Orders</a>
<div class="mt-3 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="page-title">Order <?= e($package['order_number']) ?></h1>
        <p class="mt-1 text-sm text-stone-500">Placed <?= e(fmt_date($package['created_at'], 'F j, Y \a\t g:i a')) ?></p>
    </div>
    <?= status_badge($package['status']) ?>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <?php if ($next): ?>
            <section class="card border-brand-200 p-6">
                <h2 class="font-semibold text-stone-900">Next step</h2>
                <?php if ($package['status'] === 'pending'): ?>
                    <p class="mt-1 text-sm text-stone-600">Confirm the order to let the buyer know you're preparing it.</p>
                    <?= form_open('seller_order_update', ['class' => 'mt-4']) ?>
                        <input type="hidden" name="seller_order_id" value="<?= (int) $package['id'] ?>">
                        <input type="hidden" name="status" value="processing">
                        <button type="submit" class="btn btn-primary"><?= icon('check', 'h-4 w-4') ?> Accept &amp; start preparing</button>
                    </form>
                <?php elseif ($package['status'] === 'processing'): ?>
                    <p class="mt-1 text-sm text-stone-600">Add shipping details when the package leaves your studio.</p>
                    <?= form_open('seller_order_update', ['class' => 'mt-4 grid gap-4 sm:grid-cols-2']) ?>
                        <input type="hidden" name="seller_order_id" value="<?= (int) $package['id'] ?>">
                        <input type="hidden" name="status" value="shipped">
                        <div>
                            <label class="label" for="carrier">Carrier</label>
                            <input class="input" id="carrier" name="carrier" maxlength="60" placeholder="e.g. India Post, DHL" value="<?= e(old('carrier')) ?>">
                        </div>
                        <div>
                            <label class="label" for="tracking_number">Tracking number</label>
                            <input class="input" id="tracking_number" name="tracking_number" maxlength="80" value="<?= e(old('tracking_number')) ?>">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn btn-primary"><?= icon('truck', 'h-4 w-4') ?> Mark as shipped</button>
                        </div>
                    </form>
                <?php elseif ($package['status'] === 'shipped'): ?>
                    <p class="mt-1 text-sm text-stone-600">Once the buyer has the package (and you've collected payment on delivery), mark it delivered.</p>
                    <?= form_open('seller_order_update', ['class' => 'mt-4']) ?>
                        <input type="hidden" name="seller_order_id" value="<?= (int) $package['id'] ?>">
                        <input type="hidden" name="status" value="delivered">
                        <button type="submit" class="btn btn-primary"><?= icon('check-circle', 'h-4 w-4') ?> Mark as delivered</button>
                    </form>
                <?php endif; ?>

                <?php if (in_array('cancelled', $next, true)): ?>
                    <details class="mt-6 border-t border-stone-100 pt-4">
                        <summary class="cursor-pointer text-sm font-medium text-red-700 hover:underline">Cancel this order</summary>
                        <?= form_open('seller_order_update', ['class' => 'mt-4 space-y-3', 'data-confirm' => 'Cancel this order? Items will be returned to stock.']) ?>
                            <input type="hidden" name="seller_order_id" value="<?= (int) $package['id'] ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <label class="label" for="note">Reason shown to the buyer</label>
                            <textarea class="input" id="note" name="note" maxlength="300" required placeholder="e.g. Sorry - this item was damaged and can't be replaced."></textarea>
                            <button type="submit" class="btn btn-danger btn-sm">Cancel order</button>
                        </form>
                    </details>
                <?php endif; ?>
            </section>
        <?php endif; ?>

        <section class="card">
            <h2 class="border-b border-stone-100 px-6 py-4 font-semibold text-stone-900">Items</h2>
            <ul class="divide-y divide-stone-100">
                <?php foreach ($details['items'] as $item): ?>
                    <li class="flex items-center gap-4 px-6 py-4">
                        <img src="<?= e(image_url($item['image_path'])) ?>" alt="" class="h-14 w-14 rounded-lg object-cover ring-1 ring-stone-200">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-stone-900"><?= e($item['product_name']) ?></p>
                            <p class="text-sm text-stone-500"><?= (int) $item['quantity'] ?> × <?= money($item['unit_price_cents']) ?></p>
                        </div>
                        <p class="font-semibold"><?= money($item['line_total_cents']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
            <dl class="space-y-2 border-t border-stone-100 px-6 py-4 text-sm">
                <div class="flex justify-between"><dt class="text-stone-600">Subtotal</dt><dd><?= money($package['subtotal_cents']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Shipping charged</dt><dd><?= $package['shipping_cents'] ? money($package['shipping_cents']) : 'Free' ?></dd></div>
                <div class="flex justify-between text-base font-semibold"><dt>Total to collect</dt><dd><?= money($package['total_cents']) ?></dd></div>
            </dl>
        </section>

        <section class="card p-6">
            <h2 class="font-semibold text-stone-900">History</h2>
            <ol class="mt-4 space-y-4 border-l-2 border-stone-200 pl-5">
                <?php foreach (array_reverse($details['events']) as $event): ?>
                    <li class="relative">
                        <span class="absolute -left-[1.6rem] top-1 h-3 w-3 rounded-full border-2 border-white bg-brand-600 ring-1 ring-brand-200"></span>
                        <p class="text-sm font-medium text-stone-900"><?= e(status_meta($event['status'])[0]) ?> <span class="font-normal text-stone-400">· by <?= e($event['actor_role']) ?></span></p>
                        <?php if ($event['note']): ?><p class="text-sm text-stone-600"><?= e($event['note']) ?></p><?php endif; ?>
                        <p class="text-xs text-stone-400"><?= e(fmt_date($event['created_at'], 'M j, Y · g:i a')) ?></p>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    </div>

    <aside class="space-y-6">
        <section class="card p-6">
            <h2 class="flex items-center gap-2 font-semibold text-stone-900"><?= icon('map-pin', 'h-4 w-4') ?> Ship to</h2>
            <p class="mt-3 text-sm font-medium text-stone-900"><?= e($package['ship_name']) ?></p>
            <p class="mt-1 whitespace-pre-line text-sm text-stone-600"><?= e(format_address($package)) ?></p>
            <div class="mt-4 space-y-1 border-t border-stone-100 pt-4 text-sm">
                <p><span class="text-stone-500">Phone:</span> <a href="tel:<?= e($package['ship_phone']) ?>" class="link"><?= e($package['ship_phone']) ?></a></p>
                <p class="truncate"><span class="text-stone-500">Email:</span> <a href="mailto:<?= e($package['buyer_email']) ?>" class="link"><?= e($package['buyer_email']) ?></a></p>
            </div>
        </section>
        <section class="card p-6 text-sm">
            <h2 class="font-semibold text-stone-900">Payment</h2>
            <p class="mt-2 flex items-center gap-2 text-stone-600"><?= icon('cash', 'h-4 w-4') ?> <?= e(PAYMENT_METHODS[$package['payment_method']] ?? $package['payment_method']) ?></p>
        </section>
        <?php if ($package['tracking_number'] || $package['carrier']): ?>
            <section class="card p-6 text-sm">
                <h2 class="font-semibold text-stone-900">Shipment</h2>
                <p class="mt-2 text-stone-600"><?= e($package['carrier'] ?: 'Carrier not set') ?></p>
                <?php if ($package['tracking_number']): ?><p class="mt-1 font-mono text-stone-900"><?= e($package['tracking_number']) ?></p><?php endif; ?>
            </section>
        <?php endif; ?>
        <?php if ($package['notes']): ?>
            <section class="card border-amber-200 bg-amber-50/50 p-6 text-sm">
                <h2 class="font-semibold text-stone-900">Note from the buyer</h2>
                <p class="mt-2 whitespace-pre-line text-stone-700"><?= e($package['notes']) ?></p>
            </section>
        <?php endif; ?>
    </aside>
</div>
