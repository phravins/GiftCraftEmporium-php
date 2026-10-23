<?php
$page_title = '3D Gallery';
$stmt = db()->query("SELECT p.id, p.name, p.price_cents,
        (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path
    FROM products p
    WHERE p.status = 'active' AND EXISTS (SELECT 1 FROM product_images pi WHERE pi.product_id = p.id)
    ORDER BY p.created_at DESC");
$products = $stmt->fetchAll();
?>

<div class="mx-auto max-w-2xl text-center">
    <p class="eyebrow">Gallery</p>
    <h1 class="page-title mt-2">See every gift up close</h1>
    <p class="mt-4 text-stone-600">Explore the collection in its purest form. Choose any piece to turn it around in the 3D viewer.</p>
</div>

<div class="mt-12">
    <?php if (!$products): ?>
        <?php empty_state('cube', 'The gallery is empty for now', 'Products with photos will appear here as soon as sellers list them.'); ?>
    <?php else: ?>
        <div class="columns-2 gap-4 sm:columns-3 lg:columns-4 [&>*]:mb-4">
            <?php foreach ($products as $product): ?>
                <a href="<?= e(url('visualize', ['id' => $product['id']])) ?>" class="group relative block break-inside-avoid overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200">
                    <img src="<?= e($product['image_path']) ?>" alt="<?= e($product['name']) ?>" loading="lazy" class="w-full transition duration-700 group-hover:scale-105">
                    <span class="absolute inset-0 flex flex-col justify-end bg-gradient-to-t from-stone-900/70 via-transparent to-transparent p-4 opacity-0 transition group-hover:opacity-100">
                        <span class="text-sm font-semibold text-white"><?= e($product['name']) ?></span>
                        <span class="mt-1 inline-flex items-center gap-1.5 text-xs text-white/80"><?= icon('cube', 'h-4 w-4') ?> View in 3D</span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
