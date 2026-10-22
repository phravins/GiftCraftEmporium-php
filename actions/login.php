<?php
$email = input('email', 120);
$password = $_POST['password'] ?? '';
if ($email === '' || !is_string($password) || $password === '') {
    throw new ValidationException('Enter your email and password.');
}

$stmt = db()->prepare('SELECT id, name, role, password_hash FROM users WHERE email = ?');
$stmt->execute([$email]);
$account = $stmt->fetch();

// Verify against a dummy hash when the email is unknown so timing doesn't reveal which accounts exist.
$valid = password_verify($password, $account['password_hash'] ?? password_hash('timing-dummy', PASSWORD_DEFAULT));
if (!$account || !$valid) {
    throw new ValidationException('That email and password combination is not right.');
}
if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_DEFAULT), $account['id']]);
}

$intended = safe_return_url($_SESSION['intended'] ?? null);
login_user((int) $account['id']);
flash('success', 'Welcome back, ' . explode(' ', $account['name'])[0] . '!');
redirect($intended ?? user_home(current_user()));
