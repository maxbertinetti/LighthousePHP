<?php

/**
 * Layout and Partial Rendering System for Lighthouse
 *
 * Stores layout context and provides rendering with partials.
 */

// Global layout context
$lh_layout_context = [
    'layout' => 'main',
    'data' => [],
];

/**
 * Reset layout state for a fresh request.
 *
 * @return void
 */
function lh_reset_layout(): void
{
    global $lh_layout_context;

    $lh_layout_context = [
        'layout' => 'main',
        'data' => [],
    ];
}

/**
 * Set the layout to use for the current page.
 *
 * @param string $layout Layout name (without .php extension)
 * @return void
 */
function lh_use_layout(string $layout): void
{
    global $lh_layout_context;
    $lh_layout_context['layout'] = $layout;
}

/**
 * Set data to pass to the layout and partials.
 *
 * @param array $data Key-value pairs to make available in templates
 * @return void
 */
function lh_set_data(array $data): void
{
    global $lh_layout_context;
    $lh_layout_context['data'] = array_merge($lh_layout_context['data'], $data);
}

/**
 * Render a layout with content.
 *
 * @param string $content The page content to wrap in the layout
 * @return string Rendered output
 */
function lh_layout(string $content): string
{
    global $lh_layout_context;
    $layout = $lh_layout_context['layout'];
    $data = $lh_layout_context['data'];
    $layoutPath = __DIR__ . '/../view/layouts/' . $layout . '.php';

    if (!file_exists($layoutPath)) {
        trigger_error("Layout not found: {$layout}", E_USER_WARNING);
        return $content;
    }

    ob_start();
    $data = is_array($data) ? $data : [];
    extract($data);
    $page_content = $content;
    require $layoutPath;
    return ob_get_clean();
}

/**
 * Include and render a partial.
 *
 * @param string $name   Partial name (without .php extension)
 * @param array  $data   Data to pass to the partial
 * @return string Rendered partial
 */
function lh_partial(string $name, array $data = []): string
{
    $partialPath = __DIR__ . '/../view/partials/' . $name . '.php';

    if (!file_exists($partialPath)) {
        trigger_error("Partial not found: {$name}", E_USER_WARNING);
        return '<!-- Partial not found: ' . lh_e($name) . ' -->';
    }

    ob_start();
    $data = is_array($data) ? $data : [];
    extract($data);
    require $partialPath;
    return ob_get_clean();
}
