<?php

/** Allowed status changes for a seller's package. */
const SELLER_ORDER_FLOW = [
    'pending'    => ['processing', 'cancelled'],
    'processing' => ['shipped', 'cancelled'],
    'shipped'    => ['delivered'],
    'delivered'  => [],
    'cancelled'  => [],
];

const PAYMENT_METHODS = [
    'cod' => 'Cash on delivery',
];

function shipping_for(int $subtotalCents): int {
    return $subtotalCents >= FREE_SHIPPING_THRESHOLD_CENTS ? 0 : SHIPPING_FLAT_CENTS;
}

/** The buyer's cart with live product data and any problem that blocks checkout. */
function cart_lines(int $userId): array {
    $stmt = db()->prepare("SELECT ci.id AS cart_item_id, ci.quantity,
            p.id AS product_id, p.name, p.price_cents, p.compare_at_cents, p.stock, p.status, p.seller_id,
            s.name AS store_name, s.slug AS store_slug,
            (SELECT pi.path FROM product_images pi WHERE pi.product_id = p.id
              ORDER BY pi.sort_order, pi.id LIMIT 1) AS image_path
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        LEFT JOIN stores s ON s.user_id = p.seller_id
        WHERE ci.user_id = ?
        ORDER BY s.name, ci.id");
    $stmt->execute([$userId]);

    $lines = [];
    foreach ($stmt->fetchAll() as $line) {
        $line['line_total'] = (int) $line['price_cents'] * (int) $line['quantity'];
        $line['issue'] = null;
        if ($line['status'] !== 'active') {
            $line['issue'] = 'This item is no longer available.';
        } elseif ((int) $line['stock'] === 0) {
            $line['issue'] = 'Sold out.';
        } elseif ((int) $line['stock'] < (int) $line['quantity']) {
            $line['issue'] = 'Only ' . (int) $line['stock'] . ' left - please lower the quantity.';
        }
        $lines[] = $line;
    }
    return $lines;
}

/** Groups cart lines into one package per seller and totals everything. */
function summarize_cart(array $lines): array {
    $groups = [];
    foreach ($lines as $line) {
        $sellerId = (int) $line['seller_id'];
        $groups[$sellerId] ??= [
            'seller_id'  => $sellerId,
            'store_name' => $line['store_name'],
            'store_slug' => $line['store_slug'],
            'lines'      => [],
            'subtotal'   => 0,
        ];
        $groups[$sellerId]['lines'][] = $line;
        $groups[$sellerId]['subtotal'] += $line['line_total'];
    }

    $summary = ['groups' => [], 'subtotal' => 0, 'shipping' => 0, 'total' => 0, 'item_count' => 0, 'has_issues' => false];
    foreach ($groups as $group) {
        $group['shipping'] = shipping_for($group['subtotal']);
        $group['total'] = $group['subtotal'] + $group['shipping'];
        $summary['groups'][] = $group;
        $summary['subtotal'] += $group['subtotal'];
        $summary['shipping'] += $group['shipping'];
        foreach ($group['lines'] as $line) {
            $summary['item_count'] += (int) $line['quantity'];
            $summary['has_issues'] = $summary['has_issues'] || $line['issue'] !== null;
        }
    }
    $summary['total'] = $summary['subtotal'] + $summary['shipping'];
    return $summary;
}

function generate_order_number(PDO $db): string {
    $check = $db->prepare('SELECT 1 FROM orders WHERE order_number = ?');
    do {
        $number = 'GC' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $check->execute([$number]);
    } while ($check->fetchColumn());
    return $number;
}

/**
 * Turns the buyer's cart into an order with one package per seller.
 * Must run inside db_transaction() so stock checks and decrements are atomic.
 */
function create_order_from_cart(PDO $db, int $buyerId, array $address, string $paymentMethod, string $notes): array {
    $lines = cart_lines($buyerId);
    if (!$lines) {
        throw new ValidationException('Your cart is empty.');
    }
    foreach ($lines as $line) {
        if ($line['issue']) {
            throw new ValidationException('"' . $line['name'] . '": ' . $line['issue'] . ' Please review your cart.');
        }
    }
    $summary = summarize_cart($lines);

    $orderNumber = generate_order_number($db);
    $db->prepare('INSERT INTO orders (order_number, buyer_id, subtotal_cents, shipping_cents, total_cents, payment_method,
            ship_name, ship_phone, ship_line1, ship_line2, ship_city, ship_state, ship_postal_code, ship_country, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $orderNumber, $buyerId, $summary['subtotal'], $summary['shipping'], $summary['total'], $paymentMethod,
            $address['full_name'], $address['phone'], $address['line1'], $address['line2'] ?: null,
            $address['city'], $address['state'] ?: null, $address['postal_code'], $address['country'], $notes ?: null,
        ]);
    $orderId = (int) $db->lastInsertId();

    $insertPackage = $db->prepare('INSERT INTO seller_orders (order_id, seller_id, subtotal_cents, shipping_cents, total_cents)
        VALUES (?, ?, ?, ?, ?)');
    $insertItem = $db->prepare('INSERT INTO order_items (order_id, seller_order_id, product_id, product_name, image_path,
            unit_price_cents, quantity, line_total_cents) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $takeStock = $db->prepare('UPDATE products SET stock = stock - ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ? AND status = ? AND stock >= ?');

    foreach ($summary['groups'] as $group) {
        $insertPackage->execute([$orderId, $group['seller_id'], $group['subtotal'], $group['shipping'], $group['total']]);
        $packageId = (int) $db->lastInsertId();

        foreach ($group['lines'] as $line) {
            $takeStock->execute([$line['quantity'], $line['product_id'], 'active', $line['quantity']]);
            if ($takeStock->rowCount() !== 1) {
                throw new ValidationException('"' . $line['name'] . '" just sold out. Please review your cart.');
            }
            $insertItem->execute([
                $orderId, $packageId, $line['product_id'], $line['name'], $line['image_path'],
                $line['price_cents'], $line['quantity'], $line['line_total'],
            ]);
        }
        add_order_event($db, $packageId, 'pending', 'Order placed.', 'buyer');
    }

    $db->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$buyerId]);

    return ['id' => $orderId, 'order_number' => $orderNumber];
}

