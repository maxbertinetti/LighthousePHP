<?php

/**
 * Database Layer for Lighthouse
 *
 * PDO-based procedural database functions.
 * All functions are prefixed with lh_db_.
 */

// Global PDO connection
$lh_db_connection = null;

/**
 * Override the active database connection.
 *
 * @param PDO|null $connection
 * @return void
 */
function lh_db_set_connection(?PDO $connection): void
{
    global $lh_db_connection;

    $lh_db_connection = $connection;
}

/**
 * Build a PDO DSN from the current configuration contract.
 *
 * @return string
 */
function lh_db_dsn(): string
{
    $driver = (string) lh_config('database.driver');

    if ($driver === 'sqlite') {
        $path = (string) lh_config('database.sqlite_path');
        $root = lh_project_root();

        return 'sqlite:' . $root . '/' . ltrim($path, '/');
    }

    $host = (string) lh_config('database.host');
    $port = lh_config('database.port');
    $name = (string) lh_config('database.name');
    $charset = (string) lh_config('database.charset', 'utf8mb4');

    if ($driver === 'pgsql') {
        return "pgsql:host={$host};port={$port};dbname={$name}";
    }

    return "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
}

/**
 * Connect using the loaded application configuration.
 *
 * @return void
 */
function lh_db_connect_from_config(): void
{
    $dsn = lh_db_dsn();
    $driver = (string) lh_config('database.driver');

    if ($driver === 'sqlite') {
        lh_db_connect($dsn);
        return;
    }

    lh_db_connect(
        $dsn,
        (string) lh_config('database.username'),
        (string) lh_config('database.password')
    );
}

/**
 * Connect to the database.
 *
 * @param string $dsn      PDO Data Source Name (e.g., 'sqlite:/path/to/db.sqlite')
 * @param string $user     Database username (optional for SQLite)
 * @param string $password Database password (optional for SQLite)
 * @return void
 */
function lh_db_connect(string $dsn, string $user = '', string $password = ''): void
{
    try {
        $connection = new PDO($dsn, $user, $password);
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        lh_db_set_connection($connection);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

/**
 * Get the active database connection.
 *
 * @return PDO
 */
function lh_db(): PDO
{
    global $lh_db_connection;

    if ($lh_db_connection === null) {
        die("Database not connected. Call lh_db_connect() first.");
    }

    return $lh_db_connection;
}

/**
 * Disconnect the active database connection.
 *
 * @return void
 */
function lh_db_disconnect(): void
{
    lh_db_set_connection(null);
}

/**
 * Execute a prepared statement and return results.
 *
 * @param string $sql    SQL query with :named placeholders
 * @param array  $params Parameters to bind
 * @return array Array of results
 */
function lh_db_query(string $sql, array $params = []): array
{
    $stmt = lh_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fetch a single row from a query.
 *
 * @param string $sql    SQL query with :named placeholders
 * @param array  $params Parameters to bind
 * @return array|null Row as associative array or null
 */
function lh_db_fetch(string $sql, array $params = []): ?array
{
    $stmt = lh_db()->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: null;
}

/**
 * Execute a statement without returning results (INSERT, UPDATE, DELETE).
 *
 * @param string $sql    SQL query with :named placeholders
 * @param array  $params Parameters to bind
 * @return int Number of affected rows
 */
function lh_db_execute(string $sql, array $params = []): int
{
    $stmt = lh_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/**
 * Get the ID of the last inserted row.
 *
 * @return string Last insert ID
 */
function lh_db_last_id(): string
{
    return lh_db()->lastInsertId();
}

/**
 * Start a database transaction.
 *
 * @return void
 */
function lh_db_begin(): void
{
    lh_db()->beginTransaction();
}

/**
 * Commit the current transaction.
 *
 * @return void
 */
function lh_db_commit(): void
{
    lh_db()->commit();
}

/**
 * Rollback the current transaction.
 *
 * @return void
 */
function lh_db_rollback(): void
{
    lh_db()->rollBack();
}

/**
 * Execute a callback within a transaction.
 *
 * If the callback throws an exception, the transaction is rolled back.
 *
 * @param callable $callback Function to execute
 * @return void
 */
function lh_db_transaction(callable $callback): void
{
    lh_db_begin();

    try {
        $callback();
        lh_db_commit();
    } catch (Exception $e) {
        lh_db_rollback();
        throw $e;
    }
}
