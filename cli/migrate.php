<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

echo "Running migrations...\n";

// Ensure the schema_migrations table exists — compatible with both
// legacy `migration` (checksum/batch) and simple `version` schemas.
$hasTable = db_table_exists('schema_migrations');
if (!$hasTable) {
    db()->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(255) PRIMARY KEY,
            checksum CHAR(64) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'applied',
            batch INT UNSIGNED NOT NULL DEFAULT 1,
            execution_time_ms INT UNSIGNED NULL,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}
$versionCol = db_column_exists('schema_migrations', 'migration') ? 'migration' : (db_column_exists('schema_migrations', 'version') ? 'version' : 'migration');
$applied = fetch_all("SELECT `{$versionCol}` AS v FROM schema_migrations");
$appliedVersions = array_column($applied, 'v');
$appliedMap = array_fill_keys($appliedVersions, true);

$migrationsDir = BASE_PATH . '/database/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

$runCount = 0;

foreach ($files as $file) {
    $filename = basename($file);
    if (!isset($appliedMap[$filename])) {
        echo "Applying: {$filename}...\n";
        $sql = file_get_contents($file);
        
        try {
            $start = microtime(true);
            // Use a savepoint-friendly approach: DDL causes implicit commit in MySQL, so avoid outer transaction for .sql files.
            $inTxn = false;
            try { db()->beginTransaction(); $inTxn = true; } catch (Throwable $ignored) {}
            try {
                db()->exec($sql);
                if ($inTxn) { try { db()->commit(); } catch (Throwable $ignored) { $inTxn = false; } }
            } catch (Throwable $execErr) {
                if ($inTxn) { try { db()->rollBack(); } catch (Throwable $ignored) {} }
                throw $execErr;
            }
            $ms = (int) ((microtime(true) - $start) * 1000);
            $checksum = hash('sha256', (string) $sql);
            if ($versionCol === 'migration') {
                $maxBatch = (int) (fetch_one('SELECT COALESCE(MAX(batch),0) AS b FROM schema_migrations')['b'] ?? 0);
                execute_query(
                    "INSERT INTO schema_migrations (migration, checksum, status, batch, execution_time_ms) VALUES (:migration, :checksum, 'applied', :batch, :ms)",
                    ['migration' => $filename, 'checksum' => $checksum, 'batch' => $maxBatch + 1, 'ms' => $ms]
                );
            } else {
                execute_query("INSERT INTO schema_migrations (version) VALUES (:version)", ['version' => $filename]);
            }
            echo "  -> Success\n";
            $runCount++;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $isDelimiterIssue = str_contains($sql, 'DELIMITER') && (str_contains($msg, 'DELIMITER') || str_contains($msg, 'syntax'));
            $isAlreadyApplied = str_contains($msg, 'Duplicate column') || str_contains($msg, 'already exists') || str_contains($msg, 'Duplicate entry') || $isDelimiterIssue;
            if ($isAlreadyApplied) {
                echo "  -> Skipped (already applied): " . $msg . "\n";
                try {
                    $checksum = hash('sha256', (string) $sql);
                    if ($versionCol === 'migration') {
                        $maxBatch = (int) (fetch_one('SELECT COALESCE(MAX(batch),0) AS b FROM schema_migrations')['b'] ?? 0);
                        execute_query(
                            "INSERT INTO schema_migrations (migration, checksum, status, batch, execution_time_ms) VALUES (:migration, :checksum, 'applied', :batch, :ms)",
                            ['migration' => $filename, 'checksum' => $checksum, 'batch' => $maxBatch + 1, 'ms' => 0]
                        );
                    } else {
                        execute_query("INSERT INTO schema_migrations (version) VALUES (:version)", ['version' => $filename]);
                    }
                    $runCount++;
                } catch (Throwable $e2) {
                    echo "  -> Failed to record: " . $e2->getMessage() . "\n";
                    exit(1);
                }
                continue;
            }
            echo "  -> Failed: " . $msg . "\n";
            exit(1);
        }
    }
}

// The final programme migration transfers and removes historical reference pages.
// Backups stay under /data, which is ignored by Git and blocked by the web server.
if (db_table_exists('tours') && db_column_exists('tours','program_json')) {
    require_once __DIR__ . '/../includes/tour_consolidation.php';
    $converted = tour_consolidation_activate(BASE_PATH . '/data/tour-content-backups');
    echo 'Tour consolidation: ' . count($converted) . ' programmes transferred; reference-page retirement guards installed.' . PHP_EOL;
}

if ($runCount === 0) {
    echo "Nothing to migrate.\n";
} else {
    echo "Successfully applied {$runCount} migration(s).\n";
}
