<?php

/** The signed-in user (fresh from the database), or null for guests. */
function current_user(bool $refresh = false): ?array {
    static $user = false;
    if ($user === false || $refresh) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, role, name, email, phone, created_at FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
            if (!$user) {
                unset($_SESSION['user_id']);
            }
        }
    }
    return $user;
}

function is_buyer(): bool {
    return (current_user()['role'] ?? null) === 'buyer';
}

function is_seller(): bool {
    return (current_user()['role'] ?? null) === 'seller';
}

function login_user(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    current_user(true);
}

function logout_user(): void {
    $_SESSION = [];
    session_regenerate_id(true);
    current_user(true);
}

/** Where a user lands after signing in. */
function user_home(array $user): string {
    return $user['role'] === 'seller' ? url('seller_dashboard') : 'index.php';
}

function seller_store(int $sellerId): ?array {
    static $cache = [];
    if (!array_key_exists($sellerId, $cache)) {
        $stmt = db()->prepare('SELECT * FROM stores WHERE user_id = ?');
        $stmt->execute([$sellerId]);
        $cache[$sellerId] = $stmt->fetch() ?: null;
    }
    return $cache[$sellerId];
}

function cart_count(int $userId): int {
    $stmt = db()->prepare('SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/** Product ids on the current buyer's wishlist (empty for everyone else). */
function wishlist_ids(): array {
    static $ids = null;
    if ($ids === null) {
        $ids = [];
        if (is_buyer()) {
            $stmt = db()->prepare('SELECT product_id FROM wishlist_items WHERE user_id = ?');
            $stmt->execute([current_user()['id']]);
            $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        }
    }
    return $ids;
}
