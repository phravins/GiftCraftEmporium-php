<?php
// Database schema. Tables are created on first connection; no seed data is
// inserted - sellers create categories and products, buyers create orders.

const SCHEMA_VERSION = 1;

function migrate_schema(PDO $db): void {
    $version = (int) $db->query('PRAGMA user_version')->fetchColumn();
    if ($version >= SCHEMA_VERSION) {
        return;
    }

    $db->exec('BEGIN IMMEDIATE');
    try {
        // Re-check under the write lock in case another request migrated first.
        $version = (int) $db->query('PRAGMA user_version')->fetchColumn();
        if ($version < 1) {
            foreach (schema_v1() as $sql) {
                $db->exec($sql);
            }
        }
        $db->exec('PRAGMA user_version = ' . SCHEMA_VERSION);
        $db->exec('COMMIT');
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        throw $e;
    }
}

function schema_v1(): array {
    return [
        "CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role TEXT NOT NULL CHECK (role IN ('buyer', 'seller')),
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE COLLATE NOCASE,
            phone TEXT,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        )",

        "CREATE TABLE stores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL UNIQUE REFERENCES users(id) ON DELETE CASCADE,
            name TEXT NOT NULL,
            slug TEXT NOT NULL UNIQUE,
            tagline TEXT,
            description TEXT,
            logo_path TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        )",

        "CREATE TABLE addresses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            full_name TEXT NOT NULL,
            phone TEXT NOT NULL,
            line1 TEXT NOT NULL,
            line2 TEXT,
            city TEXT NOT NULL,
            state TEXT,
            postal_code TEXT NOT NULL,
            country TEXT NOT NULL,
            is_default INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX idx_addresses_user ON addresses(user_id)",

        "CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE COLLATE NOCASE,
            slug TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            price_cents INTEGER NOT NULL CHECK (price_cents >= 0),
            compare_at_cents INTEGER CHECK (compare_at_cents IS NULL OR compare_at_cents >= 0),
            stock INTEGER NOT NULL DEFAULT 0 CHECK (stock >= 0),
            sku TEXT,
            status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'draft', 'archived')),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT
        )",
        "CREATE INDEX idx_products_seller ON products(seller_id)",
        "CREATE INDEX idx_products_category ON products(category_id)",
        "CREATE INDEX idx_products_status ON products(status, created_at)",

        "CREATE TABLE product_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            path TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0
        )",
        "CREATE INDEX idx_product_images_product ON product_images(product_id, sort_order)",

        "CREATE TABLE cart_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            quantity INTEGER NOT NULL CHECK (quantity > 0),
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (user_id, product_id)
        )",

        "CREATE TABLE wishlist_items (
            user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id, product_id)
        )",

        // One checkout by a buyer.
        "CREATE TABLE orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT NOT NULL UNIQUE,
            buyer_id INTEGER NOT NULL REFERENCES users(id),
            subtotal_cents INTEGER NOT NULL,
            shipping_cents INTEGER NOT NULL,
            total_cents INTEGER NOT NULL,
            payment_method TEXT NOT NULL DEFAULT 'cod',
            ship_name TEXT NOT NULL,
            ship_phone TEXT NOT NULL,
            ship_line1 TEXT NOT NULL,
            ship_line2 TEXT,
            ship_city TEXT NOT NULL,
            ship_state TEXT,
            ship_postal_code TEXT NOT NULL,
            ship_country TEXT NOT NULL,
            notes TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX idx_orders_buyer ON orders(buyer_id, created_at)",

        // The part of an order fulfilled by one seller (a package).
        "CREATE TABLE seller_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            seller_id INTEGER NOT NULL REFERENCES users(id),
            subtotal_cents INTEGER NOT NULL,
            shipping_cents INTEGER NOT NULL,
            total_cents INTEGER NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending'
                CHECK (status IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled')),
            carrier TEXT,
            tracking_number TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT,
            UNIQUE (order_id, seller_id)
        )",
        "CREATE INDEX idx_seller_orders_seller ON seller_orders(seller_id, status, created_at)",

        // Name, image and price are snapshots so history survives product edits.
        "CREATE TABLE order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
            seller_order_id INTEGER NOT NULL REFERENCES seller_orders(id) ON DELETE CASCADE,
            product_id INTEGER REFERENCES products(id) ON DELETE SET NULL,
            product_name TEXT NOT NULL,
            image_path TEXT,
            unit_price_cents INTEGER NOT NULL,
            quantity INTEGER NOT NULL CHECK (quantity > 0),
            line_total_cents INTEGER NOT NULL
        )",
        "CREATE INDEX idx_order_items_seller_order ON order_items(seller_order_id)",
        "CREATE INDEX idx_order_items_product ON order_items(product_id)",

        "CREATE TABLE order_events (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            seller_order_id INTEGER NOT NULL REFERENCES seller_orders(id) ON DELETE CASCADE,
            status TEXT NOT NULL,
            note TEXT,
            actor_role TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE INDEX idx_order_events_seller_order ON order_events(seller_order_id, id)",

        "CREATE TABLE reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
            buyer_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            rating INTEGER NOT NULL CHECK (rating BETWEEN 1 AND 5),
            title TEXT,
            body TEXT NOT NULL,
            seller_reply TEXT,
            seller_replied_at TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT,
            UNIQUE (product_id, buyer_id)
        )",
        "CREATE INDEX idx_reviews_product ON reviews(product_id, created_at)",
    ];
}
