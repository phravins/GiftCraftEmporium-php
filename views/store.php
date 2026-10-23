<?php
$stmt = db()->prepare('SELECT s.*, u.created_at AS member_since FROM stores s JOIN users u ON u.id = s.user_id WHERE s.slug = ?');
$stmt->execute([query('s')]);
$store = $stmt->fetch();
if (!$store) {
    abort(404, 'We could not find that shop.');
}
$page_title = $store['name'];

$sort = array_key_exists(query('sort'), SORT_OPTIONS) ? query('sort') : 'newest';
$result = search_products(['seller_id' => $store['user_id'], 'q' => query('q'), 'sort' => $sort], query_int('p', 1));

$statsStmt = db()->prepare("SELECT
        (SELECT COUNT(*) FROM products WHERE seller_id = :id AND status = 'active') AS products,
        (SELECT AVG(r.rating) FROM reviews r JOIN products p ON p.id = r.product_id WHERE p.seller_id = :id) AS rating,
        (SELECT COUNT(*) FROM reviews r JOIN products p ON p.id = r.product_id WHERE p.seller_id = :id) AS reviews,
        (SELECT COUNT(*) FROM seller_orders WHERE seller_id = :id AND status = 'delivered') AS delivered");
$statsStmt->execute(['id' => $store['user_id']]);
$stats = $statsStmt->fetch();
?>

<section class="card overflow-hidden">
    <div class="h-32 bg-gradient-to-r from-brand-100 via-brand-50 to-stone-100 sm:h-40"></div>
    <div class="px-6 pb-8 sm:px-10">
        <div class="-mt-12 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex items-end gap-5">
                <?php if ($store['logo_path']): ?>
                    <img src="<?= e($store['logo_path']) ?>" alt="<?= e($store['name']) ?> logo" class="h-24 w-24 rounded-2xl border-4 border-white object-cover shadow-md">
                <?php else: ?>
                    <span class="flex h-24 w-24 items-center justify-center rounded-2xl border-4 border-white bg-brand-600 font-display text-4xl font-semibold text-white shadow-md"><?= e(strtoupper(str_cut($store['name'], 1))) ?></span>
                <?php endif; ?>
                <div class="pb-1">
                    <h1 class="page-title"><?= e($store['name']) ?></h1>
                    <?php if ($store['tagline']): ?><p class="mt-1 text-stone-600"><?= e($store['tagline']) ?></p><?php endif; ?>
                </div>
            </div>
            <dl class="flex gap-8 text-sm">
                <div><dt class="text-stone-500">Products</dt><dd class="mt-0.5 text-lg font-semibold text-stone-900"><?= (int) $stats['products'] ?></dd></div>
                <div><dt class="text-stone-500">Rating</dt>
                    <dd class="mt-0.5 flex items-center gap-1 text-lg font-semibold text-stone-900">
                        <?php if ((int) $stats['reviews'] > 0): ?><?= star_icon('h-4 w-4', true) ?> <?= number_format((float) $stats['rating'], 1) ?><?php else: ?>–<?php endif; ?>
                    </dd></div>
                <div><dt class="text-stone-500">Orders delivered</dt><dd class="mt-0.5 text-lg font-semibold text-stone-900"><?= (int) $stats['delivered'] ?></dd></div>
            </dl>
        </div>
        <?php if ($store['description']): ?>
            <p class="mt-6 max-w-3xl whitespace-pre-line text-sm leading-relaxed text-stone-600"><?= e($store['description']) ?></p>
        <?php endif; ?>
        <p class="mt-4 text-xs text-stone-400">On GiftCraft since <?= e(fmt_date($store['member_since'], 'F Y')) ?></p>
    </div>
</section>

<div class="mt-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="section-title">Shop the collection</h2>
    <form method="get" action="index.php" class="flex flex-wrap items-center gap-2">
        <input type="hidden" name="page" value="store">
        <input type="hidden" name="s" value="<?= e($store['slug']) ?>">
        <label class="relative">
            <span class="sr-only">Search this shop</span>
            <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-stone-400"><?= icon('search', 'h-4 w-4') ?></span>
            <input type="search" name="q" value="<?= e(query('q')) ?>" placeholder="Search this shop" class="input w-56 rounded-full py-2 pl-9">
        </label>
        <select name="sort" class="input w-auto rounded-full py-2 pr-9" data-autosubmit aria-label="Sort">
            <?php foreach (SORT_OPTIONS as $value => [$label]): ?>
                <option value="<?= $value ?>" <?= $sort === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="mt-8">
    <?php if ($result['items']): ?>
        <?php product_grid($result['items']); ?>
        <?php pagination($result['pagination']); ?>
    <?php else: ?>
        <?php empty_state('tag', query('q') !== '' ? 'Nothing matches that search' : 'No products yet',
            query('q') !== '' ? 'Try another word, or clear the search to see everything in this shop.' : 'This shop has not listed any products yet.'); ?>
    <?php endif; ?>
</div>
