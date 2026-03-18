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
        <p>Welcome back, <strong><?php echo lh_e((string) ($user['name'] ?? $user['username'] ?? '')); ?></strong>.</p>
        <p>This page is protected by database-backed session auth.</p>
    </article>

    <article class="dashboard-card">
        <h2>Session Status</h2>
        <p>Signed in as: <code><?php echo lh_e((string) ($user['email'] ?? 'unknown')); ?></code></p>
        <p>Session started at: <code><?php echo lh_e((string) ($_SESSION['_lh']['login_at'] ?? 'unknown')); ?></code></p>
        <p>Session auth: <strong><?php echo lh_is_authenticated() ? 'active' : 'inactive'; ?></strong></p>
        <p>Token auth helper: <strong><?php echo lh_is_token_authenticated() ? 'valid token present' : 'no valid bearer token'; ?></strong></p>
    </article>

    <article class="dashboard-card">
        <h2>What This Enables</h2>
        <ul>
            <li>Protected page routing with `lh_require_auth()`.</li>
            <li>Registration, password reset, and profile management.</li>
            <li>CSRF-protected state changes and bearer token checks for API routes.</li>
        </ul>
    </article>
</section>
