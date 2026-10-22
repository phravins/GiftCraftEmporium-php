<?php
require_once __DIR__ . '/config.php';

date_default_timezone_set(getenv('GIFTCRAFT_TIMEZONE') ?: (ini_get('date.timezone') ?: 'UTC'));

require_once __DIR__ . '/schema.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/catalog.php';
require_once __DIR__ . '/orders.php';

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('giftcraft_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}
