<?php
// Lighthouse PHP Framework Entry Point

require_once __DIR__ . '/../core/http.php';
require_once __DIR__ . '/../core/layout.php';
require_once __DIR__ . '/../core/db.php';

// --- URL ---
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = strtok($uri, '?');
$uri = rawurldecode($uri);
$uri = preg_replace('#/+#', '/', $uri);
$uri = rtrim($uri, '/');
if ($uri === '') $uri = '/';

// --- Resolve ---
$baseDir = realpath(__DIR__ . '/../pages');
$target = $uri === '/' ? '/index.php' : $uri . '.php';
$resolved = realpath($baseDir . $target);
$notFoundPage = realpath($baseDir . '/404.php') ?: ($baseDir . '/404.php');

if ($resolved === false || strpos($resolved, $baseDir) !== 0 || !file_exists($resolved)) {
    lh_set_status(404);
    $resolved = $notFoundPage;
}

// --- HTTP ---
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET' || $method === 'HEAD') {
    lh_send_security_headers();
    lh_content_type();

    $etag = 'W/"' . md5($resolved . filemtime($resolved)) . '"';
    lh_send_etag($etag);

    lh_send_cache_control(60, true);
}

// --- Execute ---
ob_start();
require $resolved;
$content = ob_get_clean();

// Apply layout if page is not 404 without layout
if ($resolved !== $notFoundPage) {
    $content = lh_layout($content);
}

echo $content;
