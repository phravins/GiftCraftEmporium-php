<?php
// Prepares an empty marketplace database. Run from the command line:
//   php setup.php           create the database (safe to run again)
//   php setup.php --fresh   delete ALL data and uploads, then start over
//
// No sample data is created: sellers add categories and products, buyers place orders.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/includes/bootstrap.php';

if (in_array('--fresh', $argv, true)) {
    echo "This deletes every account, product, order and uploaded image.\nType 'yes' to continue: ";
    if (trim((string) fgets(STDIN)) !== 'yes') {
        exit("Cancelled.\n");
    }
    foreach ([DB_PATH, DB_PATH . '-wal', DB_PATH . '-shm'] as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (['products', 'stores'] as $dir) {
        foreach (glob(UPLOAD_DIR . "/{$dir}/*") ?: [] as $file) {
            unlink($file);
        }
    }
    echo "Old data removed.\n";
}

try {
    db();
    foreach (['products', 'stores'] as $dir) {
        if (!is_dir(UPLOAD_DIR . "/{$dir}")) {
            mkdir(UPLOAD_DIR . "/{$dir}", 0775, true);
        }
    }
    $users = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    echo 'Database ready at ' . DB_PATH . " ({$users} accounts).\n";
    echo "Start the server with:  php -S 127.0.0.1:8000 router.php\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Setup failed: ' . $e->getMessage() . "\n");
    exit(1);
}
