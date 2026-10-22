<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($page_title ? $page_title . ' · ' . APP_NAME : APP_NAME . ' - ' . APP_TAGLINE) ?></title>
<meta name="description" content="<?= e(APP_TAGLINE) ?>">
<link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com?plugins=forms"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    cream: '#faf7f2',
                    brand: {
                        50: '#fbf5f1', 100: '#f6e8de', 200: '#ecd0bc', 300: '#dfb092', 400: '#d08a66',
                        500: '#c46f48', 600: '#b5593a', 700: '#964631', 800: '#7a3b2d', 900: '#643227', 950: '#361812'
                    }
                },
                fontFamily: {
                    sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    display: ['Fraunces', 'Georgia', 'serif']
                }
            }
        }
    };
</script>
<style type="text/tailwindcss">
    @layer base {
        body { @apply bg-cream text-stone-800 font-sans antialiased; }
    }
    @layer components {
        .btn { @apply inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-sm font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50; }
        .btn-primary { @apply bg-brand-600 text-white shadow-sm hover:bg-brand-700; }
        .btn-dark { @apply bg-stone-900 text-white shadow-sm hover:bg-stone-700; }
        .btn-outline { @apply border border-stone-300 bg-white text-stone-800 hover:border-stone-400 hover:bg-stone-50; }
        .btn-ghost { @apply text-stone-700 hover:bg-stone-100; }
        .btn-danger { @apply border border-red-200 bg-white text-red-700 hover:border-red-300 hover:bg-red-50; }
        .btn-sm { @apply px-3.5 py-1.5 text-xs; }
        .btn-lg { @apply px-7 py-3.5 text-base; }
        .card { @apply rounded-2xl border border-stone-200/80 bg-white shadow-sm; }
        .label { @apply mb-1.5 block text-sm font-medium text-stone-700; }
        .input { @apply block w-full rounded-xl border-stone-300 bg-white px-3.5 py-2.5 text-sm text-stone-900 shadow-sm placeholder:text-stone-400 focus:border-brand-500 focus:ring-brand-500; }
        .checkbox { @apply h-4 w-4 rounded border-stone-300 text-brand-600 focus:ring-brand-500; }
        .radio { @apply h-4 w-4 border-stone-300 text-brand-600 focus:ring-brand-500; }
        .help { @apply mt-1.5 text-xs text-stone-500; }
        .chip { @apply inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold; }
        .link { @apply font-medium text-brand-700 underline-offset-2 hover:text-brand-800 hover:underline; }
        .eyebrow { @apply text-xs font-semibold uppercase tracking-[0.2em] text-brand-700; }
        .section-title { @apply font-display text-2xl font-semibold text-stone-900 sm:text-3xl; }
        .page-title { @apply font-display text-3xl font-semibold text-stone-900 sm:text-4xl; }
        .table-head { @apply px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-stone-500; }
        .table-cell { @apply px-4 py-3 align-middle text-sm; }
    }
</style>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/app.js" defer></script>
