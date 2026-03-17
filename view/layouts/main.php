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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo lh_e($data['title'] ?? 'Lighthouse') ?></title>
    <meta name="description" content="<?php echo lh_e($data['description'] ?? '') ?>">
</head>

<body>
    <?php echo lh_partial('header', $data) ?>
    <main>
        <?php echo $page_content ?>
    </main>
    <?php echo lh_partial('footer', $data) ?>
</body>

</html>