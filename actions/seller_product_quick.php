<?php
$user = current_user();
$product = seller_product_or_404(input_int('product_id') ?? 0, (int) $user['id']);
$stock = input_int('stock');
if ($stock === null || $stock < 0 || $stock > 100000) {
    throw new ValidationException('Stock must be a whole number between 0 and 100,000.');
}

db()->prepare('UPDATE products SET stock = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$stock, $product['id']]);
flash('success', 'Stock for "' . $product['name'] . '" set to ' . $stock . '.');
