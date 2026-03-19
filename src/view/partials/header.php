<?php

/**
 * Header partial
 *
 * Variables available:
 * - $data: Custom data
 */
?>
<header>
    <nav aria-label="Primary">
        <p><a href="/">LighthousePHP</a></p>
        <button type="button" id="menu-toggle" aria-expanded="false" aria-controls="primary-menu" aria-label="Toggle navigation">
            &#9776;
        </button>
        <ul id="primary-menu">
            <li><a href="/style-guide">Style Guide</a></li>
            <?php if (function_exists('lh_is_authenticated') && lh_is_authenticated()): ?>
                <li><a href="/dashboard/home">Dashboard</a></li>
                <li><a href="/dashboard/account">Account</a></li>
                <li class="nav-action">
                    <form class="nav-inline-form" action="/auth/logout" method="post">
                        <?php echo lh_csrf_input(); ?>
                        <button class="nav-inline-button" type="submit">Log Out</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a href="/auth/login">Log In</a></li>
                <li><a href="/auth/register">Register</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
