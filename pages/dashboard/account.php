<?php

lh_require_auth('/dashboard/account');

$user = lh_auth_user();
$profileErrors = [];
$passwordErrors = [];
$activeTab = lh_query('tab', 'profile');

if (lh_request_method() === 'POST') {
    lh_require_csrf();
    $action = lh_post('action');

    if ($action === 'profile') {
        $profileErrors = lh_auth_update_profile(
            (int) $user['id'],
            lh_post('name'),
            lh_post('username'),
            lh_post('email')
        );

        if ($profileErrors === []) {
            lh_flash_set('success', 'Your profile has been updated.');
            lh_redirect('/dashboard/account?tab=profile', 302);
        }

        $activeTab = 'profile';
        lh_set_status(422);
    } elseif ($action === 'password') {
        $passwordErrors = lh_auth_change_password(
            (int) $user['id'],
            lh_post('current_password'),
            lh_post('new_password'),
            lh_post('new_password_confirmation')
        );

        if ($passwordErrors === []) {
            lh_flash_set('success', 'Your password has been updated.');
            lh_redirect('/dashboard/account?tab=password', 302);
        }

        $activeTab = 'password';
        lh_set_status(422);
    }

    $user = lh_auth_user();
}

lh_set_data([
    'title' => 'Account',
    'description' => 'Manage your Lighthouse account.',
]);
?>

<section class="dashboard-grid">
    <article class="dashboard-card dashboard-card-primary">
        <h1>Account</h1>
        <p>Manage your profile details and sign-in credentials.</p>
        <p>Signed in as <strong><?php echo lh_e((string) $user['email']); ?></strong>.</p>
    </article>

    <article class="dashboard-card">
        <h2>Profile</h2>
        <?php if ($activeTab === 'profile'): ?>
            <?php foreach ($profileErrors as $error): ?>
                <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <form action="/dashboard/account?tab=profile" method="post">
            <?php echo lh_csrf_input(); ?>
            <input type="hidden" name="action" value="profile">
            <p><label for="name">Name</label><input id="name" name="name" type="text" value="<?php echo lh_e((string) $user['name']); ?>" required></p>
            <p><label for="username">Username</label><input id="username" name="username" type="text" value="<?php echo lh_e((string) $user['username']); ?>" required></p>
            <p><label for="email">Email</label><input id="email" name="email" type="email" value="<?php echo lh_e((string) $user['email']); ?>" required></p>
            <p><button type="submit">Update Profile</button></p>
        </form>
    </article>

    <article class="dashboard-card">
        <h2>Password</h2>
        <?php if ($activeTab === 'password'): ?>
            <?php foreach ($passwordErrors as $error): ?>
                <p><strong>Error:</strong> <?php echo lh_e($error); ?></p>
            <?php endforeach; ?>
        <?php endif; ?>
        <form action="/dashboard/account?tab=password" method="post">
            <?php echo lh_csrf_input(); ?>
            <input type="hidden" name="action" value="password">
            <p><label for="current_password">Current Password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required></p>
            <p><label for="new_password">New Password</label><input id="new_password" name="new_password" type="password" autocomplete="new-password" required></p>
            <p><label for="new_password_confirmation">Confirm New Password</label><input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" required></p>
            <p><button type="submit">Change Password</button></p>
        </form>
    </article>
</section>
