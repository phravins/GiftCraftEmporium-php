<?php
$user = current_user();
$itemId = input_int('cart_item_id') ?? 0;
$quantity = input_int('quantity') ?? 1;

$stmt = db()->prepare('SELECT ci.id, p.stock, p.name FROM cart_items ci JOIN products p ON p.id = ci.product_id
    WHERE ci.id = ? AND ci.user_id = ?');
$stmt->execute([$itemId, $user['id']]);
$item = $stmt->fetch();
if (!$item) {
    abort(404, 'That item is no longer in your cart.');
}

if ($quantity <= 0) {
    db()->prepare('DELETE FROM cart_items WHERE id = ?')->execute([$itemId]);
    flash('success', '"' . $item['name'] . '" was removed from your cart.');
} else {
    $quantity = min($quantity, MAX_CART_QTY, max(1, (int) $item['stock']));
    db()->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?')->execute([$quantity, $itemId]);
    flash('success', 'Cart updated.');
}
