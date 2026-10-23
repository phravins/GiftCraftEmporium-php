<?php
$role = ($_POST['role'] ?? '') === 'seller' ? 'seller' : 'buyer';
$name = input('name', 80);
$email = strtolower(input('email', 120));
$phone = input('phone', 30);
$storeName = input('store_name', 60);
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';

require_fields(['Name' => $name, 'Email' => $email]);
if (!valid_email($email)) {
    throw new ValidationException('Please enter a valid email address.');
}
if (strlen($password) < 8) {
    throw new ValidationException('Your password needs at least 8 characters.');
}
if (!hash_equals($password, $confirm)) {
    throw new ValidationException('The passwords do not match.');
}
if ($role === 'seller' && $storeName === '') {
    throw new ValidationException('Please give your shop a name.');
}

$exists = db()->prepare('SELECT 1 FROM users WHERE email = ?');
$exists->execute([$email]);
if ($exists->fetchColumn()) {
    throw new ValidationException('An account with this email already exists. Try signing in instead.');
}

$userId = db_transaction(function (PDO $db) use ($role, $name, $email, $phone, $password, $storeName) {
    $db->prepare('INSERT INTO users (role, name, email, phone, password_hash) VALUES (?, ?, ?, ?, ?)')
        ->execute([$role, $name, $email, $phone ?: null, password_hash($password, PASSWORD_DEFAULT)]);
    $userId = (int) $db->lastInsertId();
    if ($role === 'seller') {
        $db->prepare('INSERT INTO stores (user_id, name, slug) VALUES (?, ?, ?)')
            ->execute([$userId, $storeName, unique_slug('stores', $storeName)]);
    }
    return $userId;
});

$intended = safe_return_url($_SESSION['intended'] ?? null);
login_user($userId);

if ($role === 'seller') {
    flash('success', "Your shop \"{$storeName}\" is open! Add your first product to start selling.");
    redirect(url('seller_dashboard'));
}
flash('success', 'Welcome to GiftCraft, ' . explode(' ', $name)[0] . '!');
redirect($intended ?? 'index.php');
