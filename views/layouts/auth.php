<!DOCTYPE html>
<html lang="en">
<head><?php require __DIR__ . '/../partials/head.php'; ?></head>
<body class="min-h-screen">
<div class="grid min-h-screen lg:grid-cols-2">
    <div class="flex flex-col px-6 py-8 sm:px-12 lg:px-16">
        <a href="index.php" class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-white shadow-sm"><?= icon('gift', 'h-5 w-5') ?></span>
            <span class="font-display text-xl font-semibold tracking-tight text-stone-900">GiftCraft Emporium</span>
        </a>
        <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center py-10">
            <?php flash_messages(); ?>
            <?= $content ?>
        </div>
        <p class="text-center text-xs text-stone-400">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></p>
    </div>
    <div class="relative hidden lg:block">
        <img src="assets/img/hero_bg_full.jpg" alt="" class="absolute inset-0 h-full w-full object-cover object-right">
        <div class="absolute inset-0 bg-gradient-to-t from-stone-900/70 via-stone-900/10 to-transparent"></div>
        <div class="absolute inset-x-0 bottom-0 p-12 text-white">
            <p class="font-display text-3xl font-semibold leading-snug">“Every gift tells the story of the hands that made it.”</p>
            <p class="mt-3 text-sm text-white/80">Shop independent makers or open your own studio storefront.</p>
        </div>
    </div>
</div>
</body>
</html>
