<?php
/**
 * Sistem Atlası — Local Development Server Router
 * For use with: php -S 127.0.0.1:8000 router.php
 */

$uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = (string) parse_url($uri, PHP_URL_PATH);
$raw_path = rawurldecode($path);
$file_path = __DIR__ . $raw_path;

// 1. Security: Disallow direct access to SQLite database directory
if (str_starts_with($raw_path, '/wp-content/database/') || str_ends_with($raw_path, '.sqlite')) {
    header('HTTP/1.1 403 Forbidden');
    echo 'Forbidden';
    exit;
}

// 2. Canonicalize directories without trailing slash (e.g. /wp-admin -> /wp-admin/)
if (is_dir($file_path) && !str_ends_with($path, '/')) {
    $target = $path . '/';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $target .= '?' . $_SERVER['QUERY_STRING'];
    }
    header('Location: ' . $target, true, 301);
    exit;
}

// 3. Serve existing non-PHP static files directly via PHP built-in web server
if (is_file($file_path) && !str_ends_with($file_path, '.php')) {
    return false;
}

// 4. Direct PHP file execution (e.g. /wp-login.php, /wp-admin/admin-ajax.php)
if (is_file($file_path) && str_ends_with($file_path, '.php')) {
    $_SERVER['SCRIPT_FILENAME'] = $file_path;
    $_SERVER['SCRIPT_NAME']     = $raw_path;
    $_SERVER['PHP_SELF']        = $raw_path;
    require $file_path;
    exit;
}

// 5. Directory index file execution (e.g. /wp-admin/)
if (is_dir($file_path) && is_file($file_path . '/index.php')) {
    $_SERVER['SCRIPT_FILENAME'] = $file_path . '/index.php';
    $_SERVER['SCRIPT_NAME']     = rtrim($raw_path, '/') . '/index.php';
    $_SERVER['PHP_SELF']        = rtrim($raw_path, '/') . '/index.php';
    require $file_path . '/index.php';
    exit;
}

// 6. Pretty permalinks & virtual WordPress routes -> root index.php
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
require __DIR__ . '/index.php';
