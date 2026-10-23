<?php
$full_bleed = true;

$newArrivals = search_products(['sort' => 'newest'], 1, 8)['items'];
$bestSellers = array_values(array_filter(
    $newArrivals ? search_products(['sort' => 'popular'], 1, 4)['items'] : [],
    fn($p) => (int) $p['sold_qty'] > 0
));
$categories = categories_with_products(6);
$stores = featured_stores(4);
$stats = db()->query("SELECT
        (SELECT COUNT(*) FROM products WHERE status = 'active') AS products,
        (SELECT COUNT(DISTINCT seller_id) FROM products WHERE status = 'active') AS makers")->fetch();

$sellCta = !$user
    ? '<a href="' . e(url('register', ['role' => 'seller'])) . '" class="btn btn-outline btn-lg">Open your shop</a>'
    : ($user['role'] === 'seller'
        ? '<a href="' . e(url('seller_product')) . '" class="btn btn-outline btn-lg">List a product</a>'
        : '<a href="' . e(url('gallery')) . '" class="btn btn-outline btn-lg">' . icon('cube', 'h-5 w-5') . ' Explore in 3D</a>');
?>

<section class="relative overflow-hidden">
    <div class="pointer-events-none absolute -right-40 -top-40 h-[32rem] w-[32rem] rounded-full bg-brand-100/60 blur-3xl"></div>
    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-4 py-12 sm:px-6 lg:grid-cols-12 lg:px-8 lg:py-20">
        <div class="lg:col-span-6">
            <p class="eyebrow">Independent makers · Curated gifts</p>
            <h1 class="mt-5 font-display text-5xl font-semibold leading-[1.05] tracking-tight text-stone-900 sm:text-6xl">
                The art of gifting, <span class="italic text-brand-700">made by hand.</span>
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-stone-600">
                Discover one-of-a-kind presents from small studios - or open your own shop and sell to gift-givers everywhere.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="<?= e(url('shop')) ?>" class="btn btn-primary btn-lg">Shop gifts <?= icon('arrow-right', 'h-5 w-5') ?></a>
                <?= $sellCta ?>
            </div>
            <?php if ((int) $stats['products'] > 0): ?>
                <dl class="mt-12 flex gap-10">
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Gifts listed</dt>
                        <dd class="mt-1 font-display text-3xl font-semibold text-stone-900"><?= number_format((int) $stats['products']) ?></dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wide text-stone-500">Makers</dt>
                        <dd class="mt-1 font-display text-3xl font-semibold text-stone-900"><?= number_format((int) $stats['makers']) ?></dd>
                    </div>
                </dl>
            <?php endif; ?>
        </div>
        <div class="lg:col-span-6">
            <div class="relative">
                <img src="assets/img/hero_bg_full.jpg" alt="Shelves of handmade ceramics, candles and wrapped gifts"
                    class="aspect-[5/4] w-full rounded-[2rem] object-cover object-top shadow-2xl shadow-stone-900/10">
                <div class="absolute -bottom-6 left-6 hidden items-center gap-3 rounded-2xl bg-white px-5 py-4 shadow-xl sm:flex">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-700"><?= icon('truck') ?></span>
                    <div>
                        <p class="text-sm font-semibold text-stone-900">Track every package</p>
                        <p class="text-xs text-stone-500">Shipped by the maker, straight to you</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="border-y border-stone-200/80 bg-white">
    <div class="mx-auto grid max-w-7xl grid-cols-2 gap-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
        <?php foreach ([
            ['sparkles', 'Handmade & curated', 'Every shop is run by an independent maker.'],
            ['cash', 'Pay on delivery', 'Pay when your gift arrives at your door.'],
            ['truck', 'Free shipping', 'On orders over ' . money(FREE_SHIPPING_THRESHOLD_CENTS) . ' from a shop.'],
            ['shield', 'Verified reviews', 'Only buyers who received an item can review it.'],
        ] as [$iconName, $title, $text]): ?>
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-700"><?= icon($iconName) ?></span>
                <div>
                    <p class="text-sm font-semibold text-stone-900"><?= e($title) ?></p>
                    <p class="mt-0.5 text-xs leading-relaxed text-stone-500"><?= e($text) ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="mx-auto max-w-7xl space-y-20 px-4 py-16 sm:px-6 lg:px-8">
    <?php if (!$newArrivals): ?>
        <?php empty_state(
            'store',
            'Our shelves are waiting for their first makers',
            'GiftCraft is a brand-new marketplace. Once sellers list their handmade gifts, they will appear right here.',
            !$user
                ? '<a href="' . e(url('register', ['role' => 'seller'])) . '" class="btn btn-primary">Open your shop</a><a href="' . e(url('register')) . '" class="btn btn-outline">Create a buyer account</a>'
                : ($user['role'] === 'seller' ? '<a href="' . e(url('seller_product')) . '" class="btn btn-primary">' . icon('plus', 'h-4 w-4') . ' List your first product</a>' : '')
        ); ?>
    <?php else: ?>

        <?php if ($categories): ?>
            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Browse</p>
                        <h2 class="section-title mt-2">Shop by category</h2>
                    </div>
                    <a href="<?= e(url('shop')) ?>" class="link hidden text-sm sm:inline">All gifts &rarr;</a>
                </div>
                <div class="mt-8 grid grid-cols-2 gap-4 sm:gap-6 md:grid-cols-3">
                    <?php foreach ($categories as $category): ?>
                        <a href="<?= e(url('shop', ['category' => $category['slug']])) ?>" class="group relative block aspect-[4/3] overflow-hidden rounded-2xl bg-stone-200">
                            <img src="<?= e(image_url($category['cover_path'])) ?>" alt="" loading="lazy" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            <span class="absolute inset-0 bg-gradient-to-t from-stone-900/75 via-stone-900/10 to-transparent"></span>
                            <span class="absolute inset-x-0 bottom-0 p-4 sm:p-5">
                                <span class="block font-display text-lg font-semibold text-white sm:text-2xl"><?= e($category['name']) ?></span>
                                <span class="text-xs text-white/80"><?= plural((int) $category['product_count'], 'gift') ?></span>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section>
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="eyebrow">Just listed</p>
                    <h2 class="section-title mt-2">New arrivals</h2>
                </div>
                <a href="<?= e(url('shop', ['sort' => 'newest'])) ?>" class="link text-sm">View all &rarr;</a>
            </div>
            <div class="mt-8"><?php product_grid($newArrivals); ?></div>
        </section>

        <?php if ($bestSellers): ?>
            <section>
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <p class="eyebrow">Loved by buyers</p>
                        <h2 class="section-title mt-2">Best sellers</h2>
                    </div>
                    <a href="<?= e(url('shop', ['sort' => 'popular'])) ?>" class="link text-sm">View all &rarr;</a>
                </div>
                <div class="mt-8"><?php product_grid($bestSellers); ?></div>
            </section>
        <?php endif; ?>

        <?php if ($stores): ?>
            <section>
                <p class="eyebrow">Meet the makers</p>
                <h2 class="section-title mt-2">Featured shops</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <?php foreach ($stores as $store): ?>
                        <a href="<?= e(url('store', ['s' => $store['slug']])) ?>" class="card group overflow-hidden transition hover:shadow-lg">
                            <div class="h-28 overflow-hidden bg-stone-100">
                                <img src="<?= e(image_url($store['cover_path'])) ?>" alt="" loading="lazy" class="h-full w-full object-cover opacity-90 transition duration-500 group-hover:scale-105">
                            </div>
                            <div class="relative -mt-7 px-5 pb-5">
                                <?php if ($store['logo_path']): ?>
                                    <img src="<?= e($store['logo_path']) ?>" alt="" class="h-14 w-14 rounded-xl border-4 border-white object-cover shadow">
                                <?php else: ?>
                                    <span class="flex h-14 w-14 items-center justify-center rounded-xl border-4 border-white bg-brand-100 font-display text-xl font-semibold text-brand-800 shadow"><?= e(strtoupper(str_cut($store['name'], 1))) ?></span>
                                <?php endif; ?>
                                <p class="mt-3 font-semibold text-stone-900"><?= e($store['name']) ?></p>
                                <p class="mt-0.5 line-clamp-1 text-sm text-stone-500"><?= e($store['tagline'] ?: plural((int) $store['product_count'], 'product')) ?></p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (($user['role'] ?? null) !== 'buyer'): ?>
        <section class="overflow-hidden rounded-[2rem] bg-stone-900 text-white">
            <div class="grid items-center lg:grid-cols-2">
                <div class="p-8 sm:p-12 lg:p-16">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-300">For makers</p>
                    <h2 class="mt-3 font-display text-3xl font-semibold sm:text-4xl">Turn your craft into a business.</h2>
                    <ul class="mt-6 space-y-3 text-sm text-stone-300">
                        <li class="flex items-center gap-3"><?= icon('check-circle', 'h-5 w-5 text-brand-300') ?> List products with photos, pricing and stock in minutes</li>
                        <li class="flex items-center gap-3"><?= icon('check-circle', 'h-5 w-5 text-brand-300') ?> Confirm, ship and track orders from your Seller Hub</li>
                        <li class="flex items-center gap-3"><?= icon('check-circle', 'h-5 w-5 text-brand-300') ?> See sales, low-stock alerts and reviews at a glance</li>
                    </ul>
                    <a href="<?= e($user ? url('seller_dashboard') : url('register', ['role' => 'seller'])) ?>" class="btn btn-primary btn-lg mt-8">
                        <?= $user ? 'Go to Seller Hub' : 'Start selling' ?> <?= icon('arrow-right', 'h-5 w-5') ?>
                    </a>
                </div>
                <img src="assets/img/cat_home.png" alt="Handmade ceramic vase with dried flowers" class="h-full max-h-[28rem] w-full object-cover">
            </div>
        </section>
    <?php endif; ?>
</div>
