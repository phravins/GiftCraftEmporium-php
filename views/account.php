<?php
$page_title = 'Account settings';
$addresses = [];
if ($user['role'] === 'buyer') {
    $stmt = db()->prepare('SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC');
    $stmt->execute([$user['id']]);
    $addresses = $stmt->fetchAll();
}
?>

<h1 class="page-title">Account settings</h1>
<p class="mt-2 text-sm text-stone-500">
    <?= $user['role'] === 'seller' ? 'Seller account' : 'Buyer account' ?> · member since <?= e(fmt_date($user['created_at'], 'F Y')) ?>
</p>

<div class="mt-8 grid gap-8 lg:grid-cols-2">
    <?= form_open('account_update', ['class' => 'card space-y-5 p-6']) ?>
        <div>
            <h2 class="font-semibold text-stone-900">Profile</h2>
            <p class="mt-1 text-sm text-stone-500">Your name and contact details.</p>
        </div>
        <div>
            <label class="label" for="name">Name</label>
            <input class="input" id="name" name="name" value="<?= e(old('name', $user['name'])) ?>" maxlength="80" required>
        </div>
        <div>
            <label class="label" for="email">Email</label>
            <input class="input" type="email" id="email" name="email" value="<?= e(old('email', $user['email'])) ?>" maxlength="120" required>
        </div>
        <div>
            <label class="label" for="phone">Phone</label>
            <input class="input" id="phone" name="phone" value="<?= e(old('phone', (string) $user['phone'])) ?>" maxlength="30">
        </div>
        <button type="submit" class="btn btn-dark">Save profile</button>
    </form>

    <?= form_open('password_update', ['class' => 'card space-y-5 p-6']) ?>
        <div>
            <h2 class="font-semibold text-stone-900">Password</h2>
            <p class="mt-1 text-sm text-stone-500">Use at least 8 characters.</p>
        </div>
        <div>
            <label class="label" for="current_password">Current password</label>
            <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
        </div>
        <div>
            <label class="label" for="password">New password</label>
            <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8" required>
        </div>
        <div>
            <label class="label" for="password_confirm">Confirm new password</label>
            <input class="input" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn-dark">Change password</button>
    </form>
</div>

<?php if ($user['role'] === 'buyer'): ?>
    <section class="mt-12">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="section-title">Addresses</h2>
                <p class="mt-1 text-sm text-stone-500">Saved addresses make checkout faster.</p>
            </div>
        </div>
        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <?php foreach ($addresses as $address): ?>
                <div class="card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="text-sm">
                            <p class="font-semibold text-stone-900"><?= e($address['full_name']) ?>
                                <?php if ($address['is_default']): ?><span class="chip ml-1 bg-stone-100 text-stone-600">Default</span><?php endif; ?></p>
                            <p class="mt-1 text-stone-600"><?= e(format_address([
                                'ship_line1' => $address['line1'], 'ship_line2' => $address['line2'], 'ship_city' => $address['city'],
                                'ship_state' => $address['state'], 'ship_postal_code' => $address['postal_code'], 'ship_country' => $address['country'],
                            ], ', ')) ?></p>
                            <p class="mt-0.5 text-stone-500"><?= e($address['phone']) ?></p>
                        </div>
                        <?= form_open('address_delete', ['data-confirm' => 'Delete this address?']) ?>
                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                            <button type="submit" class="rounded-full p-2 text-stone-400 hover:bg-red-50 hover:text-red-700" aria-label="Delete address"><?= icon('trash', 'h-4 w-4') ?></button>
                        </form>
                    </div>
                    <details class="mt-3">
                        <summary class="cursor-pointer text-sm font-medium text-brand-700 hover:underline">Edit</summary>
                        <?= form_open('address_save', ['class' => 'mt-4 space-y-4']) ?>
                            <input type="hidden" name="address_id" value="<?= (int) $address['id'] ?>">
                            <?php address_fields($address, '', 'addr' . (int) $address['id'] . '-'); ?>
                            <label class="flex items-center gap-2.5 text-sm text-stone-700">
                                <input type="checkbox" name="is_default" value="1" class="checkbox" <?= $address['is_default'] ? 'checked' : '' ?>> Use as default address
                            </label>
                            <button type="submit" class="btn btn-dark btn-sm">Save address</button>
                        </form>
                    </details>
                </div>
            <?php endforeach; ?>

            <details class="card p-5" <?= !$addresses ? 'open' : '' ?>>
                <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-semibold text-stone-900"><?= icon('plus', 'h-4 w-4') ?> Add a new address</summary>
                <?= form_open('address_save', ['class' => 'mt-4 space-y-4']) ?>
                    <?php address_fields(['full_name' => $user['name'], 'phone' => $user['phone']], '', 'addr-new-'); ?>
                    <label class="flex items-center gap-2.5 text-sm text-stone-700">
                        <input type="checkbox" name="is_default" value="1" class="checkbox" <?= !$addresses ? 'checked' : '' ?>> Use as default address
                    </label>
                    <button type="submit" class="btn btn-dark btn-sm">Add address</button>
                </form>
            </details>
        </div>
    </section>
<?php endif; ?>
