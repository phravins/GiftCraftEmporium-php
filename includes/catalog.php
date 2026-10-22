<?php

const SORT_OPTIONS = [
    'newest'     => ['Newest', 'p.created_at DESC, p.id DESC'],
    'popular'    => ['Best selling', 'sold_qty DESC, p.created_at DESC'],
    'rating'     => ['Top rated', 'avg_rating DESC, review_count DESC, p.created_at DESC'],
    'price_asc'  => ['Price: low to high', 'p.price_cents ASC, p.id DESC'],
    'price_desc' => ['Price: high to low', 'p.price_cents DESC, p.id DESC'],
];

/** Product rows with store, category, cover image, rating and units sold. */
function product_select_sql(): string {
    return "SELECT p.*,
            c.name AS category_name, c.slug AS category_slug,
            s.name AS store_name, s.slug AS store_slug,
            (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id
              ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path,
            COALESCE(r.avg_rating, 0) AS avg_rating,
            COALESCE(r.review_count, 0) AS review_count,
            COALESCE(sold.sold_qty, 0) AS sold_qty
        FROM products p
        LEFT JOIN stores s ON s.user_id = p.seller_id
        LEFT JOIN categories c ON c.id = p.category_id
        LEFT JOIN (SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                   FROM reviews GROUP BY product_id) r ON r.product_id = p.id
        LEFT JOIN (SELECT oi.product_id, SUM(oi.quantity) AS sold_qty
                   FROM order_items oi JOIN seller_orders so ON so.id = oi.seller_order_id
                   WHERE so.status != 'cancelled' GROUP BY oi.product_id) sold ON sold.product_id = p.id";
}

function like_term(string $text): string {
    return '%' . addcslashes($text, '%_\\') . '%';
}

/**
 * Storefront search over active products.
 * Filters: q, category (slug), seller_id, min/max (cents), in_stock, exclude_id, sort.
 */
function search_products(array $filters, int $page = 1, int $perPage = PER_PAGE): array {
    $where = ["p.status = 'active'"];
    $params = [];

    if (($filters['q'] ?? '') !== '') {
        $where[] = "(p.name LIKE ? ESCAPE '\\' OR p.description LIKE ? ESCAPE '\\'
                     OR s.name LIKE ? ESCAPE '\\' OR c.name LIKE ? ESCAPE '\\')";
        array_push($params, ...array_fill(0, 4, like_term($filters['q'])));
    }
    if (($filters['category'] ?? '') !== '') {
        $where[] = 'c.slug = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['seller_id'])) {
        $where[] = 'p.seller_id = ?';
        $params[] = (int) $filters['seller_id'];
    }
    if (isset($filters['min']) && $filters['min'] !== null) {
        $where[] = 'p.price_cents >= ?';
        $params[] = (int) $filters['min'];
    }
    if (isset($filters['max']) && $filters['max'] !== null) {
        $where[] = 'p.price_cents <= ?';
        $params[] = (int) $filters['max'];
    }
    if (!empty($filters['in_stock'])) {
        $where[] = 'p.stock > 0';
    }
    if (!empty($filters['exclude_id'])) {
        $where[] = 'p.id != ?';
        $params[] = (int) $filters['exclude_id'];
    }
    $whereSql = ' WHERE ' . implode(' AND ', $where);

    $count = db()->prepare('SELECT COUNT(*) FROM products p
        LEFT JOIN stores s ON s.user_id = p.seller_id
        LEFT JOIN categories c ON c.id = p.category_id' . $whereSql);
    $count->execute($params);
    $pagination = paginate((int) $count->fetchColumn(), $perPage, $page);

    $order = SORT_OPTIONS[$filters['sort'] ?? 'newest'][1] ?? SORT_OPTIONS['newest'][1];
    $stmt = db()->prepare(product_select_sql() . $whereSql . " ORDER BY {$order} LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $perPage, $pagination['offset']]);

    return ['items' => $stmt->fetchAll(), 'pagination' => $pagination];
}

function find_product(int $id): ?array {
    $stmt = db()->prepare(product_select_sql() . ' WHERE p.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** A product the given seller owns, or a 404. */
function seller_product_or_404(int $productId, int $sellerId): array {
    $product = find_product($productId);
    if (!$product || (int) $product['seller_id'] !== $sellerId) {
        abort(404, 'Product not found.');
    }
    return $product;
}

function product_images(int $productId): array {
    $stmt = db()->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function discount_percent(array $product): ?int {
    $compare = (int) ($product['compare_at_cents'] ?? 0);
    $price = (int) $product['price_cents'];
    if ($compare > $price && $compare > 0) {
        return (int) round((1 - $price / $compare) * 100);
    }
    return null;
}

function all_categories(): array {
    return db()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

/** Categories that currently have something for sale, with a cover image. */
function categories_with_products(int $limit = 0): array {
    $sql = "SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count,
            (SELECT pi.path FROM products p2
               JOIN product_images pi ON pi.product_id = p2.id
              WHERE p2.category_id = c.id AND p2.status = 'active'
              ORDER BY p2.created_at DESC, pi.sort_order LIMIT 1) AS cover_path
        FROM categories c
        JOIN products p ON p.category_id = c.id AND p.status = 'active'
        GROUP BY c.id
        ORDER BY product_count DESC, c.name";
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    return db()->query($sql)->fetchAll();
}

function find_or_create_category(string $name): ?int {
    $name = trim(preg_replace('/\s+/', ' ', $name));
    if ($name === '') {
        return null;
    }
    $stmt = db()->prepare('SELECT id FROM categories WHERE name = ?');
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();
    if ($id) {
        return (int) $id;
    }
    db()->prepare('INSERT INTO categories (name, slug) VALUES (?, ?)')
        ->execute([$name, unique_slug('categories', $name)]);
    return (int) db()->lastInsertId();
}

/** Stores with active products, busiest first. */
function featured_stores(int $limit = 4): array {
    $stmt = db()->prepare("SELECT s.*, COUNT(p.id) AS product_count,
            (SELECT pi.path FROM products p2 JOIN product_images pi ON pi.product_id = p2.id
              WHERE p2.seller_id = s.user_id AND p2.status = 'active'
              ORDER BY p2.created_at DESC, pi.sort_order LIMIT 1) AS cover_path
        FROM stores s
        JOIN products p ON p.seller_id = s.user_id AND p.status = 'active'
        GROUP BY s.id
        ORDER BY product_count DESC, s.created_at
        LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function product_reviews(int $productId): array {
    $stmt = db()->prepare('SELECT r.*, u.name AS buyer_name FROM reviews r
        JOIN users u ON u.id = r.buyer_id WHERE r.product_id = ? ORDER BY r.created_at DESC, r.id DESC');
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/** Count of reviews per star rating, 5 down to 1. */
function rating_breakdown(array $reviews): array {
    $counts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($reviews as $review) {
        $counts[(int) $review['rating']]++;
    }
    return $counts;
}

/** Buyers can review a product once one of their packages containing it is delivered. */
function can_review(int $buyerId, int $productId): bool {
    $stmt = db()->prepare("SELECT 1 FROM order_items oi
        JOIN seller_orders so ON so.id = oi.seller_order_id
        JOIN orders o ON o.id = oi.order_id
        WHERE o.buyer_id = ? AND oi.product_id = ? AND so.status = 'delivered' LIMIT 1");
    $stmt->execute([$buyerId, $productId]);
    return (bool) $stmt->fetchColumn();
}
