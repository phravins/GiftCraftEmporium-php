<?php
require __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/views/partials/components.php';

// page => [view, who may see it, layout]
// Access: any | guest | user | buyer | seller. Layout "role" picks seller or store by account type.
const PAGES = [
    'home'             => ['views/home.php', 'any', 'store'],
    'shop'             => ['views/shop.php', 'any', 'store'],
    'product'          => ['views/product.php', 'any', 'store'],
    'store'            => ['views/store.php', 'any', 'store'],
    'gallery'          => ['views/gallery.php', 'any', 'store'],
    'visualize'        => ['views/visualize.php', 'any', 'store'],
    'login'            => ['views/auth/login.php', 'guest', 'auth'],
    'register'         => ['views/auth/register.php', 'guest', 'auth'],
    'cart'             => ['views/cart.php', 'buyer', 'store'],
    'checkout'         => ['views/checkout.php', 'buyer', 'store'],
    'orders'           => ['views/orders.php', 'buyer', 'store'],
    'order'            => ['views/order.php', 'buyer', 'store'],
    'wishlist'         => ['views/wishlist.php', 'buyer', 'store'],
    'account'          => ['views/account.php', 'user', 'role'],
    'seller_dashboard' => ['views/seller/dashboard.php', 'seller', 'seller'],
    'seller_products'  => ['views/seller/products.php', 'seller', 'seller'],
    'seller_product'   => ['views/seller/product_form.php', 'seller', 'seller'],
    'seller_orders'    => ['views/seller/orders.php', 'seller', 'seller'],
    'seller_order'     => ['views/seller/order.php', 'seller', 'seller'],
    'seller_reviews'   => ['views/seller/reviews.php', 'seller', 'seller'],
    'seller_settings'  => ['views/seller/settings.php', 'seller', 'seller'],
];

// action => who may run it. Every action is a CSRF-checked POST to actions/<name>.php.
const ACTIONS = [
    'login'                 => 'guest',
    'register'              => 'guest',
    'logout'                => 'user',
    'account_update'        => 'user',
    'password_update'       => 'user',
    'cart_add'              => 'buyer',
    'cart_update'           => 'buyer',
    'cart_remove'           => 'buyer',
    'wishlist_toggle'       => 'buyer',
    'place_order'           => 'buyer',
    'order_cancel'          => 'buyer',
    'review_save'           => 'buyer',
    'address_save'          => 'buyer',
    'address_delete'        => 'buyer',
    'seller_product_save'   => 'seller',
    'seller_product_quick'  => 'seller',
    'seller_product_delete' => 'seller',
    'seller_order_update'   => 'seller',
    'seller_review_reply'   => 'seller',
    'seller_settings_save'  => 'seller',
];

function enforce_access(string $access): void {
    $user = current_user();
    if ($access === 'any') {
        return;
    }
    if ($access === 'guest') {
        if ($user) {
            redirect(user_home($user));
        }
        return;
    }
    if (!$user) {
        // Send them back here (or to the page they posted from) after signing in.
        $_SESSION['intended'] = $_SERVER['REQUEST_METHOD'] === 'GET' ? current_url() : back_url();
        flash('info', 'Please sign in to continue.');
        redirect(url('login'));
    }
    if ($access !== 'user' && $user['role'] !== $access) {
        flash('error', $access === 'buyer'
            ? 'Shopping features are for buyer accounts. Seller accounts manage a store from the Seller Hub.'
            : 'The Seller Hub is only available to seller accounts.');
        redirect(user_home($user));
    }
}

function render_page(string $view, string $layout, string $page): void {
    $user = current_user();
    $page_title = null;
    $full_bleed = false;

    ob_start();
    try {
        require __DIR__ . '/' . $view;
        $content = ob_get_clean();
    } catch (Throwable $e) {
        ob_end_clean();
        throw $e;
    }

    if ($layout === 'role') {
        $layout = ($user['role'] ?? null) === 'seller' ? 'seller' : 'store';
    }
    require __DIR__ . "/views/layouts/{$layout}.php";
}

function render_error(int $status, string $message = ''): void {
    http_response_code($status);
    $GLOBALS['error_status'] = $status;
    $GLOBALS['error_message'] = $message;
    render_page('views/errors/error.php', 'store', 'error');
}

try {
    $action = $_GET['action'] ?? null;

    if ($action !== null) {
        if (!is_string($action) || !isset(ACTIONS[$action])) {
            abort(404);
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('index.php');
        }
        if (!$_POST && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            flash('error', 'That upload was too large for the server. Try fewer or smaller images.');
            redirect_back();
        }
        if (!verify_csrf()) {
            flash('error', 'Your session expired. Please try again.');
            redirect_back();
        }
        enforce_access(ACTIONS[$action]);

        try {
            require __DIR__ . "/actions/{$action}.php";
            redirect_back();
        } catch (ValidationException $e) {
            remember_input();
            flash('error', $e->getMessage());
            redirect_back();
        } catch (HttpException $e) {
            flash('error', $e->getMessage() ?: 'That item could not be found.');
            redirect_back();
        }
    }

    $page = $_GET['page'] ?? 'home';
    if (!is_string($page) || !isset(PAGES[$page])) {
        abort(404);
    }
    [$view, $access, $layout] = PAGES[$page];
    enforce_access($access);

    // Form input from a failed submit is available to this one page render.
    $GLOBALS['_old_input'] = $_SESSION['_old'] ?? [];
    unset($_SESSION['_old']);

    render_page($view, $layout, $page);
} catch (HttpException $e) {
    render_error($e->getCode() ?: 500, $e->getMessage());
} catch (Throwable $e) {
    error_log((string) $e);
    try {
        render_error(500, APP_DEBUG ? $e->getMessage() : '');
    } catch (Throwable $inner) {
        http_response_code(500);
        echo 'Something went wrong. Please try again later.';
    }
}
