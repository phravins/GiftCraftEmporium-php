<?php
$user = current_user();
$name = input('name', 80);
$email = strtolower(input('email', 120));
$phone = input('phone', 30);

require_fields(['Name' => $name, 'Email' => $email]);
if (!valid_email($email)) {
    throw new ValidationException('Please enter a valid email address.');
}
$taken = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id != ?');
$taken->execute([$email, $user['id']]);
if ($taken->fetchColumn()) {
    throw new ValidationException('Another account already uses that email.');
}

db()->prepare('UPDATE users SET name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
    ->execute([$name, $email, $phone ?: null, $user['id']]);
flash('success', 'Your profile has been updated.');
