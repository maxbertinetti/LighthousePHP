<?php

/**
 * SQL migration engine for Lighthouse.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Return the migrations directory.
 *
 * @param string|null $root
 * @return string
 */
function lh_migration_dir(?string $root = null): string
{
    $base = $root ?? lh_app_root();

    if ($root !== null && lh_has_src_app_root($root)) {
        $base = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'src';
    }

    return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'migrations';
}

/**
 * Ensure the migration tracking table exists.
 *
 * @param PDO $pdo
 * @return void
 */
function lh_migration_ensure_table(PDO $pdo): void
{
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'pgsql') {
        $sql = 'CREATE TABLE IF NOT EXISTS lighthouse_migrations (
            id BIGSERIAL PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            applied_at TIMESTAMP NOT NULL
        )';
    } elseif ($driver === 'mysql') {
        $sql = 'CREATE TABLE IF NOT EXISTS lighthouse_migrations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            applied_at TIMESTAMP NOT NULL
        )';
    } else {
        $sql = 'CREATE TABLE IF NOT EXISTS lighthouse_migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            batch INTEGER NOT NULL,
            applied_at VARCHAR(32) NOT NULL
        )';
    }

    $pdo->exec($sql);
}

/**
 * Parse migration files from disk.
 *
 * Supported filenames:
 * - 20260318_120000_create_users.up.sql
 * - 20260318_120000_create_users.down.sql
 *
 * @param string $directory
 * @return array<int, array{name:string, up:string, down:?string}>
 */
function lh_migration_files(string $directory): array
{
    if (!is_dir($directory)) {
        return [];
    }

    $files = scandir($directory) ?: [];
    $migrations = [];

    foreach ($files as $file) {
        if (!preg_match('/^([A-Za-z0-9_-]+)\.(up|down)\.sql$/', $file, $matches)) {
            continue;
        }

        $name = $matches[1];
        $direction = $matches[2];
        $path = $directory . DIRECTORY_SEPARATOR . $file;

        if (!isset($migrations[$name])) {
            $migrations[$name] = [
                'name' => $name,
                'up' => '',
                'down' => null,
            ];
        }

        $migrations[$name][$direction] = $path;
    }

    ksort($migrations);

    foreach ($migrations as $migration) {
        if ($migration['up'] === '') {
            throw new RuntimeException("Missing up migration for {$migration['name']}.");
        }
    }

    return array_values($migrations);
}

/**
 * Return the applied migrations keyed by name.
 *
 * @param PDO $pdo
 * @return array<string, array{id:int, migration:string, batch:int, applied_at:string}>
 */
function lh_migration_applied(PDO $pdo): array
{
    lh_migration_ensure_table($pdo);

    $stmt = $pdo->query('SELECT id, migration, batch, applied_at FROM lighthouse_migrations ORDER BY id ASC');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $applied = [];

    foreach ($rows as $row) {
        $applied[(string) $row['migration']] = $row;
    }

    return $applied;
}

/**
 * Return migration status rows.
 *
 * @param PDO $pdo
 * @param string $directory
 * @return array<int, array{name:string, status:string, batch:?int, has_down:bool}>
 */
function lh_migration_status(PDO $pdo, string $directory): array
{
    $migrations = lh_migration_files($directory);
    $applied = lh_migration_applied($pdo);
    $rows = [];

    foreach ($migrations as $migration) {
        $record = $applied[$migration['name']] ?? null;
        $rows[] = [
            'name' => $migration['name'],
            'status' => $record === null ? 'pending' : 'applied',
            'batch' => $record === null ? null : (int) $record['batch'],
            'has_down' => $migration['down'] !== null,
        ];
    }

    return $rows;
}

/**
 * Return the next migration batch number.
 *
 * @param PDO $pdo
 * @return int
 */
function lh_migration_next_batch(PDO $pdo): int
{
    lh_migration_ensure_table($pdo);

    $value = $pdo->query('SELECT MAX(batch) FROM lighthouse_migrations')->fetchColumn();

    return ((int) $value) + 1;
}

/**
 * Parse raw SQL into executable statements.
 *
 * Handles quoted strings and SQL comments well enough for framework-managed
 * migration files.
 *
 * @param string $sql
 * @return array<int, string>
 */
