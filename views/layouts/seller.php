<?php
$store = seller_store((int) $user['id']);
$openStmt = db()->prepare("SELECT COUNT(*) FROM seller_orders WHERE seller_id = ? AND status IN ('pending', 'processing')");
$openStmt->execute([$user['id']]);
$openOrders = (int) $openStmt->fetchColumn();

$sellerNav = [
    'seller_dashboard' => ['Dashboard', 'grid', ['seller_dashboard']],
    'seller_products'  => ['Products', 'tag', ['seller_products', 'seller_product']],
    'seller_orders'    => ['Orders', 'clipboard', ['seller_orders', 'seller_order']],
    'seller_reviews'   => ['Reviews', 'chat', ['seller_reviews']],
    'seller_settings'  => ['Store settings', 'store', ['seller_settings']],
    'account'          => ['Account', 'user', ['account']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body class="bg-stone-50">
<div class="min-h-screen">
    <div class="fixed inset-0 z-30 hidden bg-stone-900/40 lg:hidden" data-sidebar-backdrop></div>
    <aside id="seller-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-stone-200 bg-white transition-transform lg:translate-x-0">
        <div class="flex items-center gap-2.5 px-5 py-5">
            <a href="index.php" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white"><?= icon('gift', 'h-5 w-5') ?></span>
                <span>
                    <span class="block font-display text-lg font-semibold leading-tight text-stone-900">GiftCraft</span>
                    <span class="block text-[11px] font-semibold uppercase tracking-[0.18em] text-brand-700">Seller Hub</span>
                </span>
            </a>
        </div>

        <?php if ($store): ?>
            <div class="mx-4 mb-4 flex items-center gap-3 rounded-xl bg-stone-50 p-3">
                <?php if ($store['logo_path']): ?>
                    <img src="<?= e($store['logo_path']) ?>" alt="" class="h-10 w-10 rounded-lg object-cover">
                <?php else: ?>
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-100 font-display text-lg font-semibold text-brand-800"><?= e(strtoupper(str_cut($store['name'], 1))) ?></span>
                <?php endif; ?>
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-stone-900"><?= e($store['name']) ?></p>
                    <a href="<?= e(url('store', ['s' => $store['slug']])) ?>" class="text-xs text-brand-700 hover:underline">View storefront</a>
                </div>
            </div>
        <?php endif; ?>

        <nav class="flex-1 space-y-1 px-3" aria-label="Seller">
            <?php foreach ($sellerNav as $target => [$label, $iconName, $activeFor]):
                $active = in_array($page, $activeFor, true); ?>
                <a href="<?= e(url($target)) ?>"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition <?= $active ? 'bg-stone-900 text-white' : 'text-stone-600 hover:bg-stone-100 hover:text-stone-900' ?>"
                    <?= $active ? 'aria-current="page"' : '' ?>>
                    <?= icon($iconName, 'h-5 w-5') ?>
                    <span class="flex-1"><?= e($label) ?></span>
                    <?php if ($target === 'seller_orders' && $openOrders > 0): ?>
                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $active ? 'bg-white/20 text-white' : 'bg-brand-100 text-brand-800' ?>"><?= $openOrders ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-stone-200 p-3">
            <a href="index.php" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-600 hover:bg-stone-100"><?= icon('arrow-left', 'h-5 w-5') ?> Back to marketplace</a>
            <?= form_open('logout', [], 'index.php') ?>
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-600 hover:bg-stone-100"><?= icon('logout', 'h-5 w-5') ?> Sign out</button>
            </form>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex items-center gap-3 border-b border-stone-200 bg-white/90 px-4 py-3 backdrop-blur sm:px-6 lg:px-10">
            <button type="button" class="-ml-1 rounded-full p-2 text-stone-700 hover:bg-stone-100 lg:hidden" data-sidebar-open aria-label="Open navigation"><?= icon('menu', 'h-6 w-6') ?></button>
            <p class="truncate text-sm text-stone-500">Signed in as <span class="font-medium text-stone-800"><?= e($user['name']) ?></span></p>
            <a href="<?= e(url('seller_product')) ?>" class="btn btn-primary btn-sm ml-auto"><?= icon('plus', 'h-4 w-4') ?> Add product</a>
        </header>
        <main id="main" class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-10">
            <?php flash_messages(); ?>
            <?= $content ?>
        </main>
    </div>
</div>
</body>
</html>
