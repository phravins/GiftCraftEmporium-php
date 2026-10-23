<?php
$user = current_user();
$sellerId = (int) $user['id'];
$productId = input_int('product_id');
$existing = $productId ? seller_product_or_404($productId, $sellerId) : null;

$name = input('name', 120);
$description = input('description', 5000);
$categoryName = input('category', 40);
$sku = input('sku', 40);
$price = parse_money(input('price', 20));
$compareInput = input('compare_at', 20);
$compareAt = $compareInput === '' ? null : parse_money($compareInput);
$stock = input_int('stock');
$status = ($_POST['status'] ?? '') === 'draft' ? 'draft' : 'active';

require_fields(['Product name' => $name, 'Description' => $description]);
if ($price === null || $price <= 0) {
    throw new ValidationException('Enter a valid price greater than zero, like 24.99.');
}
if ($compareInput !== '' && ($compareAt === null || $compareAt <= $price)) {
    throw new ValidationException('The compare-at price must be higher than the price (or leave it empty).');
}
if ($stock === null || $stock < 0 || $stock > 100000) {
    throw new ValidationException('Stock must be a whole number between 0 and 100,000.');
}

// Photos: validate everything before touching the disk or database.
$existingImages = $existing ? product_images($productId) : [];
$removeIds = array_map('intval', array_filter((array) ($_POST['remove_images'] ?? []), 'is_numeric'));
$removed = array_values(array_filter($existingImages, fn($img) => in_array((int) $img['id'], $removeIds, true)));
$uploads = uploaded_files('images');
foreach ($uploads as $file) {
    validate_image_upload($file);
}
$imageCount = count($existingImages) - count($removed) + count($uploads);
if ($imageCount > MAX_PRODUCT_IMAGES) {
    throw new ValidationException('A product can have up to ' . MAX_PRODUCT_IMAGES . ' photos.');
}
if ($status === 'active' && $imageCount === 0) {
    throw new ValidationException('Add at least one photo before publishing, or save the product as a draft.');
}

$stored = [];
try {
    foreach ($uploads as $file) {
        $stored[] = store_image_upload($file, 'products');
    }
    $productId = db_transaction(function (PDO $db) use ($existing, $sellerId, $name, $description, $categoryName, $sku, $price, $compareAt, $stock, $status, $removed, $stored) {
        $categoryId = find_or_create_category($categoryName);
        $values = [$categoryId, $name, $description, $price, $compareAt, $stock, $sku ?: null, $status];

        if ($existing) {
            $productId = (int) $existing['id'];
            $db->prepare('UPDATE products SET category_id = ?, name = ?, description = ?, price_cents = ?, compare_at_cents = ?,
                    stock = ?, sku = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND seller_id = ?')
                ->execute([...$values, $productId, $sellerId]);
        } else {
            $db->prepare('INSERT INTO products (category_id, name, description, price_cents, compare_at_cents, stock, sku, status, seller_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([...$values, $sellerId]);
            $productId = (int) $db->lastInsertId();
        }

        $deleteImage = $db->prepare('DELETE FROM product_images WHERE id = ? AND product_id = ?');
        foreach ($removed as $image) {
            $deleteImage->execute([$image['id'], $productId]);
        }
        $addImage = $db->prepare('INSERT INTO product_images (product_id, path, sort_order) VALUES (?, ?, ?)');
        foreach ($stored as $i => $path) {
            $addImage->execute([$productId, $path, 1000 + $i]);
        }

        // Renumber photos with the chosen cover first.
        $ids = $db->prepare('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
        $ids->execute([$productId]);
        $ids = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
        $cover = input_int('cover_image_id');
        if ($cover && in_array($cover, $ids, true)) {
            $ids = array_merge([$cover], array_diff($ids, [$cover]));
        }
        $reorder = $db->prepare('UPDATE product_images SET sort_order = ? WHERE id = ?');
        foreach (array_values($ids) as $position => $id) {
            $reorder->execute([$position, $id]);
        }
        return $productId;
    });
} catch (Throwable $e) {
    array_map('delete_upload', $stored);
    throw $e;
}

// Keep files that order history still points at.
$inOrders = db()->prepare('SELECT 1 FROM order_items WHERE image_path = ? LIMIT 1');
foreach ($removed as $image) {
    $inOrders->execute([$image['path']]);
    if (!$inOrders->fetchColumn()) {
        delete_upload($image['path']);
    }
}

if ($existing) {
    flash('success', 'Your changes to "' . $name . '" have been saved.');
    redirect(url('seller_product', ['id' => $productId]));
}
flash('success', $status === 'active' ? '"' . $name . '" is now live on the marketplace!' : '"' . $name . '" was saved as a draft.');
redirect(url('seller_products'));
