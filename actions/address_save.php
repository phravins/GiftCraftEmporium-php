<?php
$user = current_user();
$userId = (int) $user['id'];
$addressId = input_int('address_id');
$address = address_from_input();
$makeDefault = !empty($_POST['is_default']);

db_transaction(function (PDO $db) use ($userId, $addressId, $address, $makeDefault) {
    if (!$addressId) {
        insert_address($db, $userId, $address, $makeDefault);
        return;
    }
    $owned = $db->prepare('SELECT 1 FROM addresses WHERE id = ? AND user_id = ?');
    $owned->execute([$addressId, $userId]);
    if (!$owned->fetchColumn()) {
        throw new ValidationException('We could not find that address.');
    }
    if ($makeDefault) {
        $db->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$userId]);
    }
    $db->prepare('UPDATE addresses SET full_name = ?, phone = ?, line1 = ?, line2 = ?, city = ?, state = ?, postal_code = ?, country = ?,
            is_default = CASE WHEN ? THEN 1 ELSE is_default END
        WHERE id = ? AND user_id = ?')
        ->execute([
            $address['full_name'], $address['phone'], $address['line1'], $address['line2'] ?: null, $address['city'],
            $address['state'] ?: null, $address['postal_code'], $address['country'], $makeDefault ? 1 : 0, $addressId, $userId,
        ]);
});

flash('success', $addressId ? 'Address updated.' : 'Address added.');
