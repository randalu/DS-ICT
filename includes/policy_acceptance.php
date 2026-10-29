<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/policy_content.php';
require_once __DIR__ . '/published_page_audit.php';

function ict_policy_acceptance_columns(): array
{
    return [
        'booking_status' => "VARCHAR(30) NOT NULL DEFAULT 'planning'",
        'booking_terms_version' => 'VARCHAR(40) NULL',
        'booking_terms_accepted_at' => 'DATETIME NULL',
        'cancellation_policy_version' => 'VARCHAR(40) NULL',
        'cancellation_policy_accepted_at' => 'DATETIME NULL',
        'payment_policy_version' => 'VARCHAR(40) NULL',
        'privacy_policy_version' => 'VARCHAR(40) NULL',
        'marketing_consent' => 'TINYINT(1) NOT NULL DEFAULT 0',
        'marketing_consent_at' => 'DATETIME NULL',
        'customer_ip' => 'VARCHAR(45) NULL',
        'customer_user_agent' => 'VARCHAR(512) NULL',
        'policy_snapshot_hash' => 'CHAR(64) NULL',
        'policy_snapshot_json' => 'LONGTEXT NULL',
        'policy_accepted_at' => 'DATETIME NULL',
        'booking_confirmed_at' => 'DATETIME NULL',
        'booking_confirmed_by_admin_user_id' => 'BIGINT UNSIGNED NULL',
    ];
}

