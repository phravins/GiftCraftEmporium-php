<?php
$user = current_user();
$current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

$stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
if (!password_verify($current, (string) $stmt->fetchColumn())) {
    throw new ValidationException('Your current password is not correct.');
}
if (strlen($password) < 8) {
    throw new ValidationException('Your new password needs at least 8 characters.');
}
if (!hash_equals($password, $confirm)) {
    throw new ValidationException('The new passwords do not match.');
}

db()->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
    ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
session_regenerate_id(true);
flash('success', 'Your password has been changed.');
