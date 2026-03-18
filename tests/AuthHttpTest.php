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

    lh_test('login page renders development defaults', function (): void {
        $response = lh_test_request('GET', '/auth/login');

        lh_assert_status(200, $response);
        lh_assert_contains('Development Defaults', $response['body']);
        lh_assert_contains('admin', $response['body']);
        lh_assert_true(!empty($response['session']['_lh']['csrf'] ?? ''), 'Expected login page to seed a CSRF token.');
    }),

    lh_test('login succeeds with valid credentials', function (): void {
        $initial = lh_test_request('GET', '/auth/login');
        $token = (string) ($initial['session']['_lh']['csrf'] ?? '');

        $response = lh_test_request('POST', '/auth/login', [
            'post' => [
                '_token' => $token,
                'username' => 'admin',
                'password' => 'lighthouse-demo-password',
                'redirect' => '/dashboard/home',
            ],
            'session' => $initial['session'],
        ]);

        lh_assert_status(302, $response);
        lh_assert_header('Location', '/dashboard/home', $response);
        lh_assert_same('admin', $response['session']['_lh']['user']['username'] ?? '');
    }),

    lh_test('logout requires csrf token', function (): void {
        $response = lh_test_request('POST', '/auth/logout', [
            'session' => [
                '_lh' => [
                    'user' => [
                        'username' => 'admin',
                        'login_at' => '2026-01-01T00:00:00+00:00',
                    ],
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
                    'user' => [
                        'username' => 'admin',
                        'login_at' => '2026-01-01T00:00:00+00:00',
                    ],
                ],
            ],
        ]);

        lh_assert_status(200, $response);
        lh_assert_contains('Dashboard Home', $response['body']);
        lh_assert_contains('Welcome back, <strong>admin</strong>.', $response['body']);
    }),
];
