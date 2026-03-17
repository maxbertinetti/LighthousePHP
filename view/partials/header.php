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
    <div class="header-actions">
        <nav>
            <ul>
                <li><a href="/">Home</a></li>
                <li><a href="/style-guide">Style Guide</a></li>
            </ul>
        </nav>
        <button id="theme-toggle" class="theme-toggle" aria-label="Toggle theme" type="button">🌓</button>
        <button id="rtl-toggle" class="rtl-toggle" aria-label="Toggle RTL" type="button">⇄</button>
        <button id="menu-toggle" aria-label="Toggle menu">☰</button>
    </div>
</header>