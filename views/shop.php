<?php
$sort = array_key_exists(query('sort'), SORT_OPTIONS) ? query('sort') : 'newest';
$filters = [
    'q'        => query('q'),
    'category' => query('category'),
    'min'      => parse_money(query('min')),
    'max'      => parse_money(query('max')),
    'in_stock' => query('in_stock') === '1',
    'sort'     => $sort,
];
$result = search_products($filters, query_int('p', 1), PER_PAGE);
$products = $result['items'];
$pg = $result['pagination'];

$categories = categories_with_products();
$activeCategory = null;
foreach ($categories as $category) {
    if ($category['slug'] === $filters['category']) {
        $activeCategory = $category;
    }
}

$heading = $filters['q'] !== '' ? 'Results for “' . $filters['q'] . '”' : ($activeCategory['name'] ?? 'All gifts');
$page_title = $filters['q'] !== '' ? 'Search: ' . $filters['q'] : ($activeCategory['name'] ?? 'Shop all gifts');

$chips = [];
if ($filters['q'] !== '') $chips[] = ['Search: ' . $filters['q'], ['q' => null]];
if ($activeCategory) $chips[] = [$activeCategory['name'], ['category' => null]];
if ($filters['min'] !== null) $chips[] = ['From ' . money($filters['min']), ['min' => null]];
if ($filters['max'] !== null) $chips[] = ['Up to ' . money($filters['max']), ['max' => null]];
if ($filters['in_stock']) $chips[] = ['In stock', ['in_stock' => null]];
$categoryLink = 'flex items-center justify-between rounded-lg px-2.5 py-1.5 text-sm transition';
?>

<div class="flex flex-col gap-8 lg:flex-row">
    <aside class="shrink-0 lg:w-64">
        <details class="card group lg:sticky lg:top-40" data-open-desktop open>
            <summary class="flex cursor-pointer list-none items-center justify-between p-4 text-sm font-semibold lg:hidden">
                <span class="flex items-center gap-2"><?= icon('funnel', 'h-4 w-4') ?> Filters</span>
                <?= icon('chevron-down', 'h-4 w-4 transition group-open:rotate-180') ?>
            </summary>
            <div class="space-y-6 border-t border-stone-100 p-5 lg:border-0">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-500">Category</h3>
                    <nav class="mt-3 space-y-0.5">
                        <a href="<?= e(url_with(['category' => null, 'p' => null])) ?>"
                            class="<?= $categoryLink ?> <?= !$activeCategory ? 'bg-stone-900 text-white' : 'text-stone-700 hover:bg-stone-100' ?>">All gifts</a>
                        <?php foreach ($categories as $category): $isActive = $activeCategory && $activeCategory['id'] === $category['id']; ?>
                            <a href="<?= e(url_with(['category' => $category['slug'], 'p' => null])) ?>"
                                class="<?= $categoryLink ?> <?= $isActive ? 'bg-stone-900 text-white' : 'text-stone-700 hover:bg-stone-100' ?>">
                                <span class="truncate"><?= e($category['name']) ?></span>
                                <span class="text-xs <?= $isActive ? 'text-white/70' : 'text-stone-400' ?>"><?= (int) $category['product_count'] ?></span>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>

                <form method="get" action="index.php" class="space-y-6">
                    <input type="hidden" name="page" value="shop">
                    <?php foreach (['q' => $filters['q'], 'category' => $filters['category'], 'sort' => $sort] as $name => $value): ?>
                        <?php if ($value !== ''): ?><input type="hidden" name="<?= $name ?>" value="<?= e($value) ?>"><?php endif; ?>
                    <?php endforeach; ?>
                    <div>
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-stone-500">Price</h3>
                        <div class="mt-3 flex items-center gap-2">
                            <input class="input" name="min" inputmode="decimal" placeholder="Min" value="<?= e(money_input($filters['min'])) ?>" aria-label="Minimum price">
                            <span class="text-stone-400">–</span>
                            <input class="input" name="max" inputmode="decimal" placeholder="Max" value="<?= e(money_input($filters['max'])) ?>" aria-label="Maximum price">
                        </div>
                    </div>
                    <label class="flex items-center gap-2.5 text-sm text-stone-700">
                        <input type="checkbox" name="in_stock" value="1" class="checkbox" <?= $filters['in_stock'] ? 'checked' : '' ?>>
                        Only show items in stock
                    </label>
                    <button type="submit" class="btn btn-dark w-full">Apply filters</button>
                </form>
            </div>
        </details>
    </aside>

    <div class="min-w-0 flex-1">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="eyebrow">Shop</p>
                <h1 class="page-title mt-2"><?= e($heading) ?></h1>
                <p class="mt-2 text-sm text-stone-500"><?= plural($pg['total'], 'gift') ?></p>
            </div>
            <form method="get" action="index.php" class="flex items-center gap-2">
                <?php foreach ($_GET as $name => $value): ?>
                    <?php if (is_string($value) && !in_array($name, ['sort', 'p'], true)): ?><input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>"><?php endif; ?>
                <?php endforeach; ?>
                <?php if (!isset($_GET['page'])): ?><input type="hidden" name="page" value="shop"><?php endif; ?>
                <label for="sort" class="whitespace-nowrap text-sm text-stone-500">Sort by</label>
                <select id="sort" name="sort" class="input w-auto rounded-full py-2 pr-9" data-autosubmit>
                    <?php foreach (SORT_OPTIONS as $value => [$label]): ?>
                        <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <noscript><button class="btn btn-outline btn-sm">Sort</button></noscript>
            </form>
        </div>

        <?php if ($chips): ?>
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <?php foreach ($chips as [$label, $remove]): ?>
                    <a href="<?= e(url_with($remove + ['p' => null])) ?>" class="chip gap-1.5 bg-white py-1 pl-3 pr-2 text-stone-700 ring-1 ring-stone-200 hover:ring-stone-300">
                        <?= e($label) ?> <?= icon('x', 'h-3.5 w-3.5 text-stone-400') ?>
                    </a>
                <?php endforeach; ?>
                <a href="<?= e(url('shop')) ?>" class="text-xs font-medium text-stone-500 hover:text-stone-800">Clear all</a>
            </div>
        <?php endif; ?>

        <div class="mt-8">
            <?php if ($products): ?>
                <?php product_grid($products, 'sm:grid-cols-2 xl:grid-cols-3'); ?>
                <?php pagination($pg); ?>
            <?php elseif ($chips): ?>
                <?php empty_state('search', 'No gifts match those filters', 'Try a different search term, widen the price range or browse all gifts.',
                    '<a href="' . e(url('shop')) . '" class="btn btn-outline">Browse all gifts</a>'); ?>
            <?php else: ?>
                <?php empty_state('store', 'No gifts listed yet', 'Sellers have not listed any products so far. Check back soon!',
                    !$user ? '<a href="' . e(url('register', ['role' => 'seller'])) . '" class="btn btn-primary">Become a seller</a>' : ''); ?>
            <?php endif; ?>
        </div>
    </div>
</div>
