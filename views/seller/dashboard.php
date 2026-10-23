<?php
$page_title = 'Seller dashboard';
$sellerId = (int) $user['id'];
$store = seller_store($sellerId);
$db = db();

$stmt = $db->prepare("SELECT
        COALESCE(SUM(CASE WHEN status != 'cancelled' THEN total_cents END), 0) AS gross,
        COALESCE(SUM(CASE WHEN status != 'cancelled' AND created_at >= datetime('now', '-30 days') THEN total_cents END), 0) AS gross_30,
        COUNT(CASE WHEN status != 'cancelled' THEN 1 END) AS orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) AS new_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) AS preparing
    FROM seller_orders WHERE seller_id = ?");
$stmt->execute([$sellerId]);
$sales = $stmt->fetch();

$stmt = $db->prepare("SELECT
        COUNT(*) AS total,
        COUNT(CASE WHEN status = 'active' THEN 1 END) AS active,
        COUNT(CASE WHEN status = 'active' AND stock = 0 THEN 1 END) AS sold_out,
        COUNT(CASE WHEN status = 'active' AND stock BETWEEN 1 AND ? THEN 1 END) AS low
    FROM products WHERE seller_id = ? AND status != 'archived'");
$stmt->execute([LOW_STOCK_THRESHOLD, $sellerId]);
$catalog = $stmt->fetch();

$stmt = $db->prepare('SELECT AVG(r.rating) AS rating, COUNT(*) AS reviews,
        COUNT(CASE WHEN r.seller_reply IS NULL THEN 1 END) AS unanswered
    FROM reviews r JOIN products p ON p.id = r.product_id WHERE p.seller_id = ?');
$stmt->execute([$sellerId]);
$feedback = $stmt->fetch();

// Daily sales for the last 14 days, in the shop's timezone.
$days = [];
for ($i = 13; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $days[$date] = ['sales' => 0, 'orders' => 0];
}
$stmt = $db->prepare("SELECT date(created_at, ?) AS day, SUM(total_cents) AS sales, COUNT(*) AS orders
    FROM seller_orders WHERE seller_id = ? AND status != 'cancelled' AND created_at >= datetime('now', '-15 days')
    GROUP BY day");
$stmt->execute([sqlite_tz_modifier(), $sellerId]);
foreach ($stmt->fetchAll() as $row) {
    if (isset($days[$row['day']])) {
        $days[$row['day']] = ['sales' => (int) $row['sales'], 'orders' => (int) $row['orders']];
    }
}
$maxSales = max(array_column($days, 'sales')) ?: 0;
$periodSales = array_sum(array_column($days, 'sales'));
$periodOrders = array_sum(array_column($days, 'orders'));

$stmt = $db->prepare("SELECT so.*, o.order_number, o.ship_name,
        (SELECT SUM(quantity) FROM order_items WHERE seller_order_id = so.id) AS item_count
    FROM seller_orders so JOIN orders o ON o.id = so.order_id
    WHERE so.seller_id = ? ORDER BY so.created_at DESC, so.id DESC LIMIT 6");
$stmt->execute([$sellerId]);
$recentOrders = $stmt->fetchAll();

$stmt = $db->prepare("SELECT oi.product_id, oi.product_name, MAX(oi.image_path) AS image_path,
        SUM(oi.quantity) AS units, SUM(oi.line_total_cents) AS revenue
    FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id
    WHERE so.seller_id = ? AND so.status != 'cancelled'
    GROUP BY oi.product_id ORDER BY units DESC, revenue DESC LIMIT 5");
$stmt->execute([$sellerId]);
$topProducts = $stmt->fetchAll();

$stmt = $db->prepare("SELECT id, name, stock FROM products
    WHERE seller_id = ? AND status = 'active' AND stock <= ? ORDER BY stock, name LIMIT 6");
$stmt->execute([$sellerId, LOW_STOCK_THRESHOLD]);
$lowStock = $stmt->fetchAll();

$setup = [
    ['Create your shop', true, url('seller_settings')],
    ['Add a logo and story', $store && $store['logo_path'] && $store['description'], url('seller_settings')],
    ['List your first product', (int) $catalog['total'] > 0, url('seller_product')],
    ['Receive your first order', (int) $sales['orders'] > 0, url('store', ['s' => $store['slug'] ?? ''])],
];
$setupDone = count(array_filter(array_column($setup, 1)));
?>

<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="eyebrow"><?= e($store['name'] ?? 'Your shop') ?></p>
        <h1 class="page-title mt-2">Good <?= (int) date('G') < 12 ? 'morning' : ((int) date('G') < 18 ? 'afternoon' : 'evening') ?>, <?= e(explode(' ', $user['name'])[0]) ?></h1>
    </div>
    <?php if ((int) $sales['new_orders'] > 0): ?>
        <a href="<?= e(url('seller_orders', ['status' => 'pending'])) ?>" class="btn btn-primary"><?= icon('inbox', 'h-4 w-4') ?> <?= plural((int) $sales['new_orders'], 'new order') ?> to confirm</a>
    <?php endif; ?>
</div>

<?php if ($setupDone < count($setup)): ?>
    <section class="card mt-8 p-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-stone-900">Get your shop ready</h2>
                <p class="text-sm text-stone-500"><?= $setupDone ?> of <?= count($setup) ?> steps complete</p>
            </div>
            <div class="h-2 w-48 overflow-hidden rounded-full bg-stone-100"><div class="h-full rounded-full bg-brand-600" style="width: <?= round($setupDone / count($setup) * 100) ?>%"></div></div>
        </div>
        <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($setup as $i => [$label, $done, $href]): ?>
                <li>
                    <a href="<?= e($href) ?>" class="flex h-full items-center gap-3 rounded-xl border p-4 text-sm transition <?= $done ? 'border-emerald-200 bg-emerald-50/60 text-emerald-900' : 'border-stone-200 hover:border-brand-300 hover:bg-brand-50/40' ?>">
                        <?php if ($done): ?>
                            <?= icon('check-circle', 'h-6 w-6 shrink-0 text-emerald-600') ?>
                        <?php else: ?>
                            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-stone-300 text-xs font-semibold text-stone-500"><?= $i + 1 ?></span>
                        <?php endif; ?>
                        <span class="font-medium"><?= e($label) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
<?php endif; ?>

<section class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ([
        ['Sales · last 30 days', money($sales['gross_30']), money($sales['gross']) . ' all time', 'cash', null],
        ['Orders to fulfil', (int) $sales['new_orders'] + (int) $sales['preparing'], (int) $sales['new_orders'] . ' new · ' . (int) $sales['preparing'] . ' preparing', 'inbox', url('seller_orders')],
        ['Active products', (int) $catalog['active'], (int) $catalog['low'] . ' low stock · ' . (int) $catalog['sold_out'] . ' sold out', 'tag', url('seller_products')],
        ['Average rating', (int) $feedback['reviews'] ? number_format((float) $feedback['rating'], 1) . ' / 5' : '–', plural((int) $feedback['reviews'], 'review') . ((int) $feedback['unanswered'] ? ' · ' . (int) $feedback['unanswered'] . ' awaiting reply' : ''), 'chat', url('seller_reviews')],
    ] as [$label, $value, $sub, $iconName, $href]): ?>
        <<?= $href ? 'a href="' . e($href) . '"' : 'div' ?> class="card block p-5 <?= $href ? 'transition hover:shadow-md' : '' ?>">
            <div class="flex items-center justify-between">
                <p class="text-sm text-stone-500"><?= e($label) ?></p>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-stone-100 text-stone-600"><?= icon($iconName, 'h-5 w-5') ?></span>
            </div>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-stone-900"><?= e((string) $value) ?></p>
            <p class="mt-1 text-xs text-stone-500"><?= e($sub) ?></p>
        </<?= $href ? 'a' : 'div' ?>>
    <?php endforeach; ?>
</section>

<section class="card mt-6 p-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="font-semibold text-stone-900">Sales, last 14 days</h2>
            <p class="text-sm text-stone-500"><?= money($periodSales) ?> from <?= plural($periodOrders, 'order') ?> (cancelled orders excluded)</p>
        </div>
    </div>

    <div class="relative mt-6">
        <div class="pointer-events-none absolute inset-x-0 top-0 flex h-48 flex-col justify-between">
            <div class="flex items-center gap-2 text-[11px] text-stone-400"><span class="w-14 text-right"><?= $maxSales ? money($maxSales) : '' ?></span><span class="h-px flex-1 bg-stone-100"></span></div>
            <div class="flex items-center gap-2 text-[11px] text-stone-400"><span class="w-14 text-right"><?= money(0) ?></span><span class="h-px flex-1 bg-stone-200"></span></div>
        </div>
        <div class="relative ml-16 flex h-48 items-end gap-[2px] sm:gap-1.5" role="img" aria-label="Daily sales for the last 14 days">
            <?php foreach ($days as $date => $day):
                $height = $maxSales ? max(2, round($day['sales'] / $maxSales * 100)) : 0; ?>
                <div class="group relative flex h-full flex-1 items-end justify-center" tabindex="0">
                    <div class="w-full max-w-7 rounded-t <?= $day['sales'] ? 'bg-brand-500 group-hover:bg-brand-700 group-focus:bg-brand-700' : '' ?>" style="height: <?= $height ?>%"></div>
                    <div class="pointer-events-none absolute bottom-full z-10 mb-2 hidden whitespace-nowrap rounded-lg bg-stone-900 px-3 py-2 text-xs text-white shadow-lg group-hover:block group-focus:block">
                        <p class="font-semibold"><?= e(date('D, M j', strtotime($date))) ?></p>
                        <p class="text-stone-300"><?= money($day['sales']) ?> · <?= plural($day['orders'], 'order') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="ml-16 mt-2 flex gap-[2px] sm:gap-1.5">
            <?php $i = 0; foreach ($days as $date => $day): ?>
                <span class="flex-1 whitespace-nowrap text-center text-[10px] text-stone-400">
                    <?php if ($i % 2 === 0): ?><span class="<?= $i % 4 === 0 ? '' : 'hidden sm:inline' ?>"><?= e(date('M j', strtotime($date))) ?></span><?php endif; ?>
                </span>
            <?php $i++; endforeach; ?>
        </div>
        <?php if (!$maxSales): ?>
            <p class="absolute inset-x-0 top-16 ml-16 text-center text-sm text-stone-500">No sales in the last 14 days yet.</p>
        <?php endif; ?>
    </div>
    <details class="mt-4 text-sm">
        <summary class="cursor-pointer text-stone-500 hover:text-stone-800">View as table</summary>
        <table class="mt-3 w-full max-w-md text-left">
            <thead><tr class="border-b border-stone-200 text-xs uppercase text-stone-500"><th class="py-2">Day</th><th class="py-2 text-right">Orders</th><th class="py-2 text-right">Sales</th></tr></thead>
            <tbody>
                <?php foreach (array_reverse($days, true) as $date => $day): ?>
                    <tr class="border-b border-stone-100"><td class="py-1.5"><?= e(date('D, M j', strtotime($date))) ?></td><td class="py-1.5 text-right"><?= $day['orders'] ?></td><td class="py-1.5 text-right"><?= money($day['sales']) ?></td></tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </details>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-3">
    <section class="card lg:col-span-2">
        <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
            <h2 class="font-semibold text-stone-900">Recent orders</h2>
            <a href="<?= e(url('seller_orders')) ?>" class="link text-sm">View all</a>
        </div>
        <?php if (!$recentOrders): ?>
            <p class="px-6 py-10 text-center text-sm text-stone-500">No orders yet. Share your storefront link to get your first sale.</p>
        <?php else: ?>
            <ul class="divide-y divide-stone-100">
                <?php foreach ($recentOrders as $order): ?>
                    <li>
                        <a href="<?= e(url('seller_order', ['id' => $order['id']])) ?>" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-6 py-3.5 hover:bg-stone-50">
                            <div class="min-w-[12rem] flex-1">
                                <p class="text-sm font-semibold text-stone-900"><?= e($order['order_number']) ?></p>
                                <p class="text-xs text-stone-500"><?= e($order['ship_name']) ?> · <?= plural((int) $order['item_count'], 'item') ?> · <?= e(fmt_date($order['created_at'], 'M j, g:i a')) ?></p>
                            </div>
                            <?= status_badge($order['status']) ?>
                            <span class="w-24 text-right text-sm font-semibold text-stone-900"><?= money($order['total_cents']) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <div class="space-y-6">
        <section class="card">
            <div class="border-b border-stone-100 px-6 py-4"><h2 class="font-semibold text-stone-900">Top sellers</h2></div>
            <?php if (!$topProducts): ?>
                <p class="px-6 py-8 text-center text-sm text-stone-500">Your best sellers will show up here.</p>
            <?php else: ?>
                <ul class="divide-y divide-stone-100">
                    <?php foreach ($topProducts as $product): ?>
                        <li class="flex items-center gap-3 px-6 py-3">
                            <img src="<?= e(image_url($product['image_path'])) ?>" alt="" class="h-10 w-10 rounded-lg object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-stone-900"><?= e($product['product_name']) ?></p>
                                <p class="text-xs text-stone-500"><?= plural((int) $product['units'], 'unit') ?> sold</p>
                            </div>
                            <span class="text-sm font-semibold"><?= money($product['revenue']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="card">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <h2 class="font-semibold text-stone-900">Stock alerts</h2>
                <a href="<?= e(url('seller_products', ['filter' => 'low'])) ?>" class="link text-sm">Manage</a>
            </div>
            <?php if (!$lowStock): ?>
                <p class="flex items-center justify-center gap-2 px-6 py-8 text-sm text-stone-500"><?= icon('check-circle', 'h-5 w-5 text-emerald-600') ?> All products are well stocked.</p>
            <?php else: ?>
                <ul class="divide-y divide-stone-100">
                    <?php foreach ($lowStock as $product): ?>
                        <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                            <a href="<?= e(url('seller_product', ['id' => $product['id']])) ?>" class="truncate font-medium text-stone-900 hover:text-brand-700"><?= e($product['name']) ?></a>
                            <span class="chip shrink-0 <?= (int) $product['stock'] === 0 ? 'bg-red-50 text-red-800 ring-1 ring-red-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' ?>">
                                <?= icon('warning', 'h-3.5 w-3.5') ?> <?= (int) $product['stock'] === 0 ? 'Sold out' : (int) $product['stock'] . ' left' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</div>
