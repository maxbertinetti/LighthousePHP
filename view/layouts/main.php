<?php

/**
 * Main layout for Lighthouse
 *
 * Variables available:
 * - $page_content: Content from the page
 * - $data: Additional data passed via lh_set_data()
 */
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title><?php echo lh_e($data['title'] ?? 'Lighthouse') ?></title>
    <meta name="description" content="<?php echo lh_e($data['description'] ?? '') ?>">
    <link rel="stylesheet" href="/assets/css/default.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>
    <?php echo lh_partial('header', $data) ?>
    <main>
        <?php $flash = function_exists('lh_flash_get') ? lh_flash_get() : null; ?>
        <?php if (is_array($flash) && isset($flash['message'])): ?>
            <aside aria-live="polite">
                <strong><?php echo lh_e(ucfirst((string) ($flash['type'] ?? 'info'))) ?>:</strong>
                <?php echo lh_e((string) $flash['message']) ?>
            </aside>
        <?php endif; ?>
        <?php echo $page_content ?>
    </main>
    <?php echo lh_partial('footer', $data) ?>
    <script src="/assets/js/default.js"></script>
</body>

</html>
