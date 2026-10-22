<?php

/** A message that is safe to show to the user (bad input, business rule). */
class ValidationException extends RuntimeException {}

/** Stops the request with an HTTP status (404, 403...). */
class HttpException extends RuntimeException {}

function abort(int $status, string $message = ''): never {
    throw new HttpException($message, $status);
}

// ---------------------------------------------------------------- output

function e($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function str_cut(string $value, int $max): string {
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

function str_length(string $value): int {
    return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
}

function money($cents): string {
    $cents = (int) $cents;
    return ($cents < 0 ? '-' : '') . APP_CURRENCY . number_format(abs($cents) / 100, 2);
}

/** "12.5" -> 1250. Returns null for anything that is not a plain amount. */
function parse_money($input): ?int {
    $value = str_replace([',', ' ', APP_CURRENCY], '', trim((string) $input));
    if (!preg_match('/^(\d{1,7})(?:\.(\d{1,2}))?$/', $value, $m)) {
        return null;
    }
    return (int) $m[1] * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
}

function money_input($cents): string {
    return $cents === null || $cents === '' ? '' : number_format(((int) $cents) / 100, 2, '.', '');
}

function fmt_date(?string $timestamp, string $format = 'M j, Y'): string {
    if (!$timestamp) {
        return '';
    }
    // SQLite CURRENT_TIMESTAMP is UTC.
    return date($format, strtotime($timestamp . ' UTC'));
}

/** SQLite date() modifier that shifts UTC timestamps into the app timezone. */
function sqlite_tz_modifier(): string {
    return sprintf('%+d minutes', (int) ((new DateTime())->getOffset() / 60));
}

function plural(int $count, string $singular, ?string $plural = null): string {
    return $count . ' ' . ($count === 1 ? $singular : ($plural ?? $singular . 's'));
}

function image_url(?string $path): string {
    return $path ?: 'assets/img/placeholder.svg';
}

function slugify(string $text): string {
    if (function_exists('iconv')) {
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($ascii !== false) {
            $text = $ascii;
        }
    }
    $text = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($text)), '-');
    return $text !== '' ? substr($text, 0, 60) : 'item';
}

/** Slug unique within $table.slug ($table is always a hard-coded name). */
function unique_slug(string $table, string $text, ?int $ignoreId = null): string {
    $base = slugify($text);
    $slug = $base;
    $stmt = db()->prepare("SELECT 1 FROM {$table} WHERE slug = ? AND id != ?");
    for ($i = 2; ; $i++) {
        $stmt->execute([$slug, $ignoreId ?? 0]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . $i;
    }
}

// ---------------------------------------------------------------- URLs & redirects

function url(string $page = 'home', array $params = []): string {
    $params = array_filter($params, fn($v) => $v !== null && $v !== '' && $v !== false);
    if ($page === 'home' && !$params) {
        return 'index.php';
    }
    return 'index.php?' . http_build_query(['page' => $page] + $params);
}

function action_url(string $action): string {
    return 'index.php?action=' . rawurlencode($action);
}

/** The current URL with some query parameters replaced (null removes one). */
function url_with(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, fn($v) => $v !== null && $v !== '' && !is_array($v));
    return $params ? 'index.php?' . http_build_query($params) : 'index.php';
}

function current_url(): string {
    return url_with([]);
}

/** Only allow redirects back into this app (blocks open redirects). */
function safe_return_url($target): ?string {
    if (is_string($target) && preg_match('/^index\.php(\?[^\r\n\\\\]*)?$/', $target)) {
        return $target;
    }
    return null;
}

function redirect(string $to): never {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $to, true, 303);
    exit;
}

function back_url(string $fallback = 'index.php'): string {
    $target = safe_return_url($_POST['_back'] ?? null);
    if (!$target && !empty($_SERVER['HTTP_REFERER'])) {
        $ref = parse_url($_SERVER['HTTP_REFERER']);
        $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        if ($ref && ($ref['host'] ?? null) === $host) {
            $path = $ref['path'] ?? '/';
            $file = str_ends_with($path, '/') ? 'index.php' : basename($path);
            $target = safe_return_url($file . (isset($ref['query']) ? '?' . $ref['query'] : ''));
        }
    }
    return $target ?? $fallback;
}

function redirect_back(string $fallback = 'index.php'): never {
    redirect(back_url($fallback));
}

// ---------------------------------------------------------------- session state

function flash(string $type, string $message): void {
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array {
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function remember_input(): void {
    $secret = ['password', 'password_confirm', 'current_password', '_csrf'];
    $_SESSION['_old'] = array_diff_key($_POST, array_flip($secret));
}

/** Previous form input after a failed submit, else $default. */
function old(string $key, $default = '') {
    $value = $GLOBALS['_old_input'][$key] ?? null;
    return is_string($value) ? $value : $default;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['_csrf'] ?? '';
    return is_string($token) && $token !== '' && hash_equals(csrf_token(), $token);
}

// ---------------------------------------------------------------- request input

function input(string $key, int $max = 255): string {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? str_cut(trim($value), $max) : '';
}

function input_int(string $key): ?int {
    $value = filter_var($_POST[$key] ?? null, FILTER_VALIDATE_INT);
    return $value === false ? null : $value;
}

function query(string $key, int $max = 100): string {
    $value = $_GET[$key] ?? '';
    return is_string($value) ? str_cut(trim($value), $max) : '';
}

function query_int(string $key, int $default = 0): int {
    $value = filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT);
    return $value === false ? $default : $value;
}

function require_fields(array $fields): void {
    foreach ($fields as $label => $value) {
        if ($value === '' || $value === null) {
            throw new ValidationException("{$label} is required.");
        }
    }
}

function valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function paginate(int $total, int $perPage, int $current): array {
    $pages = max(1, (int) ceil($total / $perPage));
    $current = min(max(1, $current), $pages);
    return [
        'total'    => $total,
        'pages'    => $pages,
        'current'  => $current,
        'per_page' => $perPage,
        'offset'   => ($current - 1) * $perPage,
    ];
}

// ---------------------------------------------------------------- uploads

/** $_FILES[$field] as a list of single files, skipping empty inputs. */
function uploaded_files(string $field): array {
    $files = $_FILES[$field] ?? null;
    if (!$files || !isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        $list = [$files];
    } else {
        $list = [];
        foreach (array_keys($files['name']) as $i) {
            $list[] = [
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
        }
    }
    return array_values(array_filter($list, fn($f) => (int) $f['error'] !== UPLOAD_ERR_NO_FILE));
}

/** Validates an uploaded image by its content (not its name) and returns the extension. */
function validate_image_upload(array $file): string {
    $error = (int) $file['error'];
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        throw new ValidationException('"' . $file['name'] . '" is larger than the server allows.');
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
        throw new ValidationException('"' . $file['name'] . '" could not be uploaded. Please try again.');
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        throw new ValidationException('"' . $file['name'] . '" is over the ' . (MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB limit.');
    }
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!isset(ALLOWED_IMAGE_TYPES[$mime])) {
        throw new ValidationException('"' . $file['name'] . '" is not a JPG, PNG, WebP or GIF image.');
    }
    return ALLOWED_IMAGE_TYPES[$mime];
}

/** Moves a validated upload into uploads/$subdir under a random name; returns its public path. */
function store_image_upload(array $file, string $subdir): string {
    $ext = validate_image_upload($file);
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create upload directory {$dir}");
    }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], "{$dir}/{$name}")) {
        throw new RuntimeException('Could not save the uploaded file.');
    }
    return UPLOAD_URL . "/{$subdir}/{$name}";
}

function delete_upload(?string $path): void {
    if (!$path || !str_starts_with($path, UPLOAD_URL . '/')) {
        return;
    }
    $real = realpath(BASE_PATH . '/' . $path);
    $root = realpath(UPLOAD_DIR);
    if ($real && $root && str_starts_with($real, $root . DIRECTORY_SEPARATOR) && is_file($real)) {
        @unlink($real);
    }
}

// ---------------------------------------------------------------- icons (Heroicons, MIT)

