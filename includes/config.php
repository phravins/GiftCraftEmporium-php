<?php
// Application settings. Environment variables override the defaults so the
// same code can run locally and on a server without edits.

define('APP_NAME', 'GiftCraft Emporium');
define('APP_TAGLINE', 'A marketplace for handmade & curated gifts');
define('APP_DEBUG', filter_var(getenv('GIFTCRAFT_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN));

define('BASE_PATH', dirname(__DIR__));
define('DB_PATH', getenv('GIFTCRAFT_DB_PATH') ?: BASE_PATH . '/data/giftcraft.sqlite');

// Money is stored in the smallest currency unit (cents / paise).
define('APP_CURRENCY', getenv('GIFTCRAFT_CURRENCY') ?: '$');

// Shipping is charged per seller package.
define('SHIPPING_FLAT_CENTS', 499);
define('FREE_SHIPPING_THRESHOLD_CENTS', 5000);

// Catalog
define('PER_PAGE', 12);
define('LOW_STOCK_THRESHOLD', 5);
define('MAX_CART_QTY', 99);

// Uploads
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('UPLOAD_URL', 'uploads');
define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024);
define('MAX_PRODUCT_IMAGES', 6);
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
]);
