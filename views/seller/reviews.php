<?php
$page_title = 'Reviews';
$sellerId = (int) $user['id'];
$onlyUnanswered = query('filter') === 'unanswered';

$where = 'p.seller_id = ?' . ($onlyUnanswered ? ' AND r.seller_reply IS NULL' : '');
$countStmt = db()->prepare("SELECT COUNT(*) FROM reviews r JOIN products p ON p.id = r.product_id WHERE {$where}");
$countStmt->execute([$sellerId]);
$pg = paginate((int) $countStmt->fetchColumn(), 10, query_int('p', 1));

$stmt = db()->prepare("SELECT r.*, u.name AS buyer_name, p.name AS product_name, p.id AS product_id,
        (SELECT path FROM product_images WHERE product_id = p.id ORDER BY sort_order, id LIMIT 1) AS image_path
    FROM reviews r JOIN products p ON p.id = r.product_id JOIN users u ON u.id = r.buyer_id
    WHERE {$where} ORDER BY r.seller_reply IS NOT NULL, r.created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$sellerId, $pg['per_page'], $pg['offset']]);
$reviews = $stmt->fetchAll();
?>

<h1 class="page-title">Reviews</h1>
<p class="mt-1 text-sm text-stone-500">What buyers say about your products. A thoughtful reply builds trust with future customers.</p>

<nav class="mt-6 inline-flex gap-1 rounded-full bg-stone-200/60 p-1">
    <?php foreach (['' => 'All reviews', 'unanswered' => 'Awaiting reply'] as $key => $label): $active = ($key === 'unanswered') === $onlyUnanswered; ?>
        <a href="<?= e(url('seller_reviews', ['filter' => $key])) ?>" class="rounded-full px-3.5 py-1.5 text-sm font-medium <?= $active ? 'bg-white text-stone-900 shadow-sm' : 'text-stone-600 hover:text-stone-900' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>

<div class="mt-6 space-y-4">
    <?php if (!$reviews): ?>
        <?php empty_state('chat', $onlyUnanswered ? 'All caught up' : 'No reviews yet',
            $onlyUnanswered ? 'You have replied to every review.' : 'Buyers can review a product once their order has been delivered.'); ?>
    <?php endif; ?>

    <?php foreach ($reviews as $review): ?>
        <article class="card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <a href="<?= e(url('product', ['id' => $review['product_id']])) ?>#reviews" class="flex items-center gap-3">
                    <img src="<?= e(image_url($review['image_path'])) ?>" alt="" class="h-11 w-11 rounded-lg object-cover">
                    <span>
                        <span class="block text-sm font-semibold text-stone-900 hover:text-brand-700"><?= e($review['product_name']) ?></span>
                        <span class="block text-xs text-stone-500"><?= e($review['buyer_name']) ?> · <?= e(fmt_date($review['created_at'])) ?></span>
                    </span>
                </a>
                <?= stars($review['rating']) ?>
            </div>
            <?php if ($review['title']): ?><h3 class="mt-4 font-semibold text-stone-900"><?= e($review['title']) ?></h3><?php endif; ?>
            <p class="mt-1 whitespace-pre-line text-sm leading-relaxed text-stone-700"><?= e($review['body']) ?></p>

            <?= form_open('seller_review_reply', ['class' => 'mt-5 rounded-xl bg-stone-50 p-4']) ?>
                <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                <label class="label" for="reply-<?= (int) $review['id'] ?>"><?= $review['seller_reply'] ? 'Your public reply' : 'Reply publicly' ?></label>
                <textarea class="input min-h-20" id="reply-<?= (int) $review['id'] ?>" name="reply" maxlength="1000" placeholder="Thank the buyer or address their feedback..."><?= e((string) $review['seller_reply']) ?></textarea>
                <div class="mt-3 flex items-center justify-between gap-3">
                    <span class="text-xs text-stone-500"><?= $review['seller_replied_at'] ? 'Replied ' . e(fmt_date($review['seller_replied_at'])) : 'Leave empty to remove a reply.' ?></span>
                    <button type="submit" class="btn btn-dark btn-sm"><?= $review['seller_reply'] ? 'Update reply' : 'Post reply' ?></button>
                </div>
            </form>
        </article>
    <?php endforeach; ?>
</div>

<?php pagination($pg); ?>