function ensure_customer_policy_acceptance_schema(): void
{
    if (!db_table_exists('customer_itinerary_plans')) {
        return;
    }

    foreach (ict_policy_acceptance_columns() as $column => $definition) {
        if (db_column_exists('customer_itinerary_plans', $column)) {
            continue;
        }

        try {
            db()->exec("ALTER TABLE customer_itinerary_plans ADD COLUMN `{$column}` {$definition}");
        } catch (Throwable $exception) {
            if (!str_contains(strtolower($exception->getMessage()), 'duplicate column')) {
                throw $exception;
            }
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS customer_policy_acceptances (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plan_id BIGINT UNSIGNED NOT NULL,
            customer_user_id BIGINT UNSIGNED NOT NULL,
            booking_terms_version VARCHAR(40) NOT NULL,
            booking_terms_accepted_at DATETIME NOT NULL,
            cancellation_policy_version VARCHAR(40) NOT NULL,
            cancellation_policy_accepted_at DATETIME NOT NULL,
            payment_policy_version VARCHAR(40) NOT NULL,
            privacy_policy_version VARCHAR(40) NOT NULL,
            marketing_consent TINYINT(1) NOT NULL DEFAULT 0,
            marketing_consent_at DATETIME NULL,
            customer_ip VARCHAR(45) NULL,
            customer_user_agent VARCHAR(512) NULL,
            policy_snapshot_hash CHAR(64) NOT NULL,
            policy_snapshot_json LONGTEXT NOT NULL,
            accepted_at DATETIME NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_policy_acceptances_plan (plan_id, accepted_at),
            KEY idx_policy_acceptances_customer (customer_user_id, accepted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function ict_policy_published_documents(bool $forUpdate = false): array
{
    if ($forUpdate && !db()->inTransaction()) { throw new LogicException('Policy locks require an active transaction.'); }
    if (!db_table_exists('static_pages')) {
        return [];
    }

    $documents = ict_policy_documents();
    $published = [];
    foreach (ict_policy_required_keys() as $key) {
        $document = $documents[$key] ?? null;
        if (!is_array($document)) {
            continue;
        }

        $page = fetch_one(
            'SELECT id, title, slug, content, meta_title, meta_description, updated_at FROM static_pages WHERE slug = :slug AND is_published = 1 LIMIT 1' . ($forUpdate ? ' FOR UPDATE' : ''),
            ['slug' => $document['slug']]
        );
        if (!$page || trim((string) ($page['content'] ?? '')) === '') {
            continue;
        }
        if (ict_policy_has_review_marker(implode("\n", [
            (string) ($page['title'] ?? ''),
            (string) ($page['content'] ?? ''),
            (string) ($page['meta_title'] ?? ''),
            (string) ($page['meta_description'] ?? ''),
        ]))) {
            continue;
        }

        if (function_exists('published_static_page_placeholder_issues')) {
            $issues = published_static_page_placeholder_issues($page);
            if ($issues) {
                continue;
            }
        }

        $title = (string) ($page['title'] ?? $document['title']);
        $renderedContent = render_public_content((string) $page['content']);

        $published[$key] = [
            'id' => (int) ($page['id'] ?? 0),
            'title' => $title,
            'slug' => (string) ($page['slug'] ?? $document['slug']),
            'content' => $renderedContent,
            'version' => ict_policy_version_for_document($title, $renderedContent, (string) ($page['slug'] ?? $document['slug'])),
            'updated_at' => (string) ($page['updated_at'] ?? ''),
        ];
    }

    return $published;
}

function ict_policy_version_for_content(string $content): string
{
    return ict_policy_version_for_document('', $content, '');
}

function ict_policy_version_for_document(string $title, string $content, string $slug = ''): string
{
    return 'v1-' . substr(hash('sha256', trim($slug) . "\0" . trim($title) . "\0" . $content), 0, 32);
}

function ict_policy_acceptance_available(): bool
{
    return count(ict_policy_published_documents()) === count(ict_policy_required_keys());
}

function ict_policy_acceptance_snapshot(array $documents): array
{
    $snapshot = [];
    foreach (ict_policy_required_keys() as $key) {
        $document = $documents[$key] ?? [];
        $snapshot[$key] = [
            'slug' => (string) ($document['slug'] ?? ''),
            'title' => (string) ($document['title'] ?? ''),
            'version' => (string) ($document['version'] ?? ''),
            'content' => (string) ($document['content'] ?? ''),
            'content_sha256' => hash('sha256', (string) ($document['content'] ?? '')),
        ];
    }

    $json = json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('Could not create policy snapshot.');
    }

    return [
        'json' => $json,
        'hash' => hash('sha256', $json),
    ];
}

function ict_booking_status_label(?string $status): string
{
    return match (strtolower(trim((string) $status))) {
        'awaiting_acceptance' => 'Awaiting customer acceptance',
        'accepted' => 'Accepted, awaiting operator confirmation',
        'confirmed' => 'Booking confirmed',
        'cancelled' => 'Booking cancelled',
        default => 'Planning',
    };
}

function ict_policy_plan_acceptance_state(array $plan, ?array $documents = null): array
{
    $documents ??= ict_policy_published_documents();
    $storedVersions = [
        'booking_terms' => trim((string) ($plan['booking_terms_version'] ?? '')),
        'cancellation_refund' => trim((string) ($plan['cancellation_policy_version'] ?? '')),
        'payment_deposit' => trim((string) ($plan['payment_policy_version'] ?? '')),
        'privacy' => trim((string) ($plan['privacy_policy_version'] ?? '')),
    ];
    $versionsMatch = count($documents) === count(ict_policy_required_keys());
    foreach (ict_policy_required_keys() as $key) {
        $currentVersion = trim((string) ($documents[$key]['version'] ?? ''));
        if ($currentVersion === '' || ($storedVersions[$key] ?? '') !== $currentVersion) {
            $versionsMatch = false;
            break;
        }
    }

    $snapshotJson = trim((string) ($plan['policy_snapshot_json'] ?? ''));
    $snapshotHash = trim((string) ($plan['policy_snapshot_hash'] ?? ''));
    $decodedSnapshot = $snapshotJson !== '' ? json_decode($snapshotJson, true) : null;
    $snapshotValid = is_array($decodedSnapshot)
        && hash_equals(hash('sha256', $snapshotJson), $snapshotHash)
        && count($decodedSnapshot) === count(ict_policy_required_keys());
    if ($snapshotValid) {
        foreach (ict_policy_required_keys() as $key) {
            if (!is_array($decodedSnapshot[$key] ?? null)
                || !is_string($decodedSnapshot[$key]['slug'] ?? null)
                || ($decodedSnapshot[$key]['version'] ?? '') !== ($storedVersions[$key] ?? '')
                || !is_string($decodedSnapshot[$key]['title'] ?? null)
                || !is_string($decodedSnapshot[$key]['content'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/', (string) ($decodedSnapshot[$key]['content_sha256'] ?? ''))
                || !hash_equals(
                    (string) ($decodedSnapshot[$key]['content_sha256'] ?? ''),
                    hash('sha256', (string) ($decodedSnapshot[$key]['content'] ?? ''))
                )
                || !hash_equals(
                    (string) ($decodedSnapshot[$key]['version'] ?? ''),
                    ict_policy_version_for_document(
                        (string) ($decodedSnapshot[$key]['title'] ?? ''),
                        (string) ($decodedSnapshot[$key]['content'] ?? ''),
                        (string) ($decodedSnapshot[$key]['slug'] ?? '')
                    )
                )) {
                $snapshotValid = false;
                break;
            }
        }
    }

    $acceptedAt = trim((string) ($plan['policy_accepted_at'] ?? ''));
    $accepted = $snapshotValid && $acceptedAt !== '';

    return [
        'available' => count($documents) === count(ict_policy_required_keys()),
        'accepted' => $accepted,
        'current_versions' => $versionsMatch,
        'ready_to_confirm' => $accepted && $versionsMatch,
        'valid_record' => $snapshotValid,
        'versions' => $storedVersions,
        'accepted_at' => $acceptedAt,
        'documents' => $documents,
    ];
}
