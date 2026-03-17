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
 * Connect to the database.
 *
 * @param string $dsn      PDO Data Source Name (e.g., 'sqlite:/path/to/db.sqlite')
 * @param string $user     Database username (optional for SQLite)
 * @param string $password Database password (optional for SQLite)
 * @return void
 */
function lh_db_connect(string $dsn, string $user = '', string $password = ''): void
{
    global $lh_db_connection;

    try {
        $lh_db_connection = new PDO($dsn, $user, $password);
        $lh_db_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