function lh_migration_parse_sql(string $sql): array
{
    $statements = [];
    $buffer = '';
    $length = strlen($sql);
    $inSingle = false;
    $inDouble = false;
    $inLineComment = false;
    $inBlockComment = false;

    for ($index = 0; $index < $length; $index++) {
        $char = $sql[$index];
        $next = $sql[$index + 1] ?? '';

        if ($inLineComment) {
            if ($char === "\n") {
                $inLineComment = false;
            }
            continue;
        }

        if ($inBlockComment) {
            if ($char === '*' && $next === '/') {
                $inBlockComment = false;
                $index++;
            }
            continue;
        }

        if (!$inSingle && !$inDouble) {
            if ($char === '-' && $next === '-') {
                $inLineComment = true;
                $index++;
                continue;
            }

            if ($char === '#') {
                $inLineComment = true;
                continue;
            }

            if ($char === '/' && $next === '*') {
                $inBlockComment = true;
                $index++;
                continue;
            }
        }

        if ($char === "'" && !$inDouble) {
            $escaped = $index > 0 && $sql[$index - 1] === '\\';

            if (!$escaped) {
                $inSingle = !$inSingle;
            }
        } elseif ($char === '"' && !$inSingle) {
            $escaped = $index > 0 && $sql[$index - 1] === '\\';

            if (!$escaped) {
                $inDouble = !$inDouble;
            }
        }

        if ($char === ';' && !$inSingle && !$inDouble) {
            $statement = trim($buffer);

            if ($statement !== '') {
                $statements[] = $statement;
            }

            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);

    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

/**
 * Execute all statements from a migration file.
 *
 * @param PDO $pdo
 * @param string $sql
 * @return void
 */
function lh_migration_execute(PDO $pdo, string $sql): void
{
    $statements = lh_migration_parse_sql($sql);

    if ($statements === []) {
        throw new RuntimeException('Migration file did not contain any SQL statements.');
    }

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

/**
 * Apply pending migrations.
 *
 * @param PDO $pdo
 * @param string $directory
 * @param int $step
 * @return array<int, string>
 */
function lh_migration_apply(PDO $pdo, string $directory, int $step = 0): array
{
    $migrations = lh_migration_files($directory);
    $applied = lh_migration_applied($pdo);
    $pending = [];

    foreach ($migrations as $migration) {
        if (!isset($applied[$migration['name']])) {
            $pending[] = $migration;
        }
    }

    if ($step > 0) {
        $pending = array_slice($pending, 0, $step);
    }

    if ($pending === []) {
        return [];
    }

    $batch = lh_migration_next_batch($pdo);
    $appliedNames = [];

    foreach ($pending as $migration) {
        $sql = trim((string) file_get_contents($migration['up']));

        if ($sql === '') {
            throw new RuntimeException("Migration {$migration['name']} has an empty up file.");
        }

        $pdo->beginTransaction();

        try {
            lh_migration_execute($pdo, $sql);
            $statement = $pdo->prepare(
                'INSERT INTO lighthouse_migrations (migration, batch, applied_at) VALUES (:migration, :batch, :applied_at)'
            );
            $statement->execute([
                'migration' => $migration['name'],
                'batch' => $batch,
                'applied_at' => gmdate(DATE_ATOM),
            ]);
            $pdo->commit();
            $appliedNames[] = $migration['name'];
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            throw new RuntimeException("Failed applying migration {$migration['name']}: " . $throwable->getMessage(), 0, $throwable);
        }
    }

    return $appliedNames;
}

/**
 * Roll back applied migrations.
 *
 * When `$step` is zero, the latest batch is rolled back. Otherwise the latest
 * `$step` applied migrations are reverted one by one.
 *
 * @param PDO $pdo
 * @param string $directory
 * @param int $step
 * @return array<int, string>
 */
function lh_migration_rollback(PDO $pdo, string $directory, int $step = 0): array
{
    lh_migration_ensure_table($pdo);

    $migrations = [];

    foreach (lh_migration_files($directory) as $migration) {
        $migrations[$migration['name']] = $migration;
    }

    if ($step > 0) {
        $query = 'SELECT migration FROM lighthouse_migrations ORDER BY batch DESC, id DESC LIMIT ' . (int) $step;
    } else {
        $latestBatch = (int) $pdo->query('SELECT MAX(batch) FROM lighthouse_migrations')->fetchColumn();

        if ($latestBatch === 0) {
            return [];
        }

        $query = 'SELECT migration FROM lighthouse_migrations WHERE batch = ' . $latestBatch . ' ORDER BY id DESC';
    }

    $rows = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

    if ($rows === []) {
        return [];
    }

    $rolledBack = [];

    foreach ($rows as $row) {
        $name = (string) $row['migration'];
        $migration = $migrations[$name] ?? null;

        if ($migration === null || $migration['down'] === null) {
            throw new RuntimeException("Missing down migration for {$name}.");
        }

        $sql = trim((string) file_get_contents($migration['down']));

        if ($sql === '') {
            throw new RuntimeException("Migration {$name} has an empty down file.");
        }

        $pdo->beginTransaction();

        try {
            lh_migration_execute($pdo, $sql);
            $statement = $pdo->prepare('DELETE FROM lighthouse_migrations WHERE migration = :migration');
            $statement->execute(['migration' => $name]);
            $pdo->commit();
            $rolledBack[] = $name;
        } catch (Throwable $throwable) {
            $pdo->rollBack();
            throw new RuntimeException("Failed rolling back migration {$name}: " . $throwable->getMessage(), 0, $throwable);
        }
    }

    return $rolledBack;
}

/**
 * Connect to the configured database for migrations.
 *
 * @param string $environment
 * @param string|null $root
 * @return PDO
 */
function lh_migration_connect(string $environment = 'development', ?string $root = null): PDO
{
    putenv("APP_ENV={$environment}");
    lh_env_set_override($environment);
    $projectRoot = $root ?? lh_project_root();
    lh_project_root_set($projectRoot);
    lh_config_set_base_dir(rtrim(lh_app_root(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'config');
    lh_config_reset();
    lh_db_disconnect();
    lh_load_config();
    lh_db_connect_from_config();

    return lh_db();
}
