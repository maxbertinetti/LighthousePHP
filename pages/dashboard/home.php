<?php

lh_require_auth('/dashboard/home');

$user = lh_auth_user();

lh_set_data([
    'title' => 'Dashboard',
    'description' => 'Authenticated Lighthouse dashboard home.',
]);
?>

<section class="dashboard-grid">
    <article class="dashboard-card dashboard-card-primary">
        <h1>Dashboard Home</h1>
        <p>Welcome back, <strong><?php echo lh_e((string) ($user['username'] ?? '')); ?></strong>.</p>
        <p>This page is protected by the new session auth guard.</p>
    </article>

    <article class="dashboard-card">
        <h2>Session Status</h2>
        <p>Signed in at: <code><?php echo lh_e((string) ($user['login_at'] ?? 'unknown')); ?></code></p>
        <p>Session auth: <strong><?php echo lh_is_authenticated() ? 'active' : 'inactive'; ?></strong></p>
        <p>Token auth helper: <strong><?php echo lh_is_token_authenticated() ? 'valid token present' : 'no valid bearer token'; ?></strong></p>
    </article>

    <article class="dashboard-card">
        <h2>What This Enables</h2>
        <ul>
            <li>Protected page routing with `lh_require_auth()`.</li>
            <li>CSRF-protected state changes with `lh_require_csrf()`.</li>
            <li>Config-backed bearer token checks for API routes.</li>
        </ul>
    </article>
</section>
