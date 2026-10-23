<?php
$user = current_user();
db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')
    ->execute([input_int('cart_item_id') ?? 0, $user['id']]);
flash('success', 'Item removed from your cart.');
