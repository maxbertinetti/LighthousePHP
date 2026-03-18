<?php

$redirect = lh_safe_redirect_target(
    lh_request_method() === 'POST' ? lh_post('redirect', '/dashboard/home') : lh_query('redirect', '/dashboard/home'),
    '/dashboard/home'
);

if (lh_is_authenticated()) {
    lh_redirect($redirect);
}

$error = '';
$username = '';

if (lh_request_method() === 'POST') {
    lh_require_csrf();

    $username = lh_post('username');
    $password = lh_post('password');

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
        lh_set_status(422);
    } elseif (lh_auth_attempt($username, $password)) {
        lh_flash_set('success', 'You are now signed in.');
        lh_redirect($redirect, 302);
    } else {
        $error = 'Invalid username or password.';
        lh_set_status(401);
    }
}

lh_set_data([
    'title' => 'Log In',
    'description' => 'Sign in to the Lighthouse dashboard.',
]);
?>

<section class="auth-grid">
    <article class="auth-card auth-card-form">
        <h1>Log In</h1>
        <p>Use the demo account configured in `config/config.development.ini` to access the dashboard.</p>
        <?php if ($error !== ''): ?>
            <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
        <?php endif; ?>
        <form action="/auth/login" method="post">
            <?php echo lh_csrf_input(); ?>
            <input type="hidden" name="redirect" value="<?php echo lh_e($redirect); ?>">

            <p>
                <label for="username">Username</label>
                <input
                    id="username"
                    name="username"
                    type="text"
                    value="<?php echo lh_e($username); ?>"
                    autocomplete="username"
                    required
                >
            </p>

            <p>
                <label for="password">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
            </p>

            <p>
                <button type="submit">Log In</button>
            </p>
        </form>
    </article>

    <article class="auth-card auth-card-aside">
        <h2>Development Defaults</h2>
        <p>Username: <code>admin</code></p>
        <p>Password: <code>lighthouse-demo-password</code></p>
        <p>Bearer token: <code>lighthouse-demo-token</code></p>
    </article>
</section>
