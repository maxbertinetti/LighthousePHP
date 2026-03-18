<?php

lh_require_guest('/dashboard/home');

$token = lh_request_method() === 'POST' ? lh_post('token') : lh_query('token');
$record = $token !== '' ? lh_auth_find_reset_token($token) : null;
$validToken = lh_auth_reset_token_is_valid($record);
$errors = [];

if (lh_request_method() === 'POST') {
    lh_require_csrf();

    $password = lh_post('password');
    $confirm = lh_post('password_confirmation');

    if (!$validToken) {
        $errors[] = 'This reset link is invalid or expired.';
        lh_set_status(400);
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
        lh_set_status(422);
    } elseif ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
        lh_set_status(422);
    } elseif (lh_auth_reset_password($token, $password)) {
        lh_flash_set('success', 'Your password has been reset. You can sign in now.');
        lh_redirect('/auth/login', 302);
    } else {
        $errors[] = 'Unable to reset your password.';
        lh_set_status(400);
    }
}

lh_set_data([
    'title' => 'Reset Password',
    'description' => 'Choose a new account password.',
]);
?>

<section class="auth-grid">
    <article class="auth-card auth-card-form">
        <h1>Reset Password</h1>
        <?php if (!$validToken): ?>
            <p>This reset link is invalid or expired.</p>
            <p><a href="/auth/forgot-password">Request a new reset link</a></p>
        <?php else: ?>
            <p>Choose a new password for <strong><?php echo lh_e((string) $record['email']); ?></strong>.</p>
            <?php foreach ($errors as $error): ?>
                <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
            <?php endforeach; ?>
            <form action="/auth/reset-password" method="post">
                <?php echo lh_csrf_input(); ?>
                <input type="hidden" name="token" value="<?php echo lh_e($token); ?>">
                <p>
                    <label for="password">New Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                </p>
                <p>
                    <label for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                </p>
                <p><button type="submit">Reset Password</button></p>
            </form>
        <?php endif; ?>
    </article>

    <article class="auth-card auth-card-aside">
        <h2>Secure Tokens</h2>
        <p>Password reset tokens are stored hashed in the database and expire automatically after the configured window.</p>
    </article>
</section>
