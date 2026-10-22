<?php
$user = current_user();
$userId = (int) $user['id'];
$addressId = input_int('address_id') ?? 0;

db_transaction(function (PDO $db) use ($userId, $addressId) {
    $db->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')->execute([$addressId, $userId]);
    // Keep one default address when the default was removed.
    $db->prepare('UPDATE addresses SET is_default = 1 WHERE id = (
            SELECT id FROM addresses WHERE user_id = ? ORDER BY id DESC LIMIT 1
        ) AND NOT EXISTS (SELECT 1 FROM addresses WHERE user_id = ? AND is_default = 1)')
        ->execute([$userId, $userId]);
});

flash('success', 'Address deleted.');
