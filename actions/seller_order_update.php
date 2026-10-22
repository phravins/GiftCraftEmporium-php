<?php
$user = current_user();
$packageId = input_int('seller_order_id') ?? 0;
$to = is_string($_POST['status'] ?? null) ? $_POST['status'] : '';
$note = input('note', 300);
$carrier = input('carrier', 60);
$tracking = input('tracking_number', 80);

if ($to === 'cancelled' && $note === '') {
    throw new ValidationException('Please tell the buyer why the order is being cancelled.');
}
if ($to === 'shipped' && ($carrier !== '' || $tracking !== '')) {
    $note = 'Shipped' . ($carrier !== '' ? " via {$carrier}" : '') . ($tracking !== '' ? " (tracking {$tracking})" : '') . '.';
}

db_transaction(function (PDO $db) use ($packageId, $user, $to, $note, $carrier, $tracking) {
    $stmt = $db->prepare('SELECT * FROM seller_orders WHERE id = ? AND seller_id = ?');
    $stmt->execute([$packageId, $user['id']]);
    $package = $stmt->fetch();
    if (!$package) {
        throw new ValidationException('We could not find that order.');
    }
    transition_seller_order($db, $package, $to, 'seller', $note ?: null, [
        'carrier'         => $carrier ?: null,
        'tracking_number' => $tracking ?: null,
    ]);
});

flash('success', [
    'processing' => 'Order accepted. The buyer can see that you are preparing it.',
    'shipped'    => 'Marked as shipped. The buyer can now follow the package.',
    'delivered'  => 'Marked as delivered. The buyer can now leave a review.',
    'cancelled'  => 'Order cancelled and the items were returned to stock.',
][$to] ?? 'Order updated.');
