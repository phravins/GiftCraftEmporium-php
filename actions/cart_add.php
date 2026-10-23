<?php
$user = current_user();
$productId = input_int('product_id') ?? 0;
$quantity = max(1, min(MAX_CART_QTY, input_int('quantity') ?? 1));

$stmt = db()->prepare('SELECT id, name, stock, status FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product || $product['status'] !== 'active') {
    throw new ValidationException('Sorry, this product is no longer available.');
}
$stock = (int) $product['stock'];
if ($stock <= 0) {
    throw new ValidationException('Sorry, "' . $product['name'] . '" just sold out.');
}

$stmt = db()->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
$stmt->execute([$user['id'], $productId]);
$newQuantity = (int) $stmt->fetchColumn() + $quantity;
$limit = min($stock, MAX_CART_QTY);

db()->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)
    ON CONFLICT (user_id, product_id) DO UPDATE SET quantity = excluded.quantity')
    ->execute([$user['id'], $productId, min($newQuantity, $limit)]);

if ($newQuantity > $limit) {
    flash('info', 'Only ' . $limit . ' of "' . $product['name'] . '" available - your cart has the maximum.');
} else {
    flash('success', '"' . $product['name'] . '" was added to your cart.');
}
