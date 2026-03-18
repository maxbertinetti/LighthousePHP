<?php

/**
 * Authentication, account, and CSRF helpers for Lighthouse.
 */

/**
 * Start the session with framework defaults.
 *
 * @return void
 */
function lh_auth_boot(): void
{
    if (lh_is_testing()) {
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

    if (!isset($_SESSION['_lh']) || !is_array($_SESSION['_lh'])) {
        $_SESSION['_lh'] = [];
    }
}

function lh_request_is_secure(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return (($_SERVER['SERVER_PORT'] ?? null) === '443');
}

function lh_request_method(): string
{
    return strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
}

function lh_post(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

function lh_query(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

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

function lh_login_url(string $redirect = '/dashboard/home'): string
{
    return '/auth/login?redirect=' . rawurlencode(lh_safe_redirect_target($redirect, '/dashboard/home'));
}

function lh_require_guest(string $redirect = '/dashboard/home'): void
{
    if (!lh_is_authenticated()) {
        return;
    }

    lh_redirect(lh_safe_redirect_target($redirect, '/dashboard/home'));
}

function lh_session_user_id(): ?int
{
    $value = $_SESSION['_lh']['user_id'] ?? null;

    if (is_int($value)) {
        return $value;
    }

    if (is_string($value) && ctype_digit($value)) {
        return (int) $value;
    }

    return null;
}

function lh_auth_set_user_session(array $user): void
{
    if (!lh_is_testing()) {
        session_regenerate_id(true);
    }

    $_SESSION['_lh']['user_id'] = (int) $user['id'];
    $_SESSION['_lh']['login_at'] = gmdate(DATE_ATOM);
    unset($_SESSION['_lh']['user']);
}

function lh_auth_user(): ?array
{
    $cached = $_SESSION['_lh']['user'] ?? null;

    if (is_array($cached) && isset($cached['id'])) {
        return $cached;
    }

    $userId = lh_session_user_id();

    if ($userId === null) {
        return null;
    }

    $user = lh_auth_find_user_by_id($userId);

    if ($user === null) {
        unset($_SESSION['_lh']['user_id'], $_SESSION['_lh']['user']);
        return null;
    }

    $_SESSION['_lh']['user'] = $user;

    return $user;
}

function lh_is_authenticated(): bool
{
    return lh_auth_user() !== null;
}

function lh_auth_identifier_is_email(string $identifier): bool
{
    return filter_var($identifier, FILTER_VALIDATE_EMAIL) !== false;
}

function lh_auth_normalize_email(string $email): string
{
    return strtolower(trim($email));
}

function lh_auth_find_user_by_id(int $id): ?array
{
    return lh_db_fetch('SELECT id, email, username, name, password_hash, created_at, updated_at FROM users WHERE id = :id LIMIT 1', [
        'id' => $id,
    ]);
}

function lh_auth_find_user_by_email(string $email): ?array
{
    return lh_db_fetch('SELECT id, email, username, name, password_hash, created_at, updated_at FROM users WHERE email = :email LIMIT 1', [
        'email' => lh_auth_normalize_email($email),
    ]);
}

function lh_auth_find_user_by_username(string $username): ?array
{
    return lh_db_fetch('SELECT id, email, username, name, password_hash, created_at, updated_at FROM users WHERE username = :username LIMIT 1', [
        'username' => trim($username),
    ]);
}

function lh_auth_find_user_by_identifier(string $identifier): ?array
{
    if (lh_auth_identifier_is_email($identifier)) {
        return lh_auth_find_user_by_email($identifier);
    }

    return lh_auth_find_user_by_username($identifier);
}

function lh_auth_validate_registration(array $input): array
{
    $errors = [];
    $name = trim((string) ($input['name'] ?? ''));
    $username = trim((string) ($input['username'] ?? ''));
    $email = lh_auth_normalize_email((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');
    $confirm = (string) ($input['password_confirmation'] ?? '');

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        $errors[] = 'Username must be 3-32 characters and use letters, numbers, dots, dashes, or underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($username !== '' && lh_auth_find_user_by_username($username) !== null) {
        $errors[] = 'That username is already taken.';
    }

    if ($email !== '' && lh_auth_find_user_by_email($email) !== null) {
        $errors[] = 'That email address is already registered.';
    }

    return $errors;
}

function lh_auth_create_user(string $name, string $username, string $email, string $password): array
{
    $timestamp = gmdate(DATE_ATOM);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    lh_db_execute(
        'INSERT INTO users (email, username, name, password_hash, created_at, updated_at) VALUES (:email, :username, :name, :password_hash, :created_at, :updated_at)',
        [
            'email' => lh_auth_normalize_email($email),
            'username' => trim($username),
            'name' => trim($name),
            'password_hash' => $passwordHash,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]
    );

    return lh_auth_find_user_by_id((int) lh_db_last_id()) ?? [];
}

function lh_auth_attempt(string $identifier, string $password): bool
{
    $user = lh_auth_find_user_by_identifier($identifier);

    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        return false;
    }

    lh_auth_set_user_session($user);

    return true;
}

function lh_logout(): void
{
    unset($_SESSION['_lh']['user_id'], $_SESSION['_lh']['user'], $_SESSION['_lh']['login_at']);

    if (!lh_is_testing()) {
        session_regenerate_id(true);
    }
}

function lh_require_auth(string $redirect = '/dashboard/home'): void
{
    if (lh_is_authenticated()) {
        return;
    }

    lh_flash_set('error', 'Please sign in to continue.');
    lh_redirect(lh_login_url($redirect), 302);
}

function lh_csrf_token(): string
{
    if (empty($_SESSION['_lh']['csrf'])) {
        $_SESSION['_lh']['csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_lh']['csrf'];
}

function lh_csrf_input(): string
{
    return '<input type="hidden" name="_token" value="' . lh_e(lh_csrf_token()) . '">';
}

function lh_verify_csrf(?string $token): bool
{
    $sessionToken = $_SESSION['_lh']['csrf'] ?? '';

    if (!is_string($sessionToken) || $sessionToken === '' || !is_string($token) || $token === '') {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

function lh_require_csrf(): void
{
    if (lh_verify_csrf($_POST['_token'] ?? null)) {
        return;
    }

    lh_set_status(419);
    echo 'CSRF token mismatch.';
    lh_abort_request();
}

function lh_flash_set(string $type, string $message): void
{
    $_SESSION['_lh']['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function lh_flash_get(): ?array
{
    $flash = $_SESSION['_lh']['flash'] ?? null;
    unset($_SESSION['_lh']['flash']);

    return is_array($flash) ? $flash : null;
}

function lh_auth_password_reset_expiry_minutes(): int
{
    return max(5, (int) lh_config('auth.password_reset_expiry_minutes', 60));
}

function lh_auth_create_reset_token(array $user): string
{
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);
    $createdAt = gmdate(DATE_ATOM);
    $expiresAt = gmdate(DATE_ATOM, time() + (lh_auth_password_reset_expiry_minutes() * 60));

    lh_db_execute('UPDATE password_reset_tokens SET used_at = :used_at WHERE user_id = :user_id AND used_at IS NULL', [
        'used_at' => $createdAt,
        'user_id' => (int) $user['id'],
    ]);

    lh_db_execute(
        'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, used_at, created_at) VALUES (:user_id, :token_hash, :expires_at, :used_at, :created_at)',
        [
            'user_id' => (int) $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => null,
            'created_at' => $createdAt,
        ]
    );

    return $plainToken;
}

function lh_auth_find_reset_token(string $token): ?array
{
    $tokenHash = hash('sha256', $token);

    return lh_db_fetch(
        'SELECT prt.id, prt.user_id, prt.token_hash, prt.expires_at, prt.used_at, prt.created_at,
                u.email, u.username, u.name
         FROM password_reset_tokens prt
         INNER JOIN users u ON u.id = prt.user_id
         WHERE prt.token_hash = :token_hash
         LIMIT 1',
        ['token_hash' => $tokenHash]
    );
}

function lh_auth_reset_token_is_valid(?array $record): bool
{
    if ($record === null) {
        return false;
    }

    if (!empty($record['used_at'])) {
        return false;
    }

    $expiresAt = strtotime((string) $record['expires_at']);

    return $expiresAt !== false && $expiresAt >= time();
}

function lh_auth_send_reset_email(array $user, string $token): string
{
    $baseUrl = rtrim((string) lh_config('app.base_url', 'http://localhost:8000'), '/');
    $url = $baseUrl . '/auth/reset-password?token=' . rawurlencode($token);
    $body = "Hello {$user['name']},\n\nA password reset was requested for your Lighthouse account.\n\nReset link:\n{$url}\n\nIf you did not request this, you can ignore this email.\n";

    return lh_mail_send((string) $user['email'], 'Reset your Lighthouse password', $body, [
        'type' => 'password_reset',
        'reset_url' => $url,
        'user_id' => (string) $user['id'],
    ]);
}

function lh_auth_request_password_reset(string $email): ?string
{
    $user = lh_auth_find_user_by_email($email);

    if ($user === null) {
        return null;
    }

    $token = lh_auth_create_reset_token($user);

    return lh_auth_send_reset_email($user, $token);
}

function lh_auth_reset_password(string $token, string $password): bool
{
    $record = lh_auth_find_reset_token($token);

    if (!lh_auth_reset_token_is_valid($record)) {
        return false;
    }

    $timestamp = gmdate(DATE_ATOM);

    lh_db_transaction(function () use ($record, $password, $timestamp): void {
        lh_db_execute(
            'UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id',
            [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => $timestamp,
                'id' => (int) $record['user_id'],
            ]
        );

        lh_db_execute(
            'UPDATE password_reset_tokens SET used_at = :used_at WHERE id = :id',
            [
                'used_at' => $timestamp,
                'id' => (int) $record['id'],
            ]
        );
    });

    return true;
}

function lh_auth_update_profile(int $userId, string $name, string $username, string $email): array
{
    $errors = [];
    $name = trim($name);
    $username = trim($username);
    $email = lh_auth_normalize_email($email);

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($username === '' || !preg_match('/^[A-Za-z0-9_.-]{3,32}$/', $username)) {
        $errors[] = 'Username must be 3-32 characters and use letters, numbers, dots, dashes, or underscores.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    $existingByUsername = lh_auth_find_user_by_username($username);
    if ($existingByUsername !== null && (int) $existingByUsername['id'] !== $userId) {
        $errors[] = 'That username is already taken.';
    }

    $existingByEmail = lh_auth_find_user_by_email($email);
    if ($existingByEmail !== null && (int) $existingByEmail['id'] !== $userId) {
        $errors[] = 'That email address is already registered.';
    }

    if ($errors !== []) {
        return $errors;
    }

    lh_db_execute(
        'UPDATE users SET name = :name, username = :username, email = :email, updated_at = :updated_at WHERE id = :id',
        [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'updated_at' => gmdate(DATE_ATOM),
            'id' => $userId,
        ]
    );

    $updated = lh_auth_find_user_by_id($userId);
    if ($updated !== null) {
        $_SESSION['_lh']['user'] = $updated;
    }

    return [];
}

function lh_auth_change_password(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
{
    $user = lh_auth_find_user_by_id($userId);
    $errors = [];

    if ($user === null) {
        return ['Unable to load the current user account.'];
    }

    if (!password_verify($currentPassword, (string) $user['password_hash'])) {
        $errors[] = 'Current password is incorrect.';
    }

    if (strlen($newPassword) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = 'New password confirmation does not match.';
    }

    if ($errors !== []) {
        return $errors;
    }

    lh_db_execute(
        'UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id',
        [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => gmdate(DATE_ATOM),
            'id' => $userId,
        ]
    );

    $_SESSION['_lh']['user'] = lh_auth_find_user_by_id($userId);

    return [];
}

function lh_bearer_token(): string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!is_string($header) || stripos($header, 'Bearer ') !== 0) {
        return '';
    }

    return trim(substr($header, 7));
}

function lh_token_is_valid(string $token): bool
{
    $expectedToken = (string) lh_config('auth.api_token', '');

    if ($expectedToken === '' || $token === '') {
        return false;
    }

    return hash_equals($expectedToken, $token);
}

function lh_is_token_authenticated(): bool
{
    return lh_token_is_valid(lh_bearer_token());
}
