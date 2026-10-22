<?php
$page_title = 'Wishlist';
$stmt = db()->prepare(product_select_sql() . " JOIN wishlist_items w ON w.product_id = p.id
    WHERE w.user_id = ? AND p.status = 'active' ORDER BY w.created_at DESC");
$stmt->execute([$user['id']]);
$products = $stmt->fetchAll();
?>

<h1 class="page-title">Wishlist</h1>
<p class="mt-2 text-sm text-stone-500">Gifts you've saved for later. Tap the heart again to remove one.</p>

<div class="mt-8">
    <?php if ($products): ?>
        <?php product_grid($products); ?>
    <?php else: ?>
        <?php empty_state('heart', 'Nothing saved yet', 'Tap the heart on any gift to keep it here while you decide.',
            '<a href="' . e(url('shop')) . '" class="btn btn-primary">Browse gifts</a>'); ?>
    <?php endif; ?>
</div>
