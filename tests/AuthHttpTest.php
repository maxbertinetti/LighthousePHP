<?php

return [
    lh_test('home page responds with 200', function (): void {
        $response = lh_test_request('GET', '/');

        lh_assert_status(200, $response);
        lh_assert_contains('Welcome to Lighthouse', $response['body']);
        lh_assert_header('Content-Type', 'text/html; charset=UTF-8', $response);
    }),

    lh_test('missing route responds with 404', function (): void {
        $response = lh_test_request('GET', '/missing-page');

        lh_assert_status(404, $response);
        lh_assert_contains('404 Not Found', $response['body']);
    }),

    lh_test('dashboard requires authentication', function (): void {
        $response = lh_test_request('GET', '/dashboard/home');

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/auth/login?redirect=%2Fdashboard%2Fhome', $response);
        lh_assert_same('Please sign in to continue.', $response['session']['_lh']['flash']['message'] ?? '');
    }),

    lh_test('login page renders auth links', function (): void {
        $response = lh_test_request('GET', '/auth/login');

        lh_assert_status(200, $response);
        lh_assert_contains('Forgot your password?', $response['body']);
        lh_assert_contains('Create an account', $response['body']);
        lh_assert_true(!empty($response['session']['_lh']['csrf'] ?? ''), 'Expected login page to seed a CSRF token.');
    }),

    lh_test('login succeeds with valid credentials', function (): void {
        $initial = lh_test_request('GET', '/auth/login');
        $token = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/auth/login', [
            'post' => [
                '_token' => $token,
                'identifier' => 'admin@example.test',
                'password' => 'password123',
                'redirect' => '/dashboard/home',
            ],
            'session' => $initial['session'],
        ]);

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/dashboard/home', $response);
        lh_assert_same(1, (int) ($response['session']['_lh']['user_id'] ?? 0));
    }),

    lh_test('logout requires csrf token', function (): void {
        $response = lh_test_request('POST', '/auth/logout', [
            'session' => [
                '_lh' => [
                    'user_id' => 1,
                ],
            ],
        ]);

        lh_assert_status(419, $response);
        lh_assert_contains('CSRF token mismatch.', $response['body']);
    }),

    lh_test('dashboard renders for authenticated user', function (): void {
        $response = lh_test_request('GET', '/dashboard/home', [
            'session' => [
                '_lh' => [
                    'user_id' => 1,
                    'login_at' => '2026-01-01T00:00:00+00:00',
                ],
            ],
        ]);

        lh_assert_status(200, $response);
        lh_assert_contains('Dashboard Home', $response['body']);
        lh_assert_contains('Welcome back, <strong>Admin User</strong>.', $response['body']);
    }),

    lh_test('registration creates a user account', function (): void {
        $initial = lh_test_request('GET', '/auth/register');
        $token = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/auth/register', [
            'session' => $initial['session'],
            'post' => [
                '_token' => $token,
                'name' => 'Jane Writer',
                'username' => 'jwriter',
                'email' => 'jane@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ],
        ]);

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/dashboard/home', $response);
        lh_assert_true((int) ($response['session']['_lh']['user_id'] ?? 0) > 1, 'Expected a created user session.');
    }),

    lh_test('forgot password writes a reset email to outbox', function (): void {
        lh_mail_clear_outbox();
        $initial = lh_test_request('GET', '/auth/forgot-password');
        $token = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/auth/forgot-password', [
            'session' => $initial['session'],
            'post' => [
                '_token' => $token,
                'email' => 'admin@example.test',
            ],
        ]);

        $messages = lh_mail_outbox_messages();

        lh_assert_status(200, $response);
        lh_assert_same(1, count($messages));
        lh_assert_contains('/auth/reset-password?token=', (string) ($messages[0]['payload']['meta']['reset_url'] ?? ''));
    }),

    lh_test('password reset updates the stored password', function (): void {
        lh_mail_clear_outbox();
        $forgot = lh_test_request('POST', '/auth/forgot-password', [
            'session' => ['_lh' => ['csrf' => 'known-token']],
            'post' => [
                '_token' => 'known-token',
                'email' => 'admin@example.test',
            ],
        ]);

        $messages = lh_mail_outbox_messages();
        $url = (string) ($messages[0]['payload']['meta']['reset_url'] ?? '');
        $parts = parse_url($url);
        parse_str((string) ($parts['query'] ?? ''), $query);
        $resetToken = (string) ($query['token'] ?? '');

        $initial = lh_test_request('GET', '/auth/reset-password?token=' . rawurlencode($resetToken));
        $csrf = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/auth/reset-password', [
            'session' => $initial['session'],
            'post' => [
                '_token' => $csrf,
                'token' => $resetToken,
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ],
        ]);

        $login = lh_test_request('POST', '/auth/login', [
            'session' => ['_lh' => ['csrf' => 'second-token']],
            'post' => [
                '_token' => 'second-token',
                'identifier' => 'admin',
                'password' => 'newpassword123',
                'redirect' => '/dashboard/home',
            ],
        ]);

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/auth/login', $response);
        lh_assert_status(302, $login);
        lh_assert_header('Location', '/dashboard/home', $login);
    }),

    lh_test('account page updates profile data', function (): void {
        $initial = lh_test_request('GET', '/dashboard/account', [
            'session' => [
                '_lh' => [
                    'user_id' => 1,
                ],
            ],
        ]);
        $csrf = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/dashboard/account?tab=profile', [
            'session' => $initial['session'],
            'post' => [
                '_token' => $csrf,
                'action' => 'profile',
                'name' => 'Admin Updated',
                'username' => 'admin-updated',
                'email' => 'admin.updated@example.test',
            ],
        ]);

        $dashboard = lh_test_request('GET', '/dashboard/home', [
            'session' => $response['session'],
        ]);

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/dashboard/account?tab=profile', $response);
        lh_assert_contains('admin.updated@example.test', $dashboard['body']);
    }),
];
