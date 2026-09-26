<?php

// Router for "php artisan serve": existing static files are served as is, the rest goes through index.php
// with the SCRIPT_NAME Apache sets, otherwise a URL like /dashboard is mistaken for the dashboard folder.
$path = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;

// logs are never served, the same as storage/logs/.htaccess does for Apache
if ($path !== '/' && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php' && ! str_starts_with($path, '/storage/logs/')) {
    return false;
}

$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