function add_order_event(PDO $db, int $sellerOrderId, string $status, ?string $note, string $actorRole): void {
    $db->prepare('INSERT INTO order_events (seller_order_id, status, note, actor_role) VALUES (?, ?, ?, ?)')
        ->execute([$sellerOrderId, $status, $note, $actorRole]);
}

/**
 * Moves a package to a new status, restocking items when it is cancelled.
 * Call inside db_transaction().
 */
function transition_seller_order(PDO $db, array $package, string $to, string $actorRole, ?string $note = null, array $shipping = []): void {
    $from = $package['status'];
    if (!in_array($to, SELLER_ORDER_FLOW[$from] ?? [], true)) {
        throw new ValidationException('This package is ' . strtolower(status_meta($from)[0]) . ' and can no longer be changed that way.');
    }

    if ($to === 'cancelled') {
        $db->prepare('UPDATE products SET stock = stock + (
                SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.seller_order_id = ? AND oi.product_id = products.id
            ), updated_at = CURRENT_TIMESTAMP
            WHERE id IN (SELECT product_id FROM order_items WHERE seller_order_id = ? AND product_id IS NOT NULL)')
            ->execute([$package['id'], $package['id']]);
    }

    if ($to === 'shipped') {
        $db->prepare('UPDATE seller_orders SET status = ?, carrier = ?, tracking_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$to, $shipping['carrier'] ?? null, $shipping['tracking_number'] ?? null, $package['id']]);
    } else {
        $db->prepare('UPDATE seller_orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$to, $package['id']]);
    }

    $defaults = [
        'processing' => 'The seller confirmed your order and is preparing it.',
        'shipped'    => 'Your package is on its way.',
        'delivered'  => 'Package delivered.',
        'cancelled'  => $actorRole === 'buyer' ? 'Cancelled by the buyer.' : 'Cancelled by the seller.',
    ];
    add_order_event($db, (int) $package['id'], $to, $note ?: ($defaults[$to] ?? null), $actorRole);
}

/** One status for a whole order, derived from its packages. */
function overall_order_status(array $packageStatuses): string {
    $active = array_values(array_filter($packageStatuses, fn($s) => $s !== 'cancelled'));
    if (!$active) {
        return 'cancelled';
    }
    $counts = array_count_values($active);
    $total = count($active);
    if (($counts['delivered'] ?? 0) === $total) {
        return 'delivered';
    }
    if (($counts['shipped'] ?? 0) + ($counts['delivered'] ?? 0) === $total) {
        return 'shipped';
    }
    if (($counts['shipped'] ?? 0) + ($counts['delivered'] ?? 0) > 0) {
        return 'partially_shipped';
    }
    return isset($counts['processing']) ? 'processing' : 'pending';
}

/** Packages for the given order ids, each with its items and timeline. */
function packages_for_orders(array $orderIds, ?int $sellerId = null): array {
    if (!$orderIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($orderIds), '?'));
    $params = array_map('intval', $orderIds);
    $sql = "SELECT so.*, s.name AS store_name, s.slug AS store_slug
        FROM seller_orders so LEFT JOIN stores s ON s.user_id = so.seller_id
        WHERE so.order_id IN ($in)";
    if ($sellerId !== null) {
        $sql .= ' AND so.seller_id = ?';
        $params[] = $sellerId;
    }
    $stmt = db()->prepare($sql . ' ORDER BY so.id');
    $stmt->execute($params);
    $packages = [];
    foreach ($stmt->fetchAll() as $package) {
        $package['items'] = [];
        $package['events'] = [];
        $packages[(int) $package['id']] = $package;
    }
    if (!$packages) {
        return [];
    }

    $ids = array_keys($packages);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $items = db()->prepare("SELECT * FROM order_items WHERE seller_order_id IN ($in) ORDER BY id");
    $items->execute($ids);
    foreach ($items->fetchAll() as $item) {
        $packages[(int) $item['seller_order_id']]['items'][] = $item;
    }
    $events = db()->prepare("SELECT * FROM order_events WHERE seller_order_id IN ($in) ORDER BY id");
    $events->execute($ids);
    foreach ($events->fetchAll() as $event) {
        $packages[(int) $event['seller_order_id']]['events'][] = $event;
    }

    $byOrder = [];
    foreach ($packages as $package) {
        $byOrder[(int) $package['order_id']][] = $package;
    }
    return $byOrder;
}

/** Reads and validates address fields posted as {$prefix}full_name, {$prefix}city... */
function address_from_input(string $prefix = ''): array {
    $address = [
        'full_name'   => input($prefix . 'full_name', 80),
        'phone'       => input($prefix . 'phone', 30),
        'line1'       => input($prefix . 'line1', 120),
        'line2'       => input($prefix . 'line2', 120),
        'city'        => input($prefix . 'city', 60),
        'state'       => input($prefix . 'state', 60),
        'postal_code' => input($prefix . 'postal_code', 20),
        'country'     => input($prefix . 'country', 60),
    ];
    require_fields([
        'Full name'   => $address['full_name'],
        'Phone'       => $address['phone'],
        'Address'     => $address['line1'],
        'City'        => $address['city'],
        'Postal code' => $address['postal_code'],
        'Country'     => $address['country'],
    ]);
    return $address;
}

function insert_address(PDO $db, int $userId, array $address, bool $makeDefault): int {
    $hasAddresses = $db->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?');
    $hasAddresses->execute([$userId]);
    $makeDefault = $makeDefault || (int) $hasAddresses->fetchColumn() === 0;
    if ($makeDefault) {
        $db->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$userId]);
    }
    $db->prepare('INSERT INTO addresses (user_id, full_name, phone, line1, line2, city, state, postal_code, country, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([
            $userId, $address['full_name'], $address['phone'], $address['line1'], $address['line2'] ?: null,
            $address['city'], $address['state'] ?: null, $address['postal_code'], $address['country'], $makeDefault ? 1 : 0,
        ]);
    return (int) $db->lastInsertId();
}

function format_address(array $order, string $separator = "\n"): string {
    $cityLine = trim(implode(', ', array_filter([$order['ship_city'], $order['ship_state']])) . ' ' . $order['ship_postal_code']);
    return implode($separator, array_filter([
        $order['ship_line1'], $order['ship_line2'], $cityLine, $order['ship_country'],
    ]));
}
