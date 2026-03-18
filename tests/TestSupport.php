<?php

/**
 * Delete a directory tree created during tests.
 *
 * @param string $path
 * @return void
 */
function lh_delete_tree(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_file($path) || is_link($path)) {
        unlink($path);
        return;
    }

    $items = scandir($path) ?: [];

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        lh_delete_tree($path . DIRECTORY_SEPARATOR . $item);
    }

    rmdir($path);
}

/**
 * Capture CLI output for assertions.
 *
 * @param callable $callback
 * @return array{code:int, stdout:string, stderr:string}
 */
function lh_capture_cli(callable $callback): array
{
    $stdout = fopen('php://temp', 'w+');
    $stderr = fopen('php://temp', 'w+');

    lh_cli_set_streams($stdout, $stderr);

    try {
        $code = $callback();
    } finally {
        lh_cli_reset_streams();
    }

    rewind($stdout);
    rewind($stderr);

    $capturedStdout = stream_get_contents($stdout) ?: '';
    $capturedStderr = stream_get_contents($stderr) ?: '';

    fclose($stdout);
    fclose($stderr);

    return [
        'code' => (int) $code,
        'stdout' => $capturedStdout,
        'stderr' => $capturedStderr,
    ];
}
