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

/**
 * Run an external command for assertions.
 *
 * @param array<int, string> $command
 * @param array<string, string> $environment
 * @param string|null $cwd
 * @return array{code:int, stdout:string, stderr:string}
 */
function lh_run_command(array $command, array $environment = [], ?string $cwd = null): array
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $cwd, array_merge($_ENV, $environment));

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to launch command.');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]) ?: '';
    $stderr = stream_get_contents($pipes[2]) ?: '';
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'code' => proc_close($process),
        'stdout' => $stdout,
        'stderr' => $stderr,
    ];
}
