<?php
$status = (int) ($GLOBALS['error_status'] ?? 500);
$message = (string) ($GLOBALS['error_message'] ?? '');
$page_title = $status === 404 ? 'Page not found' : 'Something went wrong';
?>

<div class="mx-auto max-w-xl py-16 text-center">
    <p class="font-display text-7xl font-semibold text-brand-200"><?= $status ?></p>
    <h1 class="page-title mt-4"><?= e($page_title) ?></h1>
    <p class="mt-4 text-stone-600">
        <?= e($message ?: ($status === 404
            ? 'The page you were looking for has been moved, sold out or never existed.'
            : 'An unexpected error occurred. Please try again in a moment.')) ?>
    </p>
    <div class="mt-8 flex justify-center gap-3">
        <a href="index.php" class="btn btn-primary">Back to home</a>
        <a href="<?= e(url('shop')) ?>" class="btn btn-outline">Browse gifts</a>
    </div>
</div>
