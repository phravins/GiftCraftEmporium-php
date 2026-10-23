<?php
$cartCount = is_buyer() ? cart_count((int) $user['id']) : 0;
$navCategories = categories_with_products(10);
$searchQuery = $page === 'shop' ? query('q') : '';
$navLink = 'rounded-full px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-900/5 hover:text-stone-900';
$menuLink = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-stone-700 hover:bg-stone-100';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body class="flex min-h-screen flex-col">
<a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-full focus:bg-white focus:px-4 focus:py-2 focus:shadow">Skip to content</a>

<header class="sticky top-0 z-40 border-b border-stone-200/70 bg-cream/90 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center gap-2 px-4 py-3 sm:gap-4 sm:px-6 lg:px-8">
        <button type="button" class="-ml-2 rounded-full p-2 text-stone-700 hover:bg-stone-900/5 lg:hidden" data-toggle="#mobile-menu" aria-label="Open menu" aria-expanded="false">
            <?= icon('menu', 'h-6 w-6') ?>
        </button>
        <a href="index.php" class="flex shrink-0 items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm"><?= icon('gift', 'h-5 w-5') ?></span>
            <span class="font-display text-xl font-semibold tracking-tight text-stone-900">GiftCraft<span class="hidden text-brand-700 sm:inline"> Emporium</span></span>
        </a>

        <form action="index.php" method="get" class="mx-4 hidden max-w-xl flex-1 md:block" role="search">
            <input type="hidden" name="page" value="shop">
            <label class="relative block">
                <span class="sr-only">Search</span>
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-stone-400"><?= icon('search', 'h-4 w-4') ?></span>
                <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search gifts, makers and categories"
                    class="input rounded-full border-stone-200 pl-10">
            </label>
        </form>

        <nav class="ml-auto flex items-center gap-1" aria-label="Main">
            <a href="<?= e(url('shop')) ?>" class="hidden lg:inline-flex <?= $navLink ?>">Shop</a>
            <a href="<?= e(url('gallery')) ?>" class="hidden lg:inline-flex <?= $navLink ?>">3D Gallery</a>

            <?php if (!$user): ?>
                <a href="<?= e(url('register', ['role' => 'seller'])) ?>" class="hidden lg:inline-flex <?= $navLink ?>">Sell on GiftCraft</a>
                <a href="<?= e(url('login')) ?>" class="<?= $navLink ?>">Sign in</a>
                <a href="<?= e(url('register')) ?>" class="btn btn-dark btn-sm hidden sm:inline-flex">Create account</a>
            <?php else: ?>
                <?php if ($user['role'] === 'buyer'): ?>
                    <a href="<?= e(url('wishlist')) ?>" class="rounded-full p-2 text-stone-700 hover:bg-stone-900/5" aria-label="Wishlist" title="Wishlist"><?= icon('heart', 'h-6 w-6') ?></a>
                    <a href="<?= e(url('cart')) ?>" class="relative rounded-full p-2 text-stone-700 hover:bg-stone-900/5" aria-label="Cart, <?= $cartCount ?> items" title="Cart">
                        <?= icon('bag', 'h-6 w-6') ?>
                        <?php if ($cartCount > 0): ?>
                            <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-brand-600 px-1 text-[11px] font-bold text-white"><?= $cartCount > 99 ? '99+' : $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                <?php else: ?>
                    <a href="<?= e(url('seller_dashboard')) ?>" class="btn btn-dark btn-sm hidden sm:inline-flex"><?= icon('store', 'h-4 w-4') ?> Seller Hub</a>
                <?php endif; ?>

                <details class="relative" data-dropdown>
                    <summary class="flex cursor-pointer list-none items-center gap-1 rounded-full p-1 pr-2 hover:bg-stone-900/5" aria-label="Account menu">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-stone-900 text-sm font-semibold text-white"><?= e(strtoupper(str_cut($user['name'], 1))) ?></span>
                        <?= icon('chevron-down', 'h-4 w-4 text-stone-500') ?>
                    </summary>
                    <div class="absolute right-0 mt-2 w-60 rounded-2xl border border-stone-200 bg-white p-2 shadow-xl">
                        <div class="border-b border-stone-100 px-3 pb-2 pt-1">
                            <p class="truncate text-sm font-semibold text-stone-900"><?= e($user['name']) ?></p>
                            <p class="truncate text-xs text-stone-500"><?= e($user['email']) ?></p>
                        </div>
                        <div class="py-1">
                            <?php if ($user['role'] === 'buyer'): ?>
                                <a href="<?= e(url('orders')) ?>" class="<?= $menuLink ?>"><?= icon('clipboard', 'h-4 w-4') ?> My orders</a>
                                <a href="<?= e(url('wishlist')) ?>" class="<?= $menuLink ?>"><?= icon('heart', 'h-4 w-4') ?> Wishlist</a>
                            <?php else: ?>
                                <a href="<?= e(url('seller_dashboard')) ?>" class="<?= $menuLink ?>"><?= icon('grid', 'h-4 w-4') ?> Seller Hub</a>
                                <?php if ($store = seller_store((int) $user['id'])): ?>
                                    <a href="<?= e(url('store', ['s' => $store['slug']])) ?>" class="<?= $menuLink ?>"><?= icon('store', 'h-4 w-4') ?> My storefront</a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= e(url('account')) ?>" class="<?= $menuLink ?>"><?= icon('user', 'h-4 w-4') ?> Account settings</a>
                        </div>
                        <div class="border-t border-stone-100 pt-1">
                            <?= form_open('logout', [], 'index.php') ?>
                                <button type="submit" class="<?= $menuLink ?> w-full"><?= icon('logout', 'h-4 w-4') ?> Sign out</button>
                            </form>
                        </div>
                    </div>
                </details>
            <?php endif; ?>
        </nav>
    </div>

    <div class="px-4 pb-3 md:hidden">
        <form action="index.php" method="get" role="search">
            <input type="hidden" name="page" value="shop">
            <label class="relative block">
                <span class="sr-only">Search</span>
                <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-stone-400"><?= icon('search', 'h-4 w-4') ?></span>
                <input type="search" name="q" value="<?= e($searchQuery) ?>" placeholder="Search gifts" class="input rounded-full border-stone-200 pl-10">
            </label>
        </form>
    </div>

    <?php if ($navCategories): ?>
        <div class="hidden border-t border-stone-200/70 md:block">
            <div class="mx-auto flex max-w-7xl gap-1 overflow-x-auto px-4 py-1.5 sm:px-6 lg:px-8">
                <a href="<?= e(url('shop')) ?>" class="shrink-0 rounded-full px-3 py-1.5 text-sm text-stone-600 hover:bg-stone-900/5 hover:text-stone-900">All gifts</a>
                <?php foreach ($navCategories as $category): ?>
                    <a href="<?= e(url('shop', ['category' => $category['slug']])) ?>"
                        class="shrink-0 rounded-full px-3 py-1.5 text-sm hover:bg-stone-900/5 <?= query('category') === $category['slug'] ? 'bg-stone-900/5 font-medium text-stone-900' : 'text-stone-600 hover:text-stone-900' ?>"><?= e($category['name']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="mobile-menu" class="hidden border-t border-stone-200 bg-white px-4 py-3 lg:hidden">
        <nav class="grid gap-1" aria-label="Mobile">
            <a href="<?= e(url('shop')) ?>" class="<?= $menuLink ?>"><?= icon('bag', 'h-4 w-4') ?> Shop all gifts</a>
            <a href="<?= e(url('gallery')) ?>" class="<?= $menuLink ?>"><?= icon('cube', 'h-4 w-4') ?> 3D Gallery</a>
            <?php foreach ($navCategories as $category): ?>
                <a href="<?= e(url('shop', ['category' => $category['slug']])) ?>" class="<?= $menuLink ?> pl-10"><?= e($category['name']) ?></a>
            <?php endforeach; ?>
            <?php if (!$user): ?>
                <a href="<?= e(url('register', ['role' => 'seller'])) ?>" class="<?= $menuLink ?>"><?= icon('store', 'h-4 w-4') ?> Sell on GiftCraft</a>
                <a href="<?= e(url('register')) ?>" class="<?= $menuLink ?>"><?= icon('user', 'h-4 w-4') ?> Create account</a>
            <?php elseif ($user['role'] === 'seller'): ?>
                <a href="<?= e(url('seller_dashboard')) ?>" class="<?= $menuLink ?>"><?= icon('grid', 'h-4 w-4') ?> Seller Hub</a>
            <?php else: ?>
                <a href="<?= e(url('orders')) ?>" class="<?= $menuLink ?>"><?= icon('clipboard', 'h-4 w-4') ?> My orders</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main id="main" class="flex-1">
    <?php if ($full_bleed): ?>
        <?php if (!empty($_SESSION['_flash'])): ?>
            <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8"><?php flash_messages(); ?></div>
        <?php endif; ?>
        <?= $content ?>
    <?php else: ?>
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
            <?php flash_messages(); ?>
            <?= $content ?>
        </div>
    <?php endif; ?>
</main>

<footer class="mt-16 bg-stone-900 text-stone-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div>
            <a href="index.php" class="flex items-center gap-2.5">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white"><?= icon('gift', 'h-5 w-5') ?></span>
                <span class="font-display text-xl font-semibold text-white">GiftCraft Emporium</span>
            </a>
            <p class="mt-4 text-sm leading-relaxed text-stone-400">Thoughtful gifts from independent makers, shipped straight from their studios to your door.</p>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-white">Shop</h4>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="<?= e(url('shop')) ?>" class="hover:text-white">All gifts</a></li>
                <li><a href="<?= e(url('shop', ['sort' => 'newest'])) ?>" class="hover:text-white">New arrivals</a></li>
                <li><a href="<?= e(url('shop', ['sort' => 'popular'])) ?>" class="hover:text-white">Best sellers</a></li>
                <li><a href="<?= e(url('gallery')) ?>" class="hover:text-white">3D Gallery</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-white">Sell</h4>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="<?= e(url('register', ['role' => 'seller'])) ?>" class="hover:text-white">Open a shop</a></li>
                <li><a href="<?= e(url('seller_dashboard')) ?>" class="hover:text-white">Seller Hub</a></li>
            </ul>
        </div>
        <div>
            <h4 class="text-sm font-semibold text-white">Why GiftCraft</h4>
            <ul class="mt-4 space-y-2.5 text-sm text-stone-400">
                <li class="flex items-center gap-2"><?= icon('cash', 'h-4 w-4 text-brand-300') ?> Pay on delivery</li>
                <li class="flex items-center gap-2"><?= icon('truck', 'h-4 w-4 text-brand-300') ?> Free shipping over <?= money(FREE_SHIPPING_THRESHOLD_CENTS) ?> per shop</li>
                <li class="flex items-center gap-2"><?= icon('shield', 'h-4 w-4 text-brand-300') ?> Reviews from verified buyers</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10">
        <p class="mx-auto max-w-7xl px-4 py-6 text-xs text-stone-500 sm:px-6 lg:px-8">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Crafted with care.</p>
    </div>
</footer>
</body>
</html>
