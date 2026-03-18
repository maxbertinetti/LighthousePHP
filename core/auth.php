<?php

/**
 * Authentication and CSRF helpers for Lighthouse.
 *
 * This first pass uses configuration-backed credentials and session state so
 * the authentication flow can exist before migrations and user models land.
 */

/**
 * Start the session with framework defaults.
 *
 * @return void
 */
function lh_auth_boot(): void
{
    if (lh_is_testing()) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = [];
        }

        if (!isset($_SESSION['_lh']) || !is_array($_SESSION['_lh'])) {
            $_SESSION['_lh'] = [];
        }

        return;
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('LIGHTHOUSESESSID');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => lh_request_is_secure(),
        'path' => '/',
    ]);

    session_start();

    if (!isset($_SESSION['_lh'])) {
        $_SESSION['_lh'] = [];
    }
}

/**
 * Return whether the request was made over HTTPS.
 *
 * @return bool
 */
function lh_request_is_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? null) === '443');
}

/**
 * Read the current request method.
 *
 * @return string
 */
function lh_request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

/**
 * Return a POST value as a trimmed string.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function lh_post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;

    if (!is_string($value)) {
        return $default;
    }

    return trim($value);
}

/**
 * Return a GET value as a trimmed string.
 *
 * @param string $key
 * @param string $default
 * @return string
 */
function lh_query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;

    if (!is_string($value)) {
        return $default;
    }

    return trim($value);
}

/**
 * Normalize a redirect target to an internal path.
 *
 * @param string $target
 * @param string $fallback
 * @return string
 */
function lh_safe_redirect_target(string $target, string $fallback = '/'): string
{
    if ($target === '') {
        return $fallback;
    }

    if ($target[0] !== '/' || strpos($target, '//') === 0) {
        return $fallback;
    }

    return $target;
}

/**
 * Build a login URL for protected pages.
 *
 * @param string $redirect
 * @return string
 */
function lh_login_url(string $redirect = '/dashboard/home'): string
{
    $redirect = lh_safe_redirect_target($redirect, '/dashboard/home');

    return '/auth/login?redirect=' . rawurlencode($redirect);
}

/**
 * Return the session auth payload.
 *
 * @return array|null
 */
function lh_auth_user(): ?array
{
    $user = $_SESSION['_lh']['user'] ?? null;

    return is_array($user) ? $user : null;
}

/**
 * Return whether a session-authenticated user exists.
 *
 * @return bool
 */
function lh_is_authenticated(): bool
{
    return lh_auth_user() !== null;
}

/**
 * Attempt login against config-backed credentials.
 *
 * @param string $username
 * @param string $password
 * @return bool
 */
function lh_auth_attempt(string $username, string $password): bool
{
    $expectedUsername = (string) lh_config('auth.demo_username', '');
    $passwordHash = (string) lh_config('auth.demo_password_hash', '');

    if ($username !== $expectedUsername || $passwordHash === '') {
        return false;
    }

    if (!password_verify($password, $passwordHash)) {
        return false;
    }

    if (!lh_is_testing()) {
        session_regenerate_id(true);
    }

    $_SESSION['_lh']['user'] = [
        'username' => $expectedUsername,
        'login_at' => gmdate(DATE_ATOM),
    ];

    return true;
}

/**
 * Destroy the authenticated session state.
 *
 * @return void
 */
function lh_logout(): void
{
    unset($_SESSION['_lh']['user']);

    if (!lh_is_testing()) {
        session_regenerate_id(true);
    }
}

/**
 * Require an authenticated session.
 *
 * @param string $redirect
 * @return void
 */
function lh_require_auth(string $redirect = '/dashboard/home'): void
{
    if (lh_is_authenticated()) {
        return;
    }

    lh_flash_set('error', 'Please sign in to continue.');
    lh_redirect(lh_login_url($redirect), 302);
}

/**
 * Generate and persist a CSRF token.
 *
 * @return string
 */
function lh_csrf_token(): string
{
    if (empty($_SESSION['_lh']['csrf'])) {
        $_SESSION['_lh']['csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_lh']['csrf'];
}

/**
 * Render a hidden CSRF input.
 *
 * @return string
 */
function lh_csrf_input(): string
{
    return '<input type="hidden" name="_token" value="' . lh_e(lh_csrf_token()) . '">';
}

/**
 * Verify the submitted CSRF token.
 *
 * @param string|null $token
 * @return bool
 */
function lh_verify_csrf(?string $token): bool
{
    $sessionToken = $_SESSION['_lh']['csrf'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Require a valid CSRF token on state-changing requests.
 *
 * @return void
 */
function lh_require_csrf(): void
{
    if (lh_verify_csrf($_POST['_token'] ?? null)) {
        return;
    }

    lh_set_status(419);
    echo 'CSRF token mismatch.';
    lh_abort_request();
}

/**
 * Store a one-time flash message.
 *
 * @param string $type
 * @param string $message
 * @return void
 */
function lh_flash_set(string $type, string $message): void
{
    $_SESSION['_lh']['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

/**
 * Pull and clear the current flash message.
 *
 * @return array|null
 */
function lh_flash_get(): ?array
{
    $flash = $_SESSION['_lh']['flash'] ?? null;
    unset($_SESSION['_lh']['flash']);

    return is_array($flash) ? $flash : null;
}

/**
 * Read a bearer token from the Authorization header.
 *
 * @return string
 */
function lh_bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
        return '';
    }

    return trim(substr($header, 7));
}

/**
 * Validate the configured API token.
 *
 * @param string $token
 * @return bool
 */
function lh_token_is_valid(string $token): bool
{
    $expectedToken = (string) lh_config('auth.api_token', '');

    if ($expectedToken === '' || $token === '') {
        return false;
    }

    return hash_equals($expectedToken, $token);
}

/**
 * Return whether the request carries a valid configured API token.
 *
 * @return bool
 */
function lh_is_token_authenticated(): bool
{
    return lh_token_is_valid(lh_bearer_token());
}
