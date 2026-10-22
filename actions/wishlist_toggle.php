<?php
$user = current_user();
$productId = input_int('product_id') ?? 0;

$removed = db()->prepare('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?');
$removed->execute([$user['id'], $productId]);
if ($removed->rowCount() > 0) {
    flash('success', 'Removed from your wishlist.');
    return;
}

$stmt = db()->prepare("SELECT name FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$productId]);
$name = $stmt->fetchColumn();
if ($name === false) {
    throw new ValidationException('Sorry, this product is no longer available.');
}
db()->prepare('INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)')->execute([$user['id'], $productId]);
flash('success', '"' . $name . '" was saved to your wishlist.');
