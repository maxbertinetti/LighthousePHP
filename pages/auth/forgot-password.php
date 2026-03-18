<?php

lh_require_guest('/dashboard/home');

$email = '';
$submitted = false;

if (lh_request_method() === 'POST') {
    lh_require_csrf();
    $email = lh_post('email');
    $submitted = true;

    if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        lh_auth_request_password_reset($email);
    }
}

lh_set_data([
    'title' => 'Forgot Password',
    'description' => 'Request a password reset email.',
]);
?>

<section class="auth-grid">
    <article class="auth-card auth-card-form">
        <h1>Forgot Password</h1>
        <p>Enter your account email and we will send a reset link if the account exists.</p>
        <?php if ($submitted): ?>
            <p><strong>Check your email:</strong> If an account matches that address, a reset link has been sent.</p>
        <?php endif; ?>
        <form action="/auth/forgot-password" method="post">
            <?php echo lh_csrf_input(); ?>
            <p>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?php echo lh_e($email); ?>" autocomplete="email" required>
            </p>
            <p><button type="submit">Send Reset Link</button></p>
        </form>
    </article>

    <article class="auth-card auth-card-aside">
        <h2>Reset Flow</h2>
        <p>Reset emails are written to the local Lighthouse outbox during development so you can inspect the generated link.</p>
    </article>
</section>
