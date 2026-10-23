<?php
$user = current_user();
$userId = (int) $user['id'];
$addressChoice = $_POST['address_id'] ?? 'new';

if ($addressChoice === 'new') {
    $address = address_from_input('new_');
} else {
    $stmt = db()->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->execute([(int) $addressChoice, $userId]);
    $address = $stmt->fetch();
    if (!$address) {
        throw new ValidationException('Please choose a shipping address.');
    }
}

$payment = $_POST['payment_method'] ?? 'cod';
if (!is_string($payment) || !isset(PAYMENT_METHODS[$payment])) {
    throw new ValidationException('Please choose a payment method.');
}
$notes = input('notes', 500);
$saveAddress = $addressChoice === 'new' && !empty($_POST['save_address']);

$order = db_transaction(function (PDO $db) use ($userId, $address, $payment, $notes, $saveAddress) {
    $order = create_order_from_cart($db, $userId, $address, $payment, $notes);
    if ($saveAddress) {
        insert_address($db, $userId, $address, false);
    }
    return $order;
});

flash('success', "Thank you! Order {$order['order_number']} has been placed. Each seller will confirm their package shortly.");
redirect(url('order', ['id' => $order['id']]));
