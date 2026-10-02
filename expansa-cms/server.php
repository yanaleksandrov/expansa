<?php

// Router for "php artisan serve": existing static files are served as is, the rest goes through index.php
// with the SCRIPT_NAME Apache sets, otherwise a URL like /dashboard is mistaken for the dashboard folder.
$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;

// the storage and the compiled views are never served, the same as .htaccess does for Apache
if (preg_match('~^/(storage|cache/views)/~', $path)) {
    http_response_code(403);

    return true;
}

if ($path !== '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
    return false;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
