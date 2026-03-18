<?php

/**
 * Lighthouse CLI commands and parser.
 */

require_once __DIR__ . '/migrate.php';

$lh_cli_streams = [
    'out' => STDOUT,
    'err' => STDERR,
];

$lh_cli_root_override = null;

/**
 * Override CLI streams, primarily for tests.
 *
 * @param resource $out
 * @param resource $err
 * @return void
 */
function lh_cli_set_streams($out, $err): void
{
    global $lh_cli_streams;

    $lh_cli_streams = [
        'out' => $out,
        'err' => $err,
    ];
}

/**
 * Reset CLI streams to their defaults.
 *
 * @return void
 */
function lh_cli_reset_streams(): void
{
    global $lh_cli_streams;

    $lh_cli_streams = [
        'out' => STDOUT,
        'err' => STDERR,
    ];
}

/**
 * Return the project root path.
 *
 * @return string
 */
function lh_cli_root(): string
{
    global $lh_cli_root_override;

    if (is_string($lh_cli_root_override) && $lh_cli_root_override !== '') {
        return $lh_cli_root_override;
    }

    return dirname(__DIR__);
}

/**
 * Override the CLI root, primarily for tests.
 *
 * @param string|null $root
 * @return void
 */
function lh_cli_set_root(?string $root): void
{
    global $lh_cli_root_override;

    $lh_cli_root_override = $root;
    lh_config_set_base_dir($root === null ? null : rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config');
}

/**
 * Return the PHP binary to use for subprocesses.
 *
 * @return string
 */
function lh_cli_php_binary(): string
{
    return PHP_BINARY !== '' ? PHP_BINARY : 'php';
}

/**
 * Print a line to stdout.
 *
 * @param string $message
 * @return void
 */
function lh_cli_out(string $message = ''): void
{
    global $lh_cli_streams;

    fwrite($lh_cli_streams['out'], $message . PHP_EOL);
}

/**
 * Print a line to stderr.
 *
 * @param string $message
 * @return void
 */
function lh_cli_err(string $message): void
{
    global $lh_cli_streams;

    fwrite($lh_cli_streams['err'], $message . PHP_EOL);
}

/**
 * Parse CLI options in `--key=value` or `--key value` form.
 *
 * @param array<int, string> $arguments
 * @return array{0:array<string, mixed>, 1:array<int, string>}
 */
function lh_cli_parse_options(array $arguments): array
{
    $options = [];
    $positionals = [];
    $count = count($arguments);

    for ($index = 0; $index < $count; $index++) {
        $argument = $arguments[$index];

        if (strpos($argument, '--') !== 0) {
            $positionals[] = $argument;
            continue;
        }

        $option = substr($argument, 2);

        if ($option === '') {
            continue;
        }

        if (strpos($option, '=') !== false) {
            [$key, $value] = explode('=', $option, 2);
            $options[$key] = $value;
            continue;
        }

        $next = $arguments[$index + 1] ?? null;

        if (is_string($next) && strpos($next, '--') !== 0) {
            $options[$option] = $next;
            $index++;
            continue;
        }

        $options[$option] = true;
    }

    return [$options, $positionals];
}

/**
 * Print CLI usage information.
 *
 * @return void
 */
function lh_cli_help(): void
{
    lh_cli_out('Lighthouse CLI');
    lh_cli_out('');
    lh_cli_out('Usage: lighthousephp <command> [options]');
    lh_cli_out('');
    lh_cli_out('Commands:');
    lh_cli_out('  new <path>            Create a new Lighthouse project scaffold');
    lh_cli_out('  serve                 Start the app in development mode');
    lh_cli_out('  test                  Run the Lighthouse test suite');
    lh_cli_out('  migrate status        Show migration status');
    lh_cli_out('  migrate up            Apply pending migrations');
    lh_cli_out('  migrate down          Roll back migrations');
    lh_cli_out('  help                  Show this help message');
}

/**
 * Recursively copy a directory tree.
 *
 * @param string $source
 * @param string $destination
 * @return void
 */
function lh_cli_copy_tree(string $source, string $destination): void
{
    if (is_dir($source)) {
        if (!is_dir($destination) && !mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new RuntimeException("Unable to create directory: {$destination}");
        }

        $items = scandir($source) ?: [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            lh_cli_copy_tree($source . DIRECTORY_SEPARATOR . $item, $destination . DIRECTORY_SEPARATOR . $item);
        }

        return;
    }

    $parent = dirname($destination);

    if (!is_dir($parent) && !mkdir($parent, 0777, true) && !is_dir($parent)) {
        throw new RuntimeException("Unable to create directory: {$parent}");
    }

    if (!copy($source, $destination)) {
        throw new RuntimeException("Unable to copy file: {$source}");
    }
}

/**
 * Generate a new project scaffold.
 *
 * @param array<string, mixed> $options
 * @param array<int, string> $args
 * @return int
 */
function lh_cli_command_new(array $options, array $args): int
{
    $target = $args[0] ?? '';

    if ($target === '') {
        lh_cli_err('Missing target path. Usage: lighthousephp new <path>');
        return 1;
    }

    $targetPath = $target[0] === DIRECTORY_SEPARATOR ? $target : getcwd() . DIRECTORY_SEPARATOR . $target;

    if (file_exists($targetPath) && (scandir($targetPath) ?: []) !== ['.', '..']) {
        lh_cli_err("Target directory must be empty: {$targetPath}");
        return 1;
    }

    if (!file_exists($targetPath) && !mkdir($targetPath, 0777, true) && !is_dir($targetPath)) {
        lh_cli_err("Unable to create target directory: {$targetPath}");
        return 1;
    }

    $copyMap = [
        '.gitignore',
        'config',
        'core',
        'docs',
        'migrations',
        'pages',
        'public',
        'tests',
        'view',
        'lighthousephp',
    ];

    foreach ($copyMap as $relativePath) {
        lh_cli_copy_tree(lh_cli_root() . DIRECTORY_SEPARATOR . $relativePath, $targetPath . DIRECTORY_SEPARATOR . $relativePath);
    }

    foreach (['cache', 'data', 'db'] as $directory) {
        $path = $targetPath . DIRECTORY_SEPARATOR . $directory;

        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $gitkeep = $path . DIRECTORY_SEPARATOR . '.gitkeep';

        if (!file_exists($gitkeep)) {
            file_put_contents($gitkeep, '');
        }
    }

    $devDb = $targetPath . '/db/database.sqlite';
    $testDb = $targetPath . '/db/database.testing.sqlite';

    if (!file_exists($devDb)) {
        touch($devDb);
    }

    if (!file_exists($testDb)) {
        touch($testDb);
    }

    chmod($targetPath . '/lighthousephp', 0755);

    lh_cli_out("Created Lighthouse project at {$targetPath}");
    return 0;
}

/**
 * Run a subprocess with environment overrides.
 *
 * @param array<int, string> $command
 * @param array<string, string> $environment
 * @return int
 */
function lh_cli_passthru(array $command, array $environment = []): int
{
    $descriptors = [
        0 => STDIN,
        1 => STDOUT,
        2 => STDERR,
    ];

    $process = proc_open($command, $descriptors, $pipes, lh_cli_root(), array_merge($_ENV, $environment));

    if (!is_resource($process)) {
        throw new RuntimeException('Unable to launch subprocess.');
    }

    return proc_close($process);
}

/**
 * Start the development server.
 *
 * @param array<string, mixed> $options
 * @return int
 */
function lh_cli_command_serve(array $options): int
{
    $host = (string) ($options['host'] ?? '127.0.0.1');
    $port = (string) ($options['port'] ?? '8000');
    $router = lh_cli_root() . '/public/router.php';
    $publicDir = lh_cli_root() . '/public';
    $command = [
        lh_cli_php_binary(),
        '-S',
        "{$host}:{$port}",
        '-t',
        $publicDir,
        $router,
    ];

    lh_cli_out("Serving Lighthouse at http://{$host}:{$port}");

    if (!empty($options['dry-run'])) {
        lh_cli_out(implode(' ', array_map('escapeshellarg', $command)));
        return 0;
    }

    return lh_cli_passthru($command, ['APP_ENV' => 'development']);
}

/**
 * Run the test suite.
 *
 * @param array<string, mixed> $options
 * @return int
 */
function lh_cli_command_test(array $options): int
{
    $bootstrap = lh_cli_root() . '/tests/bootstrap.php';

    if (!file_exists($bootstrap)) {
        lh_cli_err('Missing tests/bootstrap.php.');
        return 1;
    }

    if (!empty($options['dry-run'])) {
        lh_cli_out('Internal test runner: ' . $bootstrap);
        return 0;
    }

    require_once $bootstrap;

    return lh_run_tests(lh_collect_tests(lh_cli_root()));
}

/**
 * Run migrations.
 *
 * Supported options:
 * - --env=development|testing|staging|production
 * - --step=<n>
 *
 * @param array<string, mixed> $options
 * @param array<int, string> $args
 * @return int
 */
function lh_cli_command_migrate(array $options, array $args): int
{
    $action = $args[0] ?? 'status';
    $environment = (string) ($options['env'] ?? 'development');
    $step = max(0, (int) ($options['step'] ?? 0));

    try {
        $pdo = lh_migration_connect($environment, lh_cli_root());
        $directory = lh_migration_dir(lh_cli_root());

        switch ($action) {
            case 'status':
                $statusRows = lh_migration_status($pdo, $directory);
                lh_cli_out("Migration Status ({$environment})");
                lh_cli_out('');

                if ($statusRows === []) {
                    lh_cli_out('No SQL migration files found in migrations/.');
                    return 0;
                }

                foreach ($statusRows as $row) {
                    $suffix = $row['status'] === 'applied' ? 'batch ' . $row['batch'] : 'pending';
                    $down = $row['has_down'] ? 'down' : 'no-down';
                    lh_cli_out(sprintf('  [%s] %s (%s)', $suffix, $row['name'], $down));
                }

                return 0;

            case 'up':
                $applied = lh_migration_apply($pdo, $directory, $step);

                if ($applied === []) {
                    lh_cli_out('No pending migrations.');
                    return 0;
                }

                foreach ($applied as $name) {
                    lh_cli_out("Applied {$name}");
                }

                return 0;

            case 'down':
                $rolledBack = lh_migration_rollback($pdo, $directory, $step);

                if ($rolledBack === []) {
                    lh_cli_out('No applied migrations to roll back.');
                    return 0;
                }

                foreach ($rolledBack as $name) {
                    lh_cli_out("Rolled back {$name}");
                }

                return 0;

            default:
                lh_cli_err("Unknown migrate action: {$action}");
                return 1;
        }
    } catch (Throwable $throwable) {
        lh_cli_err($throwable->getMessage());
        return 1;
    } finally {
        lh_db_disconnect();
        lh_config_reset();
        lh_config_set_base_dir(null);
        lh_env_set_override(null);
    }
}

/**
 * Entry point for the Lighthouse CLI.
 *
 * @param array<int, string> $argv
 * @return int
 */
function lh_cli_main(array $argv): int
{
    $arguments = $argv;
    array_shift($arguments);

    $command = $arguments[0] ?? 'help';

    if ($command === '--help' || $command === '-h') {
        $command = 'help';
    }

    if ($command === 'help') {
        lh_cli_help();
        return 0;
    }

    array_shift($arguments);
    [$options, $args] = lh_cli_parse_options($arguments);

    switch ($command) {
        case 'new':
            return lh_cli_command_new($options, $args);

        case 'serve':
            return lh_cli_command_serve($options);

        case 'test':
            return lh_cli_command_test($options);

        case 'migrate':
            return lh_cli_command_migrate($options, $args);

        default:
            lh_cli_err("Unknown command: {$command}");
            lh_cli_help();
            return 1;
    }
}
