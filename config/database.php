<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

/**
 * Shared PDO connection
 */
function pdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_DRIVER !== 'mysql') {
        throw new RuntimeException('Only MySQL/MariaDB is supported in this project setup.');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        $pdo->exec("SET time_zone = '+05:30'");
        $pdo->exec("SET NAMES " . DB_CHARSET);
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        throw new RuntimeException('Database connection failed.');
    }

    return $pdo;
}