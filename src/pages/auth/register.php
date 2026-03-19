<?php

lh_require_guest('/dashboard/home');

$input = [
    'name' => '',
    'username' => '',
    'email' => '',
];
$errors = [];

if (lh_request_method() === 'POST') {
    lh_require_csrf();

    $input = [
        'name' => lh_post('name'),
        'username' => lh_post('username'),
        'email' => lh_post('email'),
        'password' => lh_post('password'),
        'password_confirmation' => lh_post('password_confirmation'),
    ];

    $errors = lh_auth_validate_registration($input);

    if ($errors === []) {
        $user = lh_auth_create_user($input['name'], $input['username'], $input['email'], $input['password']);
        lh_auth_set_user_session($user);
        lh_flash_set('success', 'Your account has been created.');
        lh_redirect('/dashboard/home', 302);
    }

    lh_set_status(422);
}

lh_set_data([
    'title' => 'Register',
    'description' => 'Create a Lighthouse account.',
]);
?>

<section class="auth-grid">
    <article class="auth-card auth-card-form">
        <h1>Register</h1>
        <p>Create your account to access the dashboard and account tools.</p>
        <?php foreach ($errors as $error): ?>
            <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
        <?php endforeach; ?>
        <form action="/auth/register" method="post">
            <?php echo lh_csrf_input(); ?>
            <p>
                <label for="name">Full Name</label>
                <input id="name" name="name" type="text" value="<?php echo lh_e($input['name']); ?>" autocomplete="name" required>
            </p>
            <p>
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="<?php echo lh_e($input['username']); ?>" autocomplete="username" required>
            </p>
            <p>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?php echo lh_e($input['email']); ?>" autocomplete="email" required>
            </p>
            <p>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
            </p>
            <p>
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
            </p>
            <p><button type="submit">Create Account</button></p>
        </form>
    </article>

    <article class="auth-card auth-card-aside">
        <h2>Already Registered?</h2>
        <p>If you already have an account, sign in and manage your profile from the dashboard.</p>
        <p><a href="/auth/login">Go to login</a></p>
    </article>
</section>
