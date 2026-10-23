<?php
$page_title = 'Checkout';
$summary = summarize_cart(cart_lines((int) $user['id']));
if (!$summary['groups']) {
    flash('info', 'Your cart is empty.');
    redirect(url('cart'));
}
if ($summary['has_issues']) {
    flash('error', 'Some items in your cart need attention before checkout.');
    redirect(url('cart'));
}

$stmt = db()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
$stmt->execute([$user['id']]);
$addresses = $stmt->fetchAll();
$selected = old('address_id', $addresses ? (string) $addresses[0]['id'] : 'new');
$newAddressDefaults = ['full_name' => $user['name'], 'phone' => $user['phone']];
?>

<a href="<?= e(url('cart')) ?>" class="inline-flex items-center gap-1.5 text-sm text-stone-600 hover:text-stone-900"><?= icon('arrow-left', 'h-4 w-4') ?> Back to cart</a>
<h1 class="page-title mt-3">Checkout</h1>

<?= form_open('place_order', ['class' => 'mt-8 grid gap-8 lg:grid-cols-3', 'data-checkout' => ''], url('checkout')) ?>
    <div class="space-y-6 lg:col-span-2">
        <section class="card p-6">
            <h2 class="flex items-center gap-2 font-semibold text-stone-900"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-stone-900 text-xs text-white">1</span> Shipping address</h2>
            <div class="mt-5 space-y-3">
                <?php foreach ($addresses as $address): ?>
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-stone-200 p-4 transition hover:border-stone-300 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="address_id" value="<?= (int) $address['id'] ?>" class="radio mt-0.5" <?= $selected === (string) $address['id'] ? 'checked' : '' ?> data-address-choice>
                        <span class="text-sm">
                            <span class="font-semibold text-stone-900"><?= e($address['full_name']) ?></span>
                            <?php if ($address['is_default']): ?><span class="chip ml-2 bg-stone-100 text-stone-600">Default</span><?php endif; ?>
                            <span class="mt-1 block text-stone-600"><?= e(format_address([
                                'ship_line1' => $address['line1'], 'ship_line2' => $address['line2'], 'ship_city' => $address['city'],
                                'ship_state' => $address['state'], 'ship_postal_code' => $address['postal_code'], 'ship_country' => $address['country'],
                            ], ', ')) ?></span>
                            <span class="mt-0.5 block text-stone-500"><?= e($address['phone']) ?></span>
                        </span>
                    </label>
                <?php endforeach; ?>

                <?php if ($addresses): ?>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-dashed border-stone-300 p-4 text-sm font-medium text-stone-700 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/50">
                        <input type="radio" name="address_id" value="new" class="radio" <?= $selected === 'new' ? 'checked' : '' ?> data-address-choice>
                        <?= icon('plus', 'h-4 w-4') ?> Ship to a new address
                    </label>
                <?php else: ?>
                    <input type="hidden" name="address_id" value="new">
                <?php endif; ?>

                <fieldset class="<?= $addresses && $selected !== 'new' ? 'hidden' : '' ?> pt-2" data-new-address <?= $addresses && $selected !== 'new' ? 'disabled' : '' ?>>
                    <?php address_fields($newAddressDefaults, 'new_'); ?>
                    <label class="mt-4 flex items-center gap-2.5 text-sm text-stone-700">
                        <input type="checkbox" name="save_address" value="1" class="checkbox" checked> Save this address for next time
                    </label>
                </fieldset>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="flex items-center gap-2 font-semibold text-stone-900"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-stone-900 text-xs text-white">2</span> Payment</h2>
            <div class="mt-5 space-y-3">
                <?php foreach (PAYMENT_METHODS as $value => $label): ?>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-brand-600 bg-brand-50/50 p-4">
                        <input type="radio" name="payment_method" value="<?= $value ?>" class="radio" checked>
                        <span class="text-brand-700"><?= icon('cash') ?></span>
                        <span class="text-sm">
                            <span class="block font-semibold text-stone-900"><?= e($label) ?></span>
                            <span class="block text-stone-500">Pay each seller when their package arrives.</span>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="card p-6">
            <h2 class="flex items-center gap-2 font-semibold text-stone-900"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-stone-900 text-xs text-white">3</span> Gift note or delivery instructions <span class="font-normal text-stone-400">(optional)</span></h2>
            <textarea name="notes" maxlength="500" class="input mt-5 min-h-24" placeholder="e.g. Please gift wrap - it's a birthday present!"><?= e(old('notes')) ?></textarea>
        </section>
    </div>

    <aside>
        <div class="card sticky top-36 p-6">
            <h2 class="font-semibold text-stone-900">Your order</h2>
            <div class="mt-5 space-y-5">
                <?php foreach ($summary['groups'] as $group): ?>
                    <div>
                        <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-stone-500"><?= icon('store', 'h-3.5 w-3.5') ?> <?= e($group['store_name']) ?></p>
                        <ul class="mt-2 space-y-2">
                            <?php foreach ($group['lines'] as $line): ?>
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="relative shrink-0">
                                        <img src="<?= e(image_url($line['image_path'])) ?>" alt="" class="h-12 w-12 rounded-lg object-cover ring-1 ring-stone-200">
                                        <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-stone-700 px-1 text-[11px] font-semibold text-white"><?= (int) $line['quantity'] ?></span>
                                    </span>
                                    <span class="line-clamp-2 flex-1 text-stone-700"><?= e($line['name']) ?></span>
                                    <span class="font-medium"><?= money($line['line_total']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-2 flex justify-between text-xs text-stone-500"><span>Shipping</span><span><?= $group['shipping'] ? money($group['shipping']) : 'Free' ?></span></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <dl class="mt-5 space-y-2 border-t border-stone-200 pt-5 text-sm">
                <div class="flex justify-between"><dt class="text-stone-600">Subtotal</dt><dd><?= money($summary['subtotal']) ?></dd></div>
                <div class="flex justify-between"><dt class="text-stone-600">Shipping</dt><dd><?= $summary['shipping'] ? money($summary['shipping']) : 'Free' ?></dd></div>
                <div class="flex justify-between pt-2 text-base font-semibold"><dt>Total</dt><dd><?= money($summary['total']) ?></dd></div>
            </dl>
            <button type="submit" class="btn btn-primary btn-lg mt-6 w-full">Place order · <?= money($summary['total']) ?></button>
            <p class="mt-3 text-center text-xs text-stone-500">You'll pay on delivery. You can cancel any package until it ships.</p>
        </div>
    </aside>
</form>
