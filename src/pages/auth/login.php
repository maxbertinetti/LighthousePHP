<?php

$redirect = lh_safe_redirect_target(
    lh_request_method() === 'POST' ? lh_post('redirect', '/dashboard/home') : lh_query('redirect', '/dashboard/home'),
    '/dashboard/home'
);

if (lh_is_authenticated()) {
    lh_redirect($redirect);
}

$error = '';
$identifier = '';

if (lh_request_method() === 'POST') {
    lh_require_csrf();

    $identifier = lh_post('identifier');
    $password = lh_post('password');

    if ($identifier === '' || $password === '') {
        $error = 'Email/username and password are required.';
        lh_set_status(422);
    } elseif (lh_auth_attempt($identifier, $password)) {
        lh_flash_set('success', 'You are now signed in.');
        lh_redirect($redirect, 302);
    } else {
        $error = 'Invalid login credentials.';
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
        <p>Sign in with the email address or username you registered with.</p>
        <?php if ($error !== ''): ?>
            <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
        <?php endif; ?>
        <form action="/auth/login" method="post">
            <?php echo lh_csrf_input(); ?>
            <input type="hidden" name="redirect" value="<?php echo lh_e($redirect); ?>">

            <p>
                <label for="identifier">Email or Username</label>
                <input
                    id="identifier"
                    name="identifier"
                    type="text"
                    value="<?php echo lh_e($identifier); ?>"
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
        <p><a href="/auth/forgot-password">Forgot your password?</a></p>
    </article>

    <article class="auth-card auth-card-aside">
        <h2>New Here?</h2>
        <p>Create a real account, manage your profile from the dashboard, and use email-based password reset when needed.</p>
        <p><a href="/auth/register">Create an account</a></p>
        <p>Bearer token auth remains available for API routes.</p>
    </article>
</section>
