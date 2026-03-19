<?php

lh_require_auth('/dashboard/home');

if (lh_request_method() === 'POST') {
    lh_require_csrf();
    lh_logout();
    lh_flash_set('success', 'You have been logged out.');
    lh_redirect('/', 302);
}

lh_set_data([
    'title' => 'Log Out',
    'description' => 'Sign out of the Lighthouse dashboard.',
]);
?>

<section>
    <article>
        <h1>Log Out</h1>
        <p>You are signed in as <strong><?php echo lh_e((string) (lh_auth_user()['email'] ?? '')); ?></strong>.</p>
        <p>Submit the form below to end the current session.</p>
        <form action="/auth/logout" method="post">
            <?php echo lh_csrf_input(); ?>
            <p>
                <button type="submit">Confirm Log Out</button>
            </p>
        </form>
    </article>
</section>
