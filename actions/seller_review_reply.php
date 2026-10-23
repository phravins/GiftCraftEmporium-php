<?php
$user = current_user();
$reviewId = input_int('review_id') ?? 0;
$reply = input('reply', 1000);

$stmt = db()->prepare('UPDATE reviews SET seller_reply = ?, seller_replied_at = CASE WHEN ? IS NULL THEN NULL ELSE CURRENT_TIMESTAMP END
    WHERE id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)');
$stmt->execute([$reply ?: null, $reply ?: null, $reviewId, $user['id']]);
if ($stmt->rowCount() === 0) {
    throw new ValidationException('We could not find that review.');
}
flash('success', $reply ? 'Your reply is now public.' : 'Your reply was removed.');
