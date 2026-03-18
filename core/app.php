<?php

/**
 * Application bootstrap and request dispatcher for Lighthouse.
 */

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mail.php';

/**
 * Bootstrap framework services for the current request.
 *
 * @return void
 */
function lh_bootstrap(): void
{
    lh_load_config();
    lh_apply_environment_defaults();
    lh_auth_boot();
    lh_db_connect_from_config();
}

/**
 * Resolve the requested page path.
 *
 * @param string $uri
 * @return array{path:string, not_found:string}
 */
function lh_resolve_page(string $uri): array
{
    $baseDir = realpath(__DIR__ . '/../pages');

    if ($baseDir === false) {
        lh_config_fail('Missing pages directory.');
    }

    $target = $uri === '/' ? '/index.php' : $uri . '.php';
    $resolved = realpath($baseDir . $target);
    $notFoundPage = realpath($baseDir . '/404.php') ?: ($baseDir . '/404.php');

    if ($resolved === false || strpos($resolved, $baseDir) !== 0 || !file_exists($resolved)) {
        lh_set_status(404);
        $resolved = $notFoundPage;
    }

    return [
        'path' => $resolved,
        'not_found' => $notFoundPage,
    ];
}

/**
 * Run the current HTTP request and return the rendered body.
 *
 * @return string
 */
function lh_run_app(): string
{
    lh_bootstrap();
    lh_response_reset();
    lh_reset_layout();

    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = strtok($uri, '?');
    $uri = rawurldecode($uri);
    $uri = preg_replace('#/+#', '/', $uri);
    $uri = rtrim($uri, '/');

    if ($uri === '') {
        $uri = '/';
    }

    $resolved = lh_resolve_page($uri);
    $pagePath = $resolved['path'];
    $notFoundPage = $resolved['not_found'];
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    try {
        if ($method === 'GET' || $method === 'HEAD') {
            lh_send_security_headers();
            lh_content_type();

            $etag = 'W/"' . md5($pagePath . filemtime($pagePath)) . '"';
            lh_send_etag($etag);

            lh_send_cache_control(60, true);
        }

        ob_start();
        require $pagePath;
        $content = ob_get_clean();

        if ($pagePath !== $notFoundPage) {
            $content = lh_layout($content);
        }
    } catch (LighthouseHttpAbort $abort) {
        $content = ob_get_clean();
    }

    if ($method === 'HEAD') {
        return '';
    }

    return $content;
}
