<?php

require_once __DIR__ . '/../core/migrate.php';
require_once __DIR__ . '/../core/cli.php';

/**
 * Write a migration pair into a temporary directory.
 *
 * @param string $dir
 * @param string $name
 * @param string $up
 * @param string $down
 * @return void
 */
function lh_write_migration(string $dir, string $name, string $up, string $down): void
{
    file_put_contents($dir . '/' . $name . '.up.sql', $up);
    file_put_contents($dir . '/' . $name . '.down.sql', $down);
}

/**
 * Create a temporary sqlite PDO connection.
 *
 * @param string $path
 * @return PDO
 */
function lh_test_sqlite(string $path): PDO
{
    $pdo = new PDO('sqlite:' . $path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $pdo;
}

return [
    lh_test('migration parser loads ordered up/down files', function (): void {
        $root = sys_get_temp_dir() . '/lh-migrate-' . bin2hex(random_bytes(4));
        $dir = $root . '/migrations';

        mkdir($dir, 0777, true);

        try {
            lh_write_migration($dir, '20260318_010000_create_posts', 'CREATE TABLE posts (id INTEGER);', 'DROP TABLE posts;');
            lh_write_migration($dir, '20260318_020000_create_comments', 'CREATE TABLE comments (id INTEGER);', 'DROP TABLE comments;');

            $files = lh_migration_files($dir);

            lh_assert_same('20260318_010000_create_posts', $files[0]['name']);
            lh_assert_same('20260318_020000_create_comments', $files[1]['name']);
            lh_assert_true($files[0]['down'] !== null, 'Expected down migration path.');
        } finally {
            lh_delete_tree($root);
        }
    }),

    lh_test('migrations apply and record status', function (): void {
        $root = sys_get_temp_dir() . '/lh-migrate-' . bin2hex(random_bytes(4));
        $dir = $root . '/migrations';
        $dbPath = $root . '/database.sqlite';

        mkdir($dir, 0777, true);

        try {
            lh_write_migration(
                $dir,
                '20260318_010000_create_posts',
                'CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT NOT NULL);',
                'DROP TABLE posts;'
            );

            $pdo = lh_test_sqlite($dbPath);
            $applied = lh_migration_apply($pdo, $dir);
            $status = lh_migration_status($pdo, $dir);
            $count = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'posts'")->fetchColumn();

            lh_assert_same(['20260318_010000_create_posts'], $applied);
            lh_assert_same('applied', $status[0]['status']);
            lh_assert_same(1, $count);
        } finally {
            lh_delete_tree($root);
        }
    }),

    lh_test('migrations roll back latest batch', function (): void {
        $root = sys_get_temp_dir() . '/lh-migrate-' . bin2hex(random_bytes(4));
        $dir = $root . '/migrations';
        $dbPath = $root . '/database.sqlite';

        mkdir($dir, 0777, true);

        try {
            lh_write_migration(
                $dir,
                '20260318_010000_create_posts',
                'CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT NOT NULL);',
                'DROP TABLE posts;'
            );

            $pdo = lh_test_sqlite($dbPath);
            lh_migration_apply($pdo, $dir);
            $rolledBack = lh_migration_rollback($pdo, $dir);
            $count = (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'posts'")->fetchColumn();

            lh_assert_same(['20260318_010000_create_posts'], $rolledBack);
            lh_assert_same(0, $count);
        } finally {
            lh_delete_tree($root);
        }
    }),

    lh_test('cli migrate up and down operate on configured project', function (): void {
        $root = sys_get_temp_dir() . '/lh-cli-migrate-' . bin2hex(random_bytes(4));
        $migrationsDir = $root . '/migrations';
        $configDir = $root . '/config';
        $dbDir = $root . '/db';

        mkdir($migrationsDir, 0777, true);
        mkdir($configDir, 0777, true);
        mkdir($dbDir, 0777, true);

        try {
            file_put_contents($configDir . '/config.ini.example', <<<INI
[app]
name = "LighthousePHP"
base_url = "http://localhost:8000"

[database]
driver = "sqlite"
host = "localhost"
port = 3306
name = "lighthouse"
username = "lighthouse"
password = "secret"
charset = "utf8mb4"
sqlite_path = "db/database.sqlite"

[mail]
host = "127.0.0.1"
port = 1025
username = "mailpit"
password = "mailpit"
encryption = "none"
from_address = "noreply@example.test"
from_name = "LighthousePHP"

[auth]
api_token = "token"
password_reset_expiry_minutes = 60
INI
            );
            copy($configDir . '/config.ini.example', $configDir . '/config.development.ini');

            lh_write_migration(
                $migrationsDir,
                '20260318_010000_create_posts',
                'CREATE TABLE posts (id INTEGER PRIMARY KEY, title TEXT NOT NULL);',
                'DROP TABLE posts;'
            );

            lh_cli_set_root($root);

            $up = lh_capture_cli(function (): int {
                return lh_cli_main(['lighthousephp', 'migrate', 'up']);
            });

            $status = lh_capture_cli(function (): int {
                return lh_cli_main(['lighthousephp', 'migrate', 'status']);
            });

            $down = lh_capture_cli(function (): int {
                return lh_cli_main(['lighthousephp', 'migrate', 'down']);
            });

            lh_assert_same(0, $up['code']);
            lh_assert_contains('Applied 20260318_010000_create_posts', $up['stdout']);
            lh_assert_contains('[batch 1] 20260318_010000_create_posts (down)', $status['stdout']);
            lh_assert_same(0, $down['code']);
            lh_assert_contains('Rolled back 20260318_010000_create_posts', $down['stdout']);
        } finally {
            lh_cli_set_root(null);
            lh_delete_tree($root);
        }
    }),
];
