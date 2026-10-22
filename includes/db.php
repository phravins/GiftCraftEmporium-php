<?php
class Database {
    private static ?PDO $db = null;

    public static function getConnection(): PDO {
        if (!self::$db) {
            $dir = dirname(DB_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }

            $db = new PDO('sqlite:' . DB_PATH);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA foreign_keys = ON');
            $db->exec('PRAGMA busy_timeout = 5000');
            $db->exec('PRAGMA journal_mode = WAL');

            migrate_schema($db);
            self::$db = $db;
        }
        return self::$db;
    }
}

function db(): PDO {
    return Database::getConnection();
}

/**
 * Run $fn inside a write transaction. BEGIN IMMEDIATE takes the write lock up
 * front, so stock checks and decrements cannot interleave with another checkout.
 */
function db_transaction(callable $fn) {
    $db = db();
    $db->exec('BEGIN IMMEDIATE');
    try {
        $result = $fn($db);
        $db->exec('COMMIT');
        return $result;
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        throw $e;
    }
}
