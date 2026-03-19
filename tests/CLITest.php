<?php

require_once __DIR__ . '/../src/core/cli.php';

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
            lh_assert_true(is_dir($target . '/tests'), 'Expected an empty tests directory.');
            lh_assert_true(!is_file($target . '/tests/bootstrap.php'), 'Did not expect framework tests in generated app.');
            lh_assert_true(!is_dir($target . '/docs'), 'Did not expect framework docs in generated app.');
            lh_assert_true(!is_dir($target . '/src'), 'Expected generated app to be flattened at project root.');
            lh_assert_contains('Created Lighthouse project', $result['stdout']);
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),

    lh_test('install script persists tag selector metadata', function (): void {
        $tempRoot = sys_get_temp_dir() . '/lighthouse-install-' . bin2hex(random_bytes(4));
        $prefix = $tempRoot . '/prefix';
        $repoRoot = dirname(__DIR__);

        if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Unable to create temp root.');
        }

        try {
            $result = lh_run_command(
                ['sh', 'scripts/install.sh', 'maxbertinetti/LighthousePHP', 'tag:0.1.0'],
                [
                    'LIGHTHOUSE_PREFIX' => $prefix,
                    'LIGHTHOUSE_RELEASE_VERSION' => '0.1.0',
                ],
                $repoRoot
            );

            lh_assert_same(0, $result['code'], $result['stderr']);
            lh_assert_contains('Lighthouse installed.', $result['stdout']);

            $metadata = file_get_contents($prefix . '/share/lighthouse/metadata.env');

            if ($metadata === false) {
                throw new RuntimeException('Expected metadata.env to be created.');
            }

            lh_assert_contains('LIGHTHOUSE_REPO=maxbertinetti/LighthousePHP', $metadata);
            lh_assert_contains('LIGHTHOUSE_REF=0.1.0', $metadata);
            lh_assert_contains('LIGHTHOUSE_REF_TYPE=tag', $metadata);
            lh_assert_true(is_dir($prefix . '/share/lighthouse/current/src'), 'Expected framework src payload in install bundle.');
            lh_assert_true(!is_dir($prefix . '/share/lighthouse/current/tests'), 'Did not expect framework tests in install bundle.');
            lh_assert_true(!is_dir($prefix . '/share/lighthouse/current/docs'), 'Did not expect framework docs in install bundle.');
            lh_assert_true(!is_dir($prefix . '/share/lighthouse/current/scripts'), 'Did not expect release scripts in install bundle.');
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),

    lh_test('install script defaults to latest release when no selector is provided', function (): void {
        $tempRoot = sys_get_temp_dir() . '/lighthouse-latest-' . bin2hex(random_bytes(4));
        $prefix = $tempRoot . '/prefix';
        $repoRoot = dirname(__DIR__);

        if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Unable to create temp root.');
        }

        try {
            $result = lh_run_command(
                ['sh', 'scripts/install.sh', 'maxbertinetti/LighthousePHP'],
                [
                    'LIGHTHOUSE_PREFIX' => $prefix,
                    'LIGHTHOUSE_LATEST_RELEASE' => '0.1.0',
                    'LIGHTHOUSE_RELEASE_VERSION' => '0.1.0',
                ],
                $repoRoot
            );

            lh_assert_same(0, $result['code'], $result['stderr']);
            lh_assert_contains('Lighthouse installed.', $result['stdout']);

            $metadata = file_get_contents($prefix . '/share/lighthouse/metadata.env');

            if ($metadata === false) {
                throw new RuntimeException('Expected metadata.env to be created.');
            }

            lh_assert_contains('LIGHTHOUSE_REF=0.1.0', $metadata);
            lh_assert_contains('LIGHTHOUSE_REF_TYPE=latest', $metadata);
            lh_assert_contains('LIGHTHOUSE_INSTALLED_VERSION=0.1.0', $metadata);
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),

    lh_test('installed lighthouse version works for tag-based install metadata', function (): void {
        $tempRoot = sys_get_temp_dir() . '/lighthouse-global-' . bin2hex(random_bytes(4));
        $prefix = $tempRoot . '/prefix';
        $repoRoot = dirname(__DIR__);

        if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Unable to create temp root.');
        }

        try {
            $install = lh_run_command(
                ['sh', 'scripts/install.sh', 'maxbertinetti/LighthousePHP', 'version:0.1.0'],
                [
                    'LIGHTHOUSE_PREFIX' => $prefix,
                    'LIGHTHOUSE_RELEASE_VERSION' => '0.1.0',
                ],
                $repoRoot
            );

            lh_assert_same(0, $install['code'], $install['stderr']);

            $version = lh_run_command([$prefix . '/bin/lighthouse', 'version']);

            lh_assert_same(0, $version['code'], $version['stderr']);
            lh_assert_contains('Lighthouse 0.1.0', $version['stdout']);
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),

    lh_test('release bundle installs through standalone installer script', function (): void {
        $tempRoot = sys_get_temp_dir() . '/lighthouse-bundle-' . bin2hex(random_bytes(4));
        $repoRoot = dirname(__DIR__);
        $distDir = $tempRoot . '/dist';
        $installerDir = $tempRoot . '/installer';
        $prefix = $tempRoot . '/prefix';

        if (!mkdir($tempRoot, 0777, true) && !is_dir($tempRoot)) {
            throw new RuntimeException('Unable to create temp root.');
        }

        try {
            $package = lh_run_command(
                ['sh', 'scripts/package-release.sh', $distDir],
                ['LIGHTHOUSE_RELEASE_VERSION' => '0.1.0'],
                $repoRoot
            );
            lh_assert_same(0, $package['code'], $package['stderr']);
            lh_assert_contains('Created release bundle.', $package['stdout']);

            if (!mkdir($installerDir, 0777, true) && !is_dir($installerDir)) {
                throw new RuntimeException('Unable to create installer dir.');
            }

            if (!copy($repoRoot . '/scripts/install.sh', $installerDir . '/install.sh')) {
                throw new RuntimeException('Unable to copy install.sh.');
            }

            $archiveUrl = 'file://' . $distDir . '/lighthousephp-0.1.0.tar.gz';
            $install = lh_run_command(
                ['sh', 'install.sh', 'maxbertinetti/LighthousePHP', 'tag:0.1.0'],
                [
                    'LIGHTHOUSE_PREFIX' => $prefix,
                    'LIGHTHOUSE_DOWNLOAD_URL' => $archiveUrl,
                ],
                $installerDir
            );

            lh_assert_same(0, $install['code'], $install['stderr']);
            lh_assert_contains('Lighthouse installed.', $install['stdout']);

            $version = lh_run_command([$prefix . '/bin/lighthouse', 'version']);
            lh_assert_same(0, $version['code'], $version['stderr']);
            lh_assert_contains('Lighthouse 0.1.0', $version['stdout']);

            $metadata = file_get_contents($prefix . '/share/lighthouse/metadata.env');

            if ($metadata === false) {
                throw new RuntimeException('Expected metadata.env to be created.');
            }

            lh_assert_contains('LIGHTHOUSE_REF_TYPE=tag', $metadata);
        } finally {
            lh_delete_tree($tempRoot);
        }
    }),
];