function icon(string $name, string $class = 'h-5 w-5'): string {
    static $paths = [
        'bag'      => 'M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'heart'    => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z',
        'user'     => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z',
        'search'   => 'm21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'menu'     => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'x'        => 'M6 18 18 6M6 6l12 12',
        'store'    => 'M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z',
        'cube'     => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'truck'    => 'M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12',
        'check'    => 'm4.5 12.75 6 6 9-13.5',
        'check-circle' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'x-circle' => 'm9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'chart'    => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z',
        'grid'     => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
        'sliders'  => 'M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75',
        'logout'   => 'M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9',
        'plus'     => 'M12 4.5v15m7.5-7.5h-15',
        'minus'    => 'M5 12h14',
        'trash'    => 'm14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0',
        'pencil'   => 'm16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10',
        'eye'      => 'M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z|M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'arrow-left'  => 'M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18',
        'arrow-right' => 'M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3',
        'chevron-down'  => 'm19.5 8.25-7.5 7.5-7.5-7.5',
        'chevron-right' => 'm8.25 4.5 7.5 7.5-7.5 7.5',
        'tag'      => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z|M6 6h.008v.008H6V6Z',
        'map-pin'  => 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z|M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z',
        'gift'     => 'M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'box'      => 'm20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z',
        'clipboard'=> 'M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z',
        'chat'     => 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z',
        'warning'  => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z',
        'info'     => 'm11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
        'sparkles' => 'M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z',
        'shield'   => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
        'cash'     => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z',
        'photo'    => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'clock'    => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'inbox'    => 'M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z',
        'funnel'   => 'M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z',
    ];
    $d = '';
    foreach (explode('|', $paths[$name] ?? $paths['info']) as $path) {
        $d .= '<path stroke-linecap="round" stroke-linejoin="round" d="' . $path . '"/>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="' . e($class) . '" aria-hidden="true">' . $d . '</svg>';
}

/** $filled null leaves the colour to the parent (used by the rating input). */
function star_icon(string $class, ?bool $filled = null): string {
    $fill = $filled === null ? '' : ($filled ? 'text-amber-400' : 'text-stone-200');
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="' . e($class . ' ' . $fill) . '" aria-hidden="true"><path fill-rule="evenodd" clip-rule="evenodd" d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354 7.373 21.18c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"/></svg>';
}

function stars($rating, string $class = 'h-4 w-4'): string {
    $rating = (float) $rating;
    $full = (int) round($rating);
    $html = '<span class="inline-flex items-center" role="img" aria-label="Rated ' . e(number_format($rating, 1)) . ' out of 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= star_icon($class, $i <= $full);
    }
    return $html . '</span>';
}

// ---------------------------------------------------------------- status labels

function status_meta(string $status): array {
    return [
        'pending'    => ['Awaiting confirmation', 'bg-amber-50 text-amber-800 ring-amber-200', 'clock'],
        'processing' => ['Preparing', 'bg-sky-50 text-sky-800 ring-sky-200', 'box'],
        'shipped'    => ['Shipped', 'bg-indigo-50 text-indigo-800 ring-indigo-200', 'truck'],
        'partially_shipped' => ['Partially shipped', 'bg-indigo-50 text-indigo-800 ring-indigo-200', 'truck'],
        'delivered'  => ['Delivered', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'check-circle'],
        'cancelled'  => ['Cancelled', 'bg-stone-100 text-stone-600 ring-stone-200', 'x-circle'],
        'active'     => ['Active', 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'check-circle'],
        'draft'      => ['Draft', 'bg-stone-100 text-stone-700 ring-stone-200', 'pencil'],
        'archived'   => ['Archived', 'bg-stone-100 text-stone-500 ring-stone-200', 'box'],
    ][$status] ?? [ucfirst($status), 'bg-stone-100 text-stone-700 ring-stone-200', 'info'];
}

function status_badge(string $status): string {
    [$label, $classes, $iconName] = status_meta($status);
    return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset ' . $classes . '">'
        . icon($iconName, 'h-3.5 w-3.5') . e($label) . '</span>';
}
