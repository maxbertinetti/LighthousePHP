<?php

/**
 * HTTP Response Wrapper for Lighthouse
 *
 * All functions are procedural and prefixed with lh_.
 * Provides headers, status, security, and utilities for HTTP responses.
 */

$lh_response_state = [
    'status' => 200,
    'headers' => [],
];

/**
 * Exception used to stop request processing without terminating the test process.
 */
class LighthouseHttpAbort extends RuntimeException
{
}

/**
 * Return whether the runtime is currently executing framework tests.
 *
 * @return bool
 */
function lh_is_testing(): bool
{
    return defined('LIGHTHOUSE_TESTING') && LIGHTHOUSE_TESTING === true;
}

/**
 * Reset in-memory response tracking for the next request.
 *
 * @return void
 */
function lh_response_reset(): void
{
    global $lh_response_state;

    $lh_response_state = [
        'status' => 200,
        'headers' => [],
    ];
}

/**
 * Return the tracked status code.
 *
 * @return int
 */
function lh_response_status(): int
{
    global $lh_response_state;

    return (int) ($lh_response_state['status'] ?? 200);
}

/**
 * Return all tracked headers.
 *
 * @return array<string, array{value:string, replace:bool}>
 */
function lh_response_headers(): array
{
    global $lh_response_state;

    return $lh_response_state['headers'] ?? [];
}

/**
 * Abort the current request.
 *
 * @return never
 */
function lh_abort_request()
{
    if (lh_is_testing()) {
        throw new LighthouseHttpAbort('Request aborted.');
    }

    exit;
}

/**
 * Send an HTTP header.
 *
 * @param string $name   Header name
 * @param string $value  Header value
 * @param bool   $replace Replace existing header (default true)
 * @return void
 */
function lh_header($name, $value, $replace = true)
{
    global $lh_response_state;

    $lh_response_state['headers'][$name] = [
        'value' => (string) $value,
        'replace' => (bool) $replace,
    ];

    if (!lh_is_testing()) {
        header("$name: $value", $replace);
    }
}

/**
 * Set the HTTP response status code.
 *
 * @param int $code HTTP status code (e.g. 404)
 * @return void
 */
function lh_set_status($code)
{
    global $lh_response_state;

    $lh_response_state['status'] = (int) $code;
    http_response_code($code);
}

/**
 * Set the Content-Type of the response.
 *
 * @param string $type MIME type (default 'text/html; charset=UTF-8')
 * @return void
 */
function lh_content_type($type = 'text/html; charset=UTF-8')
{
    lh_header('Content-Type', $type);
}

/**
 * Send an ETag and handle 304 response if matched.
 *
 * @param string $etag ETag value
 * @return void
 */
function lh_send_etag($etag)
{
    if (lh_is_env('development')) {
        return;
    }

    lh_header('ETag', $etag);

    if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
        $clientEtags = explode(',', $_SERVER['HTTP_IF_NONE_MATCH']);

        foreach ($clientEtags as $clientEtag) {
            if (trim($clientEtag) === $etag) {
                lh_set_status(304);
                lh_abort_request();
            }
        }
    }
}

/**
 * Send Cache-Control header.
 *
 * @param int  $ttl    Time to live in seconds (default 60)
 * @param bool $public If true, public cache (default true)
 * @return void
 */
function lh_send_cache_control($ttl = 60, $public = true)
{
    if (lh_is_env('development')) {
        lh_no_cache();
        return;
    }

    $type = $public ? 'public' : 'private';
    lh_header('Cache-Control', "$type, max-age=" . intval($ttl));
}

/**
 * Disable cache for the response.
 *
 * @return void
 */
function lh_no_cache()
{
    lh_header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    lh_header('Pragma', 'no-cache');
}

/**
 * Send HTTP security headers (CSP, HSTS, etc).
 *
 * @return void
 */
function lh_send_security_headers()
{
    lh_header(
        'Content-Security-Policy',
        "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline'; " .
            "style-src 'self' 'unsafe-inline'; " .
            "img-src 'self' data:; " .
            "font-src 'self';"
    );
    lh_header('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
    lh_header('X-Frame-Options', 'SAMEORIGIN');
    lh_header('X-Content-Type-Options', 'nosniff');
    lh_header('Referrer-Policy', 'strict-origin-when-cross-origin');
    lh_header('X-XSS-Protection', '1; mode=block');
}

/**
 * Perform an HTTP redirect.
 *
 * @param string $url    Target URL
 * @param int    $status HTTP status code (default 302)
 * @return void
 */
function lh_redirect($url, $status = 302)
{
    lh_set_status($status);
    lh_header('Location', $url);
    lh_abort_request();
}

/**
 * Escape for safe HTML output.
 *
 * @param string $value Value to escape
 * @return string
 */
function lh_e($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * Detect if the request accepts JSON.
 *
 * @return bool
 */
function lh_is_json_request()
{
    return isset($_SERVER['HTTP_ACCEPT']) &&
        strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
}
