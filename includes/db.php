<?php
declare(strict_types=1);

/**
 * Shortcut to the shared PDO instance.
 */
if (getenv('MOCK_DB') === '1' && !class_exists('MockPDO')) {
    class MockPDO extends PDO {
        public function __construct() {
            parent::__construct('sqlite::memory:');
        }
        public function exec(string $statement): int|false {
            return 0;
        }
    }
}

function db(): PDO
{
    if (getenv('MOCK_DB') === '1') {
        static $sqlitePdo = null;
        if ($sqlitePdo === null) {
            $sqlitePdo = new MockPDO();
        }
        return $sqlitePdo;
    }
    return pdo();
}

/**
 * Generic query helpers.
 */
function fetch_one(string $sql, array $params = []): ?array
{
    if (getenv('MOCK_DB') === '1') {
        return null;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

function fetch_all(string $sql, array $params = []): array
{
    if (getenv('MOCK_DB') === '1') {
        return [];
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function execute_query(string $sql, array $params = []): bool
{
    if (getenv('MOCK_DB') === '1') {
        return true;
    }
    $stmt = db()->prepare($sql);

    return $stmt->execute($params);
}

function last_insert_id(): string
{
    return db()->lastInsertId();
}

/**
 * Pagination helper.
 */
function paginate_offset(int $page, int $perPage): int
{
    $page = max(1, $page);
    $perPage = max(1, $perPage);

    return ($page - 1) * $perPage;
}

/**
 * Published content helpers.
 */
function db_column_exists(string $table, string $column, bool $refresh = false): bool
{
    if (getenv('MOCK_DB') === '1') {
        return true;
    }
    static $cache = [];
    $cacheKey = $table . '.' . $column;

    if (!$refresh && array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $sql = "
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
        LIMIT 1
    ";

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);

        $cache[$cacheKey] = (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        // If metadata lookup fails, use safe fallbacks in select lists.
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

function db_table_exists(string $table, bool $refresh = false): bool
{
    if (getenv('MOCK_DB') === '1') {
        return true;
    }
    static $cache = [];

    if (!$refresh && array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $sql = "
        SELECT 1
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
        LIMIT 1
    ";

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute(['table' => $table]);
        $cache[$table] = (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function db_index_exists(string $table, string $indexName): bool
{
    static $cache = [];
    $cacheKey = $table . '.' . $indexName;

    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $sql = "
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND INDEX_NAME = :index_name
        LIMIT 1
    ";

    try {
        $stmt = db()->prepare($sql);
        $stmt->execute([
            'table' => $table,
            'index_name' => $indexName,
        ]);
        $cache[$cacheKey] = (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        $cache[$cacheKey] = false;
    }

    return $cache[$cacheKey];
}

