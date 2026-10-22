<?php
$user = current_user();
$productId = input_int('product_id') ?? 0;
$rating = input_int('rating') ?? 0;
$title = input('title', 100);
$body = input('body', 2000);

if (!can_review((int) $user['id'], $productId)) {
    throw new ValidationException('You can review this product once your order of it has been delivered.');
}
if ($rating < 1 || $rating > 5) {
    throw new ValidationException('Please choose a star rating.');
}
if ($body === '') {
    throw new ValidationException('Please write a few words about the product.');
}

db()->prepare('INSERT INTO reviews (product_id, buyer_id, rating, title, body) VALUES (?, ?, ?, ?, ?)
    ON CONFLICT (product_id, buyer_id) DO UPDATE SET
        rating = excluded.rating, title = excluded.title, body = excluded.body, updated_at = CURRENT_TIMESTAMP')
    ->execute([$productId, $user['id'], $rating, $title ?: null, $body]);

flash('success', 'Thanks for your review!');
redirect(url('product', ['id' => $productId]) . '#reviews');
