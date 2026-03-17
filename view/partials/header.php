<?php

/**
 * Header partial
 *
 * Variables available:
 * - $data: Custom data
 */
?>
<header>
    <h1><?php echo lh_e($data['site_name'] ?? 'Lighthouse') ?></h1>
    <nav>
        <ul>
            <li><a href="/">Home</a></li>
            <li><a href="/about">About</a></li>
        </ul>
    </nav>
</header>