<?php
// Router for PHP's built-in server:  php -S 127.0.0.1:8000 router.php
// Serves static assets and uploads, blocks private files, sends everything else to index.php.

$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

$private = '#(^|/)\.|^/(data|includes|actions|views)(/|$)|\.(sqlite|sqlite-wal|sqlite-shm|db|md|bat|sh|log)$#i';
$phpFile = preg_match('#\.(php|phtml|phar)$#i', $path) && $path !== '/index.php';
if (preg_match($private, $path) || $phpFile) {
    http_response_code(404);
    echo 'Not found';
    return true;
}

if ($path !== '/' && is_file(__DIR__ . $path)) {
    return false;
}

require __DIR__ . '/index.php';
