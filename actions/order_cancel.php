<?php
$user = current_user();
$packageId = input_int('seller_order_id') ?? 0;

db_transaction(function (PDO $db) use ($packageId, $user) {
    $stmt = $db->prepare('SELECT so.* FROM seller_orders so JOIN orders o ON o.id = so.order_id
        WHERE so.id = ? AND o.buyer_id = ?');
    $stmt->execute([$packageId, $user['id']]);
    $package = $stmt->fetch();
    if (!$package) {
        throw new ValidationException('We could not find that package.');
    }
    transition_seller_order($db, $package, 'cancelled', 'buyer');
});

flash('success', 'The package has been cancelled. You will not be charged for it.');
