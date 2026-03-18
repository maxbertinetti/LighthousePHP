<?php

define('LIGHTHOUSE_ENV', 'testing');
define('LIGHTHOUSE_TESTING', true);

require_once __DIR__ . '/../core/test.php';
require_once __DIR__ . '/../core/test_http.php';
require_once __DIR__ . '/../core/migrate.php';
require_once __DIR__ . '/../core/mail.php';
require_once __DIR__ . '/TestSupport.php';

lh_delete_tree(__DIR__ . '/../data/mail');

$testingDbPath = __DIR__ . '/../db/database.testing.sqlite';

if (file_exists($testingDbPath)) {
    unlink($testingDbPath);
}

touch($testingDbPath);

$pdo = lh_migration_connect('testing', dirname(__DIR__));
lh_migration_apply($pdo, lh_migration_dir(dirname(__DIR__)));

$seedTimestamp = gmdate(DATE_ATOM);
$seedPasswordHash = password_hash('password123', PASSWORD_DEFAULT);
lh_db_execute(
    'INSERT INTO users (email, username, name, password_hash, created_at, updated_at) VALUES (:email, :username, :name, :password_hash, :created_at, :updated_at)',
    [
        'email' => 'admin@example.test',
        'username' => 'admin',
        'name' => 'Admin User',
        'password_hash' => $seedPasswordHash,
        'created_at' => $seedTimestamp,
        'updated_at' => $seedTimestamp,
    ]
);
