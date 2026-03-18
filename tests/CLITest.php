<?php

require_once __DIR__ . '/../core/cli.php';

return [
    lh_test('cli help prints usage', function (): void {
        $result = lh_capture_cli(function (): int {
            return lh_cli_main(['lighthousephp', 'help']);
        });

        lh_assert_same(0, $result['code']);
        lh_assert_contains('Usage: lighthousephp <command> [options]', $result['stdout']);
    }),

    lh_test('cli help uses invoked binary name', function (): void {
        $result = lh_capture_cli(function (): int {
            return lh_cli_main(['lighthouse', 'help']);
        });

        lh_assert_same(0, $result['code']);
        lh_assert_contains('Usage: lighthouse <command> [options]', $result['stdout']);
    }),

    lh_test('cli serve dry run uses development router', function (): void {
        $result = lh_capture_cli(function (): int {
            return lh_cli_main(['lighthousephp', 'serve', '--dry-run', '--host=0.0.0.0', '--port=9090']);
        });

        lh_assert_same(0, $result['code']);
        lh_assert_contains('Serving Lighthouse at http://0.0.0.0:9090', $result['stdout']);
        lh_assert_contains('/public/router.php', $result['stdout']);
    }),

    lh_test('cli migrate status works without sql files', function (): void {
        $result = lh_capture_cli(function (): int {
            return lh_cli_main(['lighthousephp', 'migrate', 'status']);
        });

        lh_assert_same(0, $result['code']);
        lh_assert_contains('Migration Status', $result['stdout']);
    }),

    lh_test('cli new creates a scaffold', function (): void {
        $tempRoot = sys_get_temp_dir() . '/lighthouse-cli-' . bin2hex(random_bytes(4));
        $target = $tempRoot . '/demo-app';

        if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Unable to create temp root.');
        }

        try {
            $result = lh_capture_cli(function () use ($target): int {
                return lh_cli_main(['lighthousephp', 'new', $target]);
            });

            lh_assert_same(0, $result['code']);
            lh_assert_true(is_file($target . '/lighthousephp'), 'Expected scaffolded lighthousephp CLI script.');
            lh_assert_true(is_file($target . '/public/index.php'), 'Expected scaffolded public/index.php.');
            lh_assert_contains('Created Lighthouse project', $result['stdout']);
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),
];
