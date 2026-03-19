<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = __DIR__ . ($path === false ? '/' : $path);

if ($path !== false && $path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/index.php';
