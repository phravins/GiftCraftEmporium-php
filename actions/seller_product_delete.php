<?php
$user = current_user();
$product = seller_product_or_404(input_int('product_id') ?? 0, (int) $user['id']);
$productId = (int) $product['id'];

$hasOrders = db()->prepare('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1');
$hasOrders->execute([$productId]);

if ($hasOrders->fetchColumn()) {
    // Ordered products are archived so buyers' and sellers' order history keeps working.
    db_transaction(function (PDO $db) use ($productId) {
        $db->prepare("UPDATE products SET status = 'archived', updated_at = CURRENT_TIMESTAMP WHERE id = ?")->execute([$productId]);
        $db->prepare('DELETE FROM cart_items WHERE product_id = ?')->execute([$productId]);
        $db->prepare('DELETE FROM wishlist_items WHERE product_id = ?')->execute([$productId]);
    });
    flash('success', '"' . $product['name'] . '" was archived. It is hidden from buyers but kept in your order history.');
} else {
    $images = product_images($productId);
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$productId]);
    foreach ($images as $image) {
        delete_upload($image['path']);
    }
    flash('success', '"' . $product['name'] . '" was deleted.');
}
redirect(url('seller_products'));
