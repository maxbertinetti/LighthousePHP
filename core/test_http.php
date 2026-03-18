<?php

/**
 * HTTP testing utilities for Lighthouse.
 */

require_once __DIR__ . '/app.php';

/**
 * Create a request and capture its response.
 *
 * @param string $method
 * @param string $uri
 * @param array $options
 * @return array{status:int, headers:array<string, array{value:string, replace:bool}>, body:string, session:array}
 */
function lh_test_request(string $method, string $uri, array $options = []): array
{
    $previousServer = $_SERVER ?? [];
    $previousGet = $_GET ?? [];
    $previousPost = $_POST ?? [];
    $previousSession = $_SESSION ?? [];

    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $queryString = $parts['query'] ?? '';

    parse_str($queryString, $query);

    $_SERVER = array_merge([
        'REQUEST_METHOD' => strtoupper($method),
        'REQUEST_URI' => $path . ($queryString !== '' ? '?' . $queryString : ''),
        'HTTP_HOST' => 'localhost',
        'SERVER_PORT' => '8000',
        'HTTPS' => 'off',
        'HTTP_ACCEPT' => 'text/html',
    ], $options['server'] ?? []);

    $_GET = array_merge($query, $options['get'] ?? []);
    $_POST = $options['post'] ?? [];
    $_SESSION = $options['session'] ?? [];

    if (!isset($_SESSION['_lh']) || !is_array($_SESSION['_lh'])) {
        $_SESSION['_lh'] = [];
    }

    try {
        $body = lh_run_app();
        $response = [
            'status' => lh_response_status(),
            'headers' => lh_response_headers(),
            'body' => $body,
            'session' => $_SESSION,
        ];
    } finally {
        $_SERVER = $previousServer;
        $_GET = $previousGet;
        $_POST = $previousPost;
        $_SESSION = $previousSession;
    }

    return $response;
}

/**
 * Assert the response status code.
 *
 * @param int $expected
 * @param array $response
 * @return void
 */
function lh_assert_status(int $expected, array $response): void
{
    lh_assert_same($expected, (int) ($response['status'] ?? 0), 'Unexpected response status.');
}

/**
 * Assert a response header value.
 *
 * @param string $name
 * @param string $expected
 * @param array $response
 * @return void
 */
function lh_assert_header(string $name, string $expected, array $response): void
{
    $headers = $response['headers'] ?? [];
    lh_assert_array_has_key($name, $headers, "Missing response header '{$name}'.");
    lh_assert_same($expected, (string) $headers[$name]['value'], "Unexpected value for header '{$name}'.");
}
