<?php
$page_title = 'Orders';
$sellerId = (int) $user['id'];

$tabs = ['' => 'All', 'pending' => 'New', 'processing' => 'Preparing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
$status = array_key_exists(query('status'), $tabs) ? query('status') : '';
$search = query('q');

$stmt = db()->prepare('SELECT status, COUNT(*) AS n FROM seller_orders WHERE seller_id = ? GROUP BY status');
$stmt->execute([$sellerId]);
$counts = array_column($stmt->fetchAll(), 'n', 'status');
$counts[''] = array_sum($counts);

$where = 'so.seller_id = ?';
$params = [$sellerId];
if ($status !== '') {
    $where .= ' AND so.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where .= " AND (o.order_number LIKE ? ESCAPE '\\' OR o.ship_name LIKE ? ESCAPE '\\')";
    array_push($params, like_term($search), like_term($search));
}
$countStmt = db()->prepare("SELECT COUNT(*) FROM seller_orders so JOIN orders o ON o.id = so.order_id WHERE {$where}");
$countStmt->execute($params);
$pg = paginate((int) $countStmt->fetchColumn(), 20, query_int('p', 1));

$stmt = db()->prepare("SELECT so.*, o.order_number, o.ship_name, o.ship_city, o.ship_country,
        (SELECT SUM(quantity) FROM order_items WHERE seller_order_id = so.id) AS item_count,
        (SELECT image_path FROM order_items WHERE seller_order_id = so.id ORDER BY id LIMIT 1) AS image_path
    FROM seller_orders so JOIN orders o ON o.id = so.order_id
    WHERE {$where}
    ORDER BY CASE so.status WHEN 'pending' THEN 0 WHEN 'processing' THEN 1 ELSE 2 END, so.created_at DESC, so.id DESC
    LIMIT ? OFFSET ?");
$stmt->execute([...$params, $pg['per_page'], $pg['offset']]);
$orders = $stmt->fetchAll();
?>

<h1 class="page-title">Orders</h1>
<p class="mt-1 text-sm text-stone-500">Confirm new orders, add tracking when you ship and mark them delivered.</p>

<div class="mt-6 flex flex-wrap items-center justify-between gap-4">
    <nav class="flex flex-wrap gap-1 rounded-full bg-stone-200/60 p-1">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= e(url('seller_orders', ['status' => $key, 'q' => $search])) ?>"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition <?= $status === $key ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-600 hover:text-stone-900' ?>">
                <?= e($label) ?> <span class="text-xs <?= $key === 'pending' && ($counts['pending'] ?? 0) > 0 ? 'font-semibold text-brand-700' : 'text-stone-400' ?>"><?= (int) ($counts[$key] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <form method="get" action="index.php" class="relative">
        <input type="hidden" name="page" value="seller_orders">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400"><?= icon('search', 'h-4 w-4') ?></span>
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Order number or customer" class="input w-64 rounded-full py-2 pl-9" aria-label="Search orders">
    </form>
</div>

<div class="mt-6">
    <?php if (!$orders): ?>
        <?php empty_state('inbox', $counts[''] ? 'No orders here' : 'No orders yet',
            $counts[''] ? 'Nothing matches this filter or search.' : 'When buyers order your products, they will appear here for you to confirm and ship.'); ?>
    <?php else: ?>
        <div class="card overflow-x-auto">
            <table class="w-full min-w-[720px]">
                <thead class="border-b border-stone-200 bg-stone-50/80">
                    <tr>
                        <th class="table-head">Order</th>
                        <th class="table-head">Customer</th>
                        <th class="table-head">Items</th>
                        <th class="table-head">Status</th>
                        <th class="table-head text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    <?php foreach ($orders as $order): $href = e(url('seller_order', ['id' => $order['id']])); ?>
                        <tr class="cursor-pointer hover:bg-stone-50/60" data-href="<?= $href ?>">
                            <td class="table-cell">
                                <a href="<?= $href ?>" class="font-semibold text-stone-900 hover:text-brand-700"><?= e($order['order_number']) ?></a>
                                <p class="text-xs text-stone-500"><?= e(fmt_date($order['created_at'], 'M j, Y · g:i a')) ?></p>
                            </td>
                            <td class="table-cell">
                                <p class="font-medium text-stone-900"><?= e($order['ship_name']) ?></p>
                                <p class="text-xs text-stone-500"><?= e($order['ship_city'] . ', ' . $order['ship_country']) ?></p>
                            </td>
                            <td class="table-cell">
                                <span class="flex items-center gap-2">
                                    <img src="<?= e(image_url($order['image_path'])) ?>" alt="" class="h-9 w-9 rounded-md object-cover">
                                    <span class="text-stone-600"><?= plural((int) $order['item_count'], 'item') ?></span>
                                </span>
                            </td>
                            <td class="table-cell"><?= status_badge($order['status']) ?></td>
                            <td class="table-cell text-right font-semibold"><?= money($order['total_cents']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php pagination($pg); ?>
    <?php endif; ?>
</div>
