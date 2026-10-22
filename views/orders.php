<?php
$page_title = 'My orders';

$countStmt = db()->prepare('SELECT COUNT(*) FROM orders WHERE buyer_id = ?');
$countStmt->execute([$user['id']]);
$pg = paginate((int) $countStmt->fetchColumn(), 10, query_int('p', 1));

$stmt = db()->prepare('SELECT * FROM orders WHERE buyer_id = ? ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?');
$stmt->execute([$user['id'], $pg['per_page'], $pg['offset']]);
$orders = $stmt->fetchAll();
$packages = packages_for_orders(array_column($orders, 'id'));
?>

<h1 class="page-title">My orders</h1>
<p class="mt-2 text-sm text-stone-500">Track packages, cancel before they ship and review delivered gifts.</p>

<div class="mt-8 space-y-5">
    <?php if (!$orders): ?>
        <?php empty_state('clipboard', 'No orders yet', 'When you place an order, you can follow every package from here.',
            '<a href="' . e(url('shop')) . '" class="btn btn-primary">Start shopping</a>'); ?>
    <?php endif; ?>

    <?php foreach ($orders as $order):
        $orderPackages = $packages[(int) $order['id']] ?? [];
        $status = overall_order_status(array_column($orderPackages, 'status'));
        $items = array_merge(...array_map(fn($p) => $p['items'], $orderPackages ?: [['items' => []]])); ?>
        <a href="<?= e(url('order', ['id' => $order['id']])) ?>" class="card block p-5 transition hover:shadow-md sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="font-semibold text-stone-900">Order <?= e($order['order_number']) ?></p>
                    <p class="mt-0.5 text-sm text-stone-500">Placed <?= e(fmt_date($order['created_at'])) ?> · <?= plural(count($orderPackages), 'package') ?></p>
                </div>
                <div class="flex items-center gap-4">
                    <?= status_badge($status) ?>
                    <span class="font-semibold text-stone-900"><?= money($order['total_cents']) ?></span>
                </div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <?php foreach (array_slice($items, 0, 6) as $item): ?>
                    <img src="<?= e(image_url($item['image_path'])) ?>" alt="<?= e($item['product_name']) ?>" title="<?= e($item['product_name']) ?>" class="h-14 w-14 rounded-lg object-cover ring-1 ring-stone-200">
                <?php endforeach; ?>
                <?php if (count($items) > 6): ?><span class="text-sm text-stone-500">+<?= count($items) - 6 ?> more</span><?php endif; ?>
                <span class="ml-auto hidden items-center gap-1 text-sm font-medium text-brand-700 sm:inline-flex">View details <?= icon('chevron-right', 'h-4 w-4') ?></span>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php pagination($pg); ?>
