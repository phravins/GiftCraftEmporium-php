<?php
$page_title = 'Products';
$sellerId = (int) $user['id'];

$filters = [
    'all'      => ['All', "p.status != 'archived'"],
    'active'   => ['Active', "p.status = 'active'"],
    'draft'    => ['Drafts', "p.status = 'draft'"],
    'low'      => ['Low / sold out', "p.status = 'active' AND p.stock <= " . (int) LOW_STOCK_THRESHOLD],
    'archived' => ['Archived', "p.status = 'archived'"],
];
$filter = array_key_exists(query('filter'), $filters) ? query('filter') : 'all';
$search = query('q');

$counts = [];
foreach ($filters as $key => [, $condition]) {
    $stmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE p.seller_id = ? AND {$condition}");
    $stmt->execute([$sellerId]);
    $counts[$key] = (int) $stmt->fetchColumn();
}

$where = "p.seller_id = ? AND {$filters[$filter][1]}";
$params = [$sellerId];
if ($search !== '') {
    $where .= " AND (p.name LIKE ? ESCAPE '\\' OR p.sku LIKE ? ESCAPE '\\')";
    array_push($params, like_term($search), like_term($search));
}
$countStmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
$countStmt->execute($params);
$pg = paginate((int) $countStmt->fetchColumn(), 15, query_int('p', 1));

$stmt = db()->prepare(product_select_sql() . " WHERE {$where} ORDER BY COALESCE(p.updated_at, p.created_at) DESC, p.id DESC LIMIT ? OFFSET ?");
$stmt->execute([...$params, $pg['per_page'], $pg['offset']]);
$products = $stmt->fetchAll();
?>

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <h1 class="page-title">Products</h1>
        <p class="mt-1 text-sm text-stone-500">Manage listings, prices and stock.</p>
    </div>
    <a href="<?= e(url('seller_product')) ?>" class="btn btn-primary"><?= icon('plus', 'h-4 w-4') ?> Add product</a>
</div>

<div class="mt-6 flex flex-wrap items-center justify-between gap-4">
    <nav class="flex flex-wrap gap-1 rounded-full bg-stone-200/60 p-1">
        <?php foreach ($filters as $key => [$label]): ?>
            <a href="<?= e(url('seller_products', ['filter' => $key === 'all' ? null : $key, 'q' => $search])) ?>"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition <?= $filter === $key ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-600 hover:text-stone-900' ?>">
                <?= e($label) ?> <span class="text-xs text-stone-400"><?= $counts[$key] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <form method="get" action="index.php" class="relative">
        <input type="hidden" name="page" value="seller_products">
        <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400"><?= icon('search', 'h-4 w-4') ?></span>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name or SKU" class="input w-64 rounded-full py-2 pl-9" aria-label="Search products">
    </form>
</div>

<div class="mt-6">
    <?php if (!$products): ?>
        <?php if ($counts['all'] === 0 && $counts['archived'] === 0): ?>
            <?php empty_state('tag', 'List your first product', 'Add photos, a price and how many you have in stock. It goes live on the marketplace straight away.',
                '<a href="' . e(url('seller_product')) . '" class="btn btn-primary">' . icon('plus', 'h-4 w-4') . ' Add product</a>'); ?>
        <?php else: ?>
            <?php empty_state('search', 'No products here', 'Nothing matches this filter or search.'); ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="border-b border-stone-200 bg-stone-50/80">
                    <tr>
                        <th class="table-head">Product</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Price</th>
                        <th class="table-head">Stock</th>
                        <th class="table-head text-right">Sold</th>
                        <th class="table-head"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    <?php foreach ($products as $product): $stock = (int) $product['stock']; ?>
                        <tr class="hover:bg-stone-50/60">
                            <td class="table-cell">
                                <a href="<?= e(url('seller_product', ['id' => $product['id']])) ?>" class="flex items-center gap-3">
                                    <img src="<?= e(image_url($product['image_path'])) ?>" alt="" class="h-12 w-12 rounded-lg object-cover ring-1 ring-stone-200">
                                    <span class="min-w-0">
                                        <span class="line-clamp-1 font-medium text-stone-900 hover:text-brand-700"><?= e($product['name']) ?></span>
                                        <span class="block text-xs text-stone-500"><?= e($product['category_name'] ?: 'Uncategorised') ?><?= $product['sku'] ? ' · ' . e($product['sku']) : '' ?></span>
                                    </span>
                                </a>
                            </td>
                            <td class="table-cell"><?= status_badge($product['status']) ?></td>
                            <td class="table-cell text-right font-medium"><?= money($product['price_cents']) ?></td>
                            <td class="table-cell">
                                <?= form_open('seller_product_quick', ['class' => 'flex items-center gap-2']) ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                    <input type="number" name="stock" value="<?= $stock ?>" min="0" max="100000"
                                        class="input w-20 py-1.5 <?= $stock === 0 ? 'border-red-300' : ($stock <= LOW_STOCK_THRESHOLD ? 'border-amber-300' : '') ?>" aria-label="Stock for <?= e($product['name']) ?>">
                                    <button type="submit" class="btn btn-outline btn-sm">Save</button>
                                </form>
                            </td>
                            <td class="table-cell text-right text-stone-600"><?= (int) $product['sold_qty'] ?></td>
                            <td class="table-cell">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="<?= e(url('product', ['id' => $product['id']])) ?>" class="rounded-full p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900" title="View on storefront" aria-label="View on storefront"><?= icon('eye', 'h-4 w-4') ?></a>
                                    <a href="<?= e(url('seller_product', ['id' => $product['id']])) ?>" class="rounded-full p-2 text-stone-500 hover:bg-stone-100 hover:text-stone-900" title="Edit" aria-label="Edit"><?= icon('pencil', 'h-4 w-4') ?></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php pagination($pg); ?>
    <?php endif; ?>
</div>
