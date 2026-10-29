<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/policy_acceptance.php';

const CUSTOMER_SESSION_KEY = '_ict_customer_user_id';
const CUSTOMER_OAUTH_STATE_KEY = '_ict_customer_oauth_state';
const CUSTOMER_OAUTH_RETURN_KEY = '_ict_customer_oauth_return_to';
const CUSTOMER_MESSAGE_MAX_LENGTH = 4000;
const CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH = 8000;
const CUSTOMER_CHANGE_REASON_MAX_LENGTH = 2000;
const CUSTOMER_CUSTOMIZER_PENDING_KEY = '_ict_customer_customizer_pending';

/**
 * Whitelist of writable columns per table for dynamic SQL construction.
 * Every column name interpolated into a SQL string must appear here.
 */
function customer_allowed_columns(string $table): array
{
    return match ($table) {
        'contact_inquiries' => [
            'customer_user_id', 'name', 'email', 'preferred_itinerary_id',
            'preferred_itinerary_name', 'trip_support_type', 'message',
            'source_page', 'inquiry_status', 'is_spam', 'tracking_meta_json',
            'id', 'phone', 'whatsapp', 'country', 'interests',
            'accommodation_preference', 'created_at',
        ],
        'itinerary_days' => [
            'id', 'day_number', 'day_end', 'title', 'summary',
            'overnight_location', 'travel_time', 'image_path',
        ],
        'itineraries' => [
            'id', 'name', 'slug', 'is_published',
        ],
        'customer_itinerary_plans' => [
            'customer_user_id', 'contact_inquiry_id', 'source_itinerary_id',
            'plan_title', 'travel_dates', 'traveler_summary', 'trip_support_type',
            'plan_status', 'updated_at', 'id',
            'booking_status', 'booking_confirmed_at', 'booking_confirmed_by_admin_user_id',
            'booking_terms_version', 'booking_terms_accepted_at',
            'cancellation_policy_version', 'cancellation_policy_accepted_at',
            'payment_policy_version', 'privacy_policy_version',
            'marketing_consent', 'marketing_consent_at', 'customer_ip',
            'customer_user_agent', 'policy_snapshot_hash', 'policy_snapshot_json',
            'policy_accepted_at',
        ],
        'contact_inquiry_messages' => [
            'id', 'inquiry_id', 'customer_user_id', 'admin_user_id',
            'sender_type', 'message_body', 'is_visible_to_customer', 'created_at',
        ],
        default => [],
    };
}

/**
 * Validate a column name against the allowlist and return it, or return a safe NULL alias.
 * Use for bare column names (no table prefix). For aliased selects, call this
 * and wrap the result manually.
 */
/**
 * Validate a column name against the allowlist and DB schema.
 * Returns the bare column name if valid, null otherwise.
 */
function customer_column_name(string $table, string $column): ?string
{
    $allowed = customer_allowed_columns($table);
    if (!in_array($column, $allowed, true)) {
        return null;
    }
    return db_column_exists($table, $column) ? $column : null;
}

/**
 * Build a safe SQL column select expression.
 * Returns the column name if valid, or a fallback AS column expression.
 */
function customer_column_select(string $table, string $column, ?string $fallback = null): string
{
    $name = customer_column_name($table, $column);
    if ($name !== null) {
        return $name;
    }
    return ($fallback ?? 'NULL') . ' AS ' . $column;
}

function customer_trip_support_types(): array
{
    return [
        'self_booked_adventure' => [
            'label' => 'Self-Booked Adventure',
            'short_label' => 'Self-booked',
            'description' => 'You want a personalized route plan, while you handle bookings and on-trip support yourself.',
            'included' => ['Personalized trip plan'],
            'excluded' => ['Handling all bookings', '24/7 support during trip', 'Private local guide'],
        ],
        'fully_arranged_trip' => [
            'label' => 'Fully Arranged Trip',
            'short_label' => 'Fully arranged',
            'description' => 'You want us to help shape the plan, coordinate bookings, and support the trip from start to finish.',
            'included' => ['Personalized trip plan', 'Handling all bookings', '24/7 support during trip'],
            'excluded' => ['Private local guide'],
        ],
        'private_guide_trip' => [
            'label' => 'Trip With Private Guide',
            'short_label' => 'Private guide',
            'description' => 'You want the most supported option with a private driver-guide and full planning support.',
            'included' => ['Personalized trip plan', 'Handling all bookings', '24/7 support during trip', 'Private local guide'],
            'excluded' => [],
        ],
    ];
}

function customer_trip_support_type_label(?string $type): string
{
    $type = trim((string) $type);
    $types = customer_trip_support_types();

    return $types[$type]['label'] ?? 'Not selected';
}

function customer_db_column_exists(string $table, string $column): bool
{
    try {
        $stmt = db()->prepare("
            SELECT 1
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND COLUMN_NAME = :column
            LIMIT 1
        ");
        $stmt->execute(['table' => $table, 'column' => $column]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

function customer_db_index_exists(string $table, string $indexName): bool
{
    try {
        $stmt = db()->prepare("
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = :table
              AND INDEX_NAME = :index_name
            LIMIT 1
        ");
        $stmt->execute(['table' => $table, 'index_name' => $indexName]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable) {
        return false;
    }
}

function ensure_customer_accounts_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS customer_users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            google_sub VARCHAR(191) NOT NULL,
            email VARCHAR(190) NOT NULL,
            email_verified TINYINT(1) NOT NULL DEFAULT 0,
            name VARCHAR(190) NULL,
            avatar_url VARCHAR(500) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'active',
            last_login_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_customer_users_google_sub (google_sub),
            KEY idx_customer_users_email (email),
            KEY idx_customer_users_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensure_contact_inquiries_extended_schema();

    if (db_table_exists('contact_inquiries') && !customer_db_column_exists('contact_inquiries', 'customer_user_id')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD COLUMN customer_user_id BIGINT UNSIGNED NULL AFTER id');
        } catch (Throwable $e) {
            if (!str_contains(strtolower($e->getMessage()), 'duplicate column')) {
                throw $e;
            }
        }
    }

    if (db_table_exists('contact_inquiries') && !customer_db_index_exists('contact_inquiries', 'idx_contact_inquiries_customer_user')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD KEY idx_contact_inquiries_customer_user (customer_user_id)');
        } catch (Throwable) {
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS contact_inquiry_messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            inquiry_id BIGINT UNSIGNED NOT NULL,
            customer_user_id BIGINT UNSIGNED NULL,
            admin_user_id BIGINT UNSIGNED NULL,
            sender_type VARCHAR(20) NOT NULL,
            message_body TEXT NOT NULL,
            is_visible_to_customer TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_contact_inquiry_messages_inquiry (inquiry_id, created_at),
            KEY idx_contact_inquiry_messages_customer (customer_user_id, created_at),
            KEY idx_contact_inquiry_messages_admin (admin_user_id, created_at),
            KEY idx_contact_inquiry_messages_visible (is_visible_to_customer)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS customer_itinerary_plans (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_user_id BIGINT UNSIGNED NOT NULL,
            contact_inquiry_id BIGINT UNSIGNED NOT NULL,
            source_itinerary_id BIGINT UNSIGNED NULL,
            plan_title VARCHAR(255) NOT NULL,
            travel_dates VARCHAR(190) NULL,
            traveler_summary VARCHAR(255) NULL,
            trip_support_type VARCHAR(40) NULL,
            plan_status VARCHAR(30) NOT NULL DEFAULT 'planning',
            customer_notes TEXT NULL,
            admin_notes TEXT NULL,
            last_admin_update_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_customer_plan_inquiry (contact_inquiry_id),
            KEY idx_customer_plans_customer (customer_user_id, plan_status),
            KEY idx_customer_plans_source_itinerary (source_itinerary_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensure_customer_policy_acceptance_schema();

    if (!customer_db_column_exists('customer_itinerary_plans', 'trip_support_type')) {
        try {
            db()->exec('ALTER TABLE customer_itinerary_plans ADD COLUMN trip_support_type VARCHAR(40) NULL AFTER traveler_summary');
        } catch (Throwable $e) {
            if (!str_contains(strtolower($e->getMessage()), 'duplicate column')) {
                throw $e;
            }
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS customer_itinerary_days (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plan_id BIGINT UNSIGNED NOT NULL,
            source_itinerary_day_id BIGINT UNSIGNED NULL,
            day_number INT UNSIGNED NOT NULL,
            day_end INT UNSIGNED NULL,
            title VARCHAR(255) NOT NULL,
            summary LONGTEXT NULL,
            overnight_location VARCHAR(180) NULL,
            travel_time VARCHAR(120) NULL,
            image_path VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            admin_updated_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_customer_plan_days_plan (plan_id, day_number, id),
            KEY idx_customer_plan_days_source (source_itinerary_day_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS customer_itinerary_change_requests (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            plan_id BIGINT UNSIGNED NOT NULL,
            plan_day_id BIGINT UNSIGNED NOT NULL,
            customer_user_id BIGINT UNSIGNED NOT NULL,
            contact_inquiry_id BIGINT UNSIGNED NULL,
            request_status VARCHAR(30) NOT NULL DEFAULT 'pending',
            requested_title VARCHAR(255) NULL,
            requested_summary LONGTEXT NULL,
            requested_overnight_location VARCHAR(180) NULL,
            requested_travel_time VARCHAR(120) NULL,
            request_reason TEXT NULL,
            admin_response TEXT NULL,
            reviewed_by_admin_user_id BIGINT UNSIGNED NULL,
            reviewed_at DATETIME NULL,
            applied_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_customer_change_requests_plan (plan_id, request_status, created_at),
            KEY idx_customer_change_requests_day (plan_day_id, request_status),
            KEY idx_customer_change_requests_customer (customer_user_id, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    ensure_analytics_configuration_schema();
    foreach ([
        'customer_google_login_enabled' => '0',
        'customer_google_client_id' => '',
        'customer_google_client_secret' => '',
    ] as $settingKey => $settingValue) {
        execute_query(
            "INSERT INTO analytics_settings (setting_key, setting_value)
             SELECT :setting_key, :setting_value
             WHERE NOT EXISTS (
                 SELECT 1 FROM analytics_settings WHERE setting_key = :setting_key_exists
             )",
            [
                'setting_key' => $settingKey,
                'setting_value' => $settingValue,
                'setting_key_exists' => $settingKey,
            ]
        );
    }

    $ready = true;
}

function customer_google_login_enabled(): bool
{
    ensure_customer_accounts_schema();

    return analytics_setting_get('customer_google_login_enabled', '0') === '1'
        && customer_google_client_id() !== ''
        && customer_google_client_secret() !== '';
}

function customer_google_client_id(): string
{
    return trim((string) analytics_setting_get('customer_google_client_id', ''));
}

function customer_google_client_secret(): string
{
    return trim((string) analytics_setting_get('customer_google_client_secret', ''));
}

function customer_google_redirect_uri(): string
{
    return base_url('account/google/callback');
}

function customer_id(): ?int
{
    $id = $_SESSION[CUSTOMER_SESSION_KEY] ?? null;
    return is_numeric($id) ? (int) $id : null;
}

function customer_user(): ?array
{
    static $cached = null;
    static $loadedId = null;

    $id = customer_id();
    if (!$id) {
        return null;
    }

    if ($loadedId === $id) {
        return $cached;
    }

    ensure_customer_accounts_schema();
    $loadedId = $id;
    $cached = fetch_one(
        "SELECT id, google_sub, email, email_verified, name, avatar_url, status, last_login_at, created_at
         FROM customer_users
         WHERE id = :id AND status = 'active'
         LIMIT 1",
        ['id' => $id]
    );

    if (!$cached) {
        unset($_SESSION[CUSTOMER_SESSION_KEY]);
    }

    return $cached ?: null;
}

function customer_check(): bool
{
    return customer_user() !== null;
}

function customer_require_auth(): array
{
    $user = customer_user();
    if (!$user) {
        $_SESSION[CUSTOMER_OAUTH_RETURN_KEY] = request_path();
        flash_set('error', 'Please sign in with Google to view your account.');
        redirect('account/login');
    }

    return $user;
}

function customer_logout(): void
{
    unset($_SESSION[CUSTOMER_SESSION_KEY], $_SESSION[CUSTOMER_OAUTH_STATE_KEY], $_SESSION[CUSTOMER_OAUTH_RETURN_KEY]);
    session_regenerate_active_id(true);
}

function customer_google_auth_url(?string $returnTo = null): string
{
    ensure_customer_accounts_schema();

    $state = bin2hex(random_bytes(24));
    $_SESSION[CUSTOMER_OAUTH_STATE_KEY] = $state;
    if ($returnTo !== null && $returnTo !== '') {
        $_SESSION[CUSTOMER_OAUTH_RETURN_KEY] = $returnTo;
    }

    $params = [
        'response_type' => 'code',
        'client_id' => customer_google_client_id(),
        'redirect_uri' => customer_google_redirect_uri(),
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function customer_http_json(string $url, array $options = []): array
{
    $method = strtoupper((string) ($options['method'] ?? 'GET'));
    $headers = $options['headers'] ?? [];
    $body = $options['body'] ?? null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : (string) $body);
        }
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Google OAuth request failed' . ($error !== '' ? ': ' . $error : '.'));
        }

        $decoded = json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Google OAuth response was not valid JSON.');
        }
        return $decoded;
    }

    $context = [
        'http' => [
            'method' => $method,
            'timeout' => 15,
            'header' => implode("\r\n", $headers),
        ],
    ];
    if ($body !== null) {
        $context['http']['content'] = is_array($body) ? http_build_query($body) : (string) $body;
    }

    $raw = file_get_contents($url, false, stream_context_create($context));
    if ($raw === false) {
        throw new RuntimeException('Google OAuth request failed.');
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Google OAuth response was not valid JSON.');
    }

    return $decoded;
}

function customer_google_exchange_code(string $code): array
{
    return customer_http_json('https://oauth2.googleapis.com/token', [
        'method' => 'POST',
        'headers' => ['Content-Type: application/x-www-form-urlencoded'],
        'body' => [
            'code' => $code,
            'client_id' => customer_google_client_id(),
            'client_secret' => customer_google_client_secret(),
            'redirect_uri' => customer_google_redirect_uri(),
            'grant_type' => 'authorization_code',
        ],
    ]);
}

function customer_google_userinfo(string $accessToken): array
{
    return customer_http_json('https://www.googleapis.com/oauth2/v3/userinfo', [
        'headers' => ['Authorization: Bearer ' . $accessToken],
    ]);
}

function customer_login_from_google_profile(array $profile): array
{
    ensure_customer_accounts_schema();

    $sub = trim((string) ($profile['sub'] ?? ''));
    $email = strtolower(trim((string) ($profile['email'] ?? '')));
    $emailVerified = filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $name = trim((string) ($profile['name'] ?? ''));
    $avatar = trim((string) ($profile['picture'] ?? ''));

    if ($sub === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Google did not return a valid verified account profile.');
    }

    if (!$emailVerified) {
        throw new RuntimeException('Please use a Google account with a verified email address.');
    }

    execute_query(
        "INSERT INTO customer_users
            (google_sub, email, email_verified, name, avatar_url, status, last_login_at)
         VALUES
            (:google_sub, :email, 1, :name, :avatar_url, 'active', NOW())
         ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            email_verified = 1,
            name = VALUES(name),
            avatar_url = VALUES(avatar_url),
            status = IF(status = 'blocked', status, 'active'),
            last_login_at = NOW()",
        [
            'google_sub' => $sub,
            'email' => $email,
            'name' => $name !== '' ? $name : null,
            'avatar_url' => $avatar !== '' ? $avatar : null,
        ]
    );

    $user = fetch_one(
        "SELECT id, google_sub, email, email_verified, name, avatar_url, status, last_login_at
         FROM customer_users
         WHERE google_sub = :google_sub
         LIMIT 1",
        ['google_sub' => $sub]
    );

    if (!$user || (string) ($user['status'] ?? '') !== 'active') {
        throw new RuntimeException('This customer account is not active.');
    }

    session_regenerate_active_id(true);
    $_SESSION[CUSTOMER_SESSION_KEY] = (int) $user['id'];
    customer_link_inquiries_by_email((int) $user['id'], (string) $user['email']);
    customer_ensure_plans_for_customer($user);

    return $user;
}

function customer_link_inquiries_by_email(int $customerUserId, string $email): int
{
    ensure_customer_accounts_schema();

    $email = strtolower(trim($email));
    if ($customerUserId <= 0 || $email === '' || !db_table_exists('contact_inquiries') || customer_column_name('contact_inquiries', 'customer_user_id') === null) {
        return 0;
    }

    $spamCol = customer_column_name('contact_inquiries', 'is_spam');
    $spamClause = $spamCol !== null ? 'AND ' . $spamCol . ' = 0' : '';
    if (db_column_exists('contact_inquiries', 'admin_guest_only')) { $spamClause .= ' AND admin_guest_only = 0'; }
    execute_query(
        "UPDATE contact_inquiries
         SET customer_user_id = :customer_user_id_set
         WHERE LOWER(email) = :email
           {$spamClause}
           AND (customer_user_id IS NULL OR customer_user_id = :customer_user_id_match)",
        [
            'customer_user_id_set' => $customerUserId,
            'customer_user_id_match' => $customerUserId,
            'email' => $email,
        ]
    );

    return 0;
}

function fetch_customer_inquiries(array $customer): array
{
    ensure_customer_accounts_schema();

    if (!db_table_exists('contact_inquiries')) {
        return [];
    }

    $customerId = (int) ($customer['id'] ?? 0);
    $email = strtolower(trim((string) ($customer['email'] ?? '')));
    $hasCustomerId = customer_column_name('contact_inquiries', 'customer_user_id') !== null;
    $spamCol = customer_column_name('contact_inquiries', 'is_spam');
    $spamClause = $spamCol !== null ? 'AND ci.' . $spamCol . ' = 0' : '';
    if (db_column_exists('contact_inquiries', 'admin_guest_only')) { $spamClause .= ' AND ci.admin_guest_only = 0'; }
    $ownerClause = $hasCustomerId
        ? '(ci.customer_user_id = :customer_user_id OR (ci.customer_user_id IS NULL AND LOWER(ci.email) = :email))'
        : 'LOWER(ci.email) = :email';
    $params = ['email' => $email];
    if ($hasCustomerId) {
        $params['customer_user_id'] = $customerId;
    }

    return fetch_all("
        SELECT
            ci.*,
            COUNT(cim.id) AS message_count,
            MAX(cim.created_at) AS last_message_at
        FROM contact_inquiries ci
        LEFT JOIN contact_inquiry_messages cim ON cim.inquiry_id = ci.id AND cim.is_visible_to_customer = 1
        WHERE {$ownerClause}
          {$spamClause}
        GROUP BY ci.id
        ORDER BY COALESCE(MAX(cim.created_at), ci.created_at) DESC, ci.id DESC
    ", $params);
}

function find_customer_inquiry(int $inquiryId, array $customer): ?array
{
    ensure_customer_accounts_schema();

    if ($inquiryId <= 0 || !db_table_exists('contact_inquiries')) {
        return null;
    }

    $customerId = (int) ($customer['id'] ?? 0);
    $email = strtolower(trim((string) ($customer['email'] ?? '')));
    $hasCustomerId = customer_column_name('contact_inquiries', 'customer_user_id') !== null;
    $spamCol = customer_column_name('contact_inquiries', 'is_spam');
    $spamClause = $spamCol !== null ? 'AND ' . $spamCol . ' = 0' : '';
    if (db_column_exists('contact_inquiries', 'admin_guest_only')) { $spamClause .= ' AND admin_guest_only = 0'; }
    $ownerClause = $hasCustomerId
        ? '(customer_user_id = :customer_user_id OR (customer_user_id IS NULL AND LOWER(email) = :email))'
        : 'LOWER(email) = :email';
    $params = [
        'id' => $inquiryId,
        'email' => $email,
    ];
    if ($hasCustomerId) {
        $params['customer_user_id'] = $customerId;
    }

    return fetch_one("
        SELECT *
        FROM contact_inquiries
        WHERE id = :id
          AND {$ownerClause}
          {$spamClause}
        LIMIT 1
    ", $params);
}

function fetch_contact_inquiry_messages(int $inquiryId, bool $customerVisibleOnly = true): array
{
    ensure_customer_accounts_schema();

    if ($inquiryId <= 0) {
        return [];
    }

    $visibleClause = $customerVisibleOnly ? 'AND is_visible_to_customer = 1' : '';

    return fetch_all("
        SELECT id, inquiry_id, customer_user_id, admin_user_id, sender_type, message_body, is_visible_to_customer, created_at
        FROM contact_inquiry_messages
        WHERE inquiry_id = :inquiry_id
          {$visibleClause}
        ORDER BY id ASC
    ", ['inquiry_id' => $inquiryId]);
}

function create_contact_inquiry_message(
    int $inquiryId,
    string $senderType,
    string $messageBody,
    ?int $customerUserId = null,
    ?int $adminUserId = null,
    bool $visibleToCustomer = true
): int {
    ensure_customer_accounts_schema();

    $messageBody = trim($messageBody);
    if ($inquiryId <= 0 || $messageBody === '') {
        throw new InvalidArgumentException('Message cannot be empty.');
    }

    $messageLength = function_exists('mb_strlen') ? mb_strlen($messageBody) : strlen($messageBody);
    if ($messageLength > CUSTOMER_MESSAGE_MAX_LENGTH) {
        throw new InvalidArgumentException('Message is too long. Please keep it under ' . CUSTOMER_MESSAGE_MAX_LENGTH . ' characters.');
    }

    if (!in_array($senderType, ['customer', 'admin', 'system'], true)) {
        throw new InvalidArgumentException('Invalid message sender.');
    }

    execute_query(
        "INSERT INTO contact_inquiry_messages
            (inquiry_id, customer_user_id, admin_user_id, sender_type, message_body, is_visible_to_customer)
         VALUES
            (:inquiry_id, :customer_user_id, :admin_user_id, :sender_type, :message_body, :is_visible_to_customer)",
        [
            'inquiry_id' => $inquiryId,
            'customer_user_id' => $customerUserId,
            'admin_user_id' => $adminUserId,
            'sender_type' => $senderType,
            'message_body' => $messageBody,
            'is_visible_to_customer' => $visibleToCustomer ? 1 : 0,
        ]
    );

    return (int) last_insert_id();
}

function inquiry_status_label(?string $status): string
{
    $status = strtolower(trim((string) $status));
    $labels = [
        'new' => 'New',
        'follow_up' => 'Follow Up',
        'quoted' => 'Quoted',
        'won' => 'Confirmed',
        'lost' => 'Closed',
        'spam' => 'Hidden',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status !== '' ? $status : 'new'));
}

function customer_plan_status_label(?string $status): string
{
    $status = strtolower(trim((string) $status));
    $labels = [
        'planning' => 'Planning',
        'reviewing' => 'Reviewing',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status !== '' ? $status : 'planning'));
}

function customer_change_request_status_label(?string $status): string
{
    $status = strtolower(trim((string) $status));
    $labels = [
        'pending' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'applied' => 'Applied to plan',
    ];

    return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status !== '' ? $status : 'pending'));
}

function customer_inquiry_traveler_summary(array $inquiry): string
{
    $parts = [];
    $adults = (int) ($inquiry['adults_count'] ?? 0);
    $children = (int) ($inquiry['children_count'] ?? 0);
    if ($adults > 0) {
        $parts[] = $adults . ' adult' . ($adults === 1 ? '' : 's');
    }
    if ($children > 0) {
        $parts[] = $children . ' child' . ($children === 1 ? '' : 'ren');
    }
    if (!$parts && !empty($inquiry['group_size'])) {
        $parts[] = trim((string) $inquiry['group_size']);
    }

    return implode(', ', array_filter($parts));
}

function find_customer_plan_for_itinerary(int $customerUserId, int $itineraryId): ?array
{
    ensure_customer_accounts_schema();

    if ($customerUserId <= 0 || $itineraryId <= 0) {
        return null;
    }

    return fetch_one(
        "SELECT *
         FROM customer_itinerary_plans
         WHERE customer_user_id = :customer_user_id
           AND source_itinerary_id = :source_itinerary_id
           AND plan_status NOT IN ('completed', 'cancelled')
         ORDER BY updated_at DESC, id DESC
         LIMIT 1",
        [
            'customer_user_id' => $customerUserId,
            'source_itinerary_id' => $itineraryId,
        ]
    );
}

function customer_start_itinerary_customization(array $customer, int $itineraryId, string $tripSupportType): ?array
{
    ensure_customer_accounts_schema();

    $customerUserId = (int) ($customer['id'] ?? 0);
    $tripSupportType = array_key_exists($tripSupportType, customer_trip_support_types()) ? $tripSupportType : 'fully_arranged_trip';

    if ($customerUserId <= 0 || $itineraryId <= 0) {
        return null;
    }

    if (!db_table_exists('itineraries') || !db_column_exists('itineraries', 'id')) {
        return null;
    }

    $nameCol = customer_column_name('itineraries', 'name');
    $slugCol = customer_column_name('itineraries', 'slug');
    $publishedCol = customer_column_name('itineraries', 'is_published');
    $itineraryNameSelect = $nameCol !== null ? $nameCol : "CONCAT('Itinerary #', id) AS name";
    $itinerarySlugSelect = $slugCol !== null ? $slugCol : "'' AS slug";
    $publishedFilter = $publishedCol !== null ? 'AND ' . $publishedCol . ' = 1' : '';

    $itinerary = fetch_one(
        "SELECT id, {$itineraryNameSelect}, {$itinerarySlugSelect}
         FROM itineraries
         WHERE id = :id
           {$publishedFilter}
         LIMIT 1",
        ['id' => $itineraryId]
    );
    if (!$itinerary) {
        return null;
    }

    $existingPlan = find_customer_plan_for_itinerary($customerUserId, $itineraryId);
    if ($existingPlan) {
        return customer_update_plan_support_type((int) $existingPlan['id'], $customerUserId, $tripSupportType);
    }

    $customerName = trim((string) ($customer['name'] ?? ''));
    $customerEmail = trim((string) ($customer['email'] ?? ''));
    if ($customerName === '' && $customerEmail !== '') {
        $customerName = ucfirst((string) strtok($customerEmail, '@'));
    }
    if ($customerName === '') {
        $customerName = 'Customer';
    }

    $message = 'Customer started itinerary customization from the website account flow.'
        . "\n\nSelected support type: " . customer_trip_support_type_label($tripSupportType)
        . "\nSource itinerary: " . (string) ($itinerary['name'] ?? 'Selected itinerary');

    $columnValueMap = [
        'customer_user_id' => $customerUserId,
        'name' => substr($customerName, 0, 150),
        'email' => $customerEmail !== '' ? substr($customerEmail, 0, 190) : null,
        'preferred_itinerary_id' => $itineraryId,
        'preferred_itinerary_name' => substr((string) ($itinerary['name'] ?? 'Selected itinerary'), 0, 180),
        'trip_support_type' => $tripSupportType,
        'message' => $message,
        'source_page' => '/account/customize-itinerary',
        'inquiry_status' => 'new',
        'is_spam' => 0,
        'tracking_meta_json' => json_encode([
            'source' => 'account_itinerary_customizer',
            'itinerary_slug' => (string) ($itinerary['slug'] ?? ''),
        ]),
    ];

    $allowedColumns = customer_allowed_columns('contact_inquiries');
    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (in_array($column, $allowedColumns, true) && customer_db_column_exists('contact_inquiries', $column)) {
            $writeData[$column] = $value;
        }
    }

    if (!$writeData || empty($writeData['name']) || empty($writeData['message'])) {
        return null;
    }

    $columns = array_keys($writeData);
    $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
    execute_query(
        'INSERT INTO contact_inquiries (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
        $writeData
    );

    $inquiryId = (int) last_insert_id();
    if ($inquiryId <= 0) {
        return null;
    }

    log_contact_inquiry_activity(
        $inquiryId,
        'customizer_started',
        null,
        $tripSupportType,
        'Customer selected ' . customer_trip_support_type_label($tripSupportType) . ' from itinerary customizer.',
        null
    );

    $plan = customer_create_plan_from_inquiry($inquiryId, $customerUserId);
    if ($plan && customer_column_name('customer_itinerary_plans', 'trip_support_type') !== null) {
        execute_query(
            "UPDATE customer_itinerary_plans SET trip_support_type = :trip_support_type WHERE id = :id",
            ['trip_support_type' => $tripSupportType, 'id' => (int) $plan['id']]
        );
        $plan = fetch_one("SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1", ['id' => (int) $plan['id']]);
    }

    return $plan;
}

/** Change an existing plan under the same row lock used by acceptance/confirmation. */
function customer_update_plan_support_type(int $planId, int $customerUserId, string $tripSupportType): ?array
{
    if (!array_key_exists($tripSupportType, customer_trip_support_types())) {
        throw new RuntimeException('Please choose the type of trip support you want.');
    }
    $pdo = db();
    $ownsTransaction = !$pdo->inTransaction();
    if ($ownsTransaction) {
        $pdo->beginTransaction();
    }
    try {
        $plan = fetch_one(
            'SELECT * FROM customer_itinerary_plans WHERE id = :id AND customer_user_id = :customer_id LIMIT 1 FOR UPDATE',
            ['id' => $planId, 'customer_id' => $customerUserId]
        );
        if (!$plan) {
            if ($ownsTransaction) {
                $pdo->commit();
            }
            return null;
        }
        if ((string) ($plan['trip_support_type'] ?? '') !== $tripSupportType) {
            if (!in_array((string) ($plan['booking_status'] ?? ''), ['planning', 'awaiting_acceptance', 'accepted'], true)
                || in_array((string) ($plan['plan_status'] ?? ''), ['completed', 'cancelled'], true)) {
                throw new RuntimeException('Please contact us to request an amendment to this booking. Your agreed trip support has not changed.');
            }
            execute_query(
                "UPDATE customer_itinerary_plans
                 SET trip_support_type = :support_type, plan_status = 'reviewing', booking_status = 'planning',
                     policy_accepted_at = NULL, booking_terms_accepted_at = NULL, cancellation_policy_accepted_at = NULL
                 WHERE id = :id AND customer_user_id = :customer_id",
                ['support_type' => $tripSupportType, 'id' => $planId, 'customer_id' => $customerUserId]
            );
            execute_query(
                'UPDATE contact_inquiries SET trip_support_type = :support_type WHERE id = :id AND customer_user_id = :customer_id',
                ['support_type' => $tripSupportType, 'id' => (int) $plan['contact_inquiry_id'], 'customer_id' => $customerUserId]
            );
            // The immutable acceptance records and snapshot remain as history, not active acceptance.
            execute_query(
                'INSERT INTO contact_inquiry_activity (inquiry_id, action_type, old_value, new_value, note)
                 VALUES (:inquiry_id, :action, :old_value, :new_value, :note)',
                [
                    'inquiry_id' => (int) $plan['contact_inquiry_id'],
                    'action' => 'customer_plan_support_changed',
                    'old_value' => $plan['trip_support_type'],
                    'new_value' => $tripSupportType,
                    'note' => 'Customer changed trip support. Operator review and fresh policy acceptance are required before booking confirmation.',
                ]
            );
            $plan = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1', ['id' => $planId]);
        }
        if ($ownsTransaction) {
            $pdo->commit();
        }
        return $plan;
    } catch (Throwable $exception) {
        if ($ownsTransaction && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }
}

function customer_create_plan_from_inquiry(int $inquiryId, int $customerUserId): ?array
{
    ensure_customer_accounts_schema();

    if ($inquiryId <= 0 || $customerUserId <= 0) {
        return null;
    }

    $existing = fetch_one(
        "SELECT * FROM customer_itinerary_plans WHERE contact_inquiry_id = :inquiry_id LIMIT 1",
        ['inquiry_id' => $inquiryId]
    );
    if ($existing) {
        return (int) ($existing['customer_user_id'] ?? 0) === $customerUserId ? $existing : null;
    }

    $hasCustomerUserId = customer_column_name('contact_inquiries', 'customer_user_id') !== null;
    $customerSelect = $hasCustomerUserId ? 'ci.customer_user_id' : 'NULL AS customer_user_id';
    $canJoinItineraries = db_table_exists('itineraries') && db_column_exists('itineraries', 'id');
    $itineraryNameCol = customer_column_name('itineraries', 'name');
    $sourceItineraryNameSelect = $canJoinItineraries && $itineraryNameCol !== null ? 'i.' . $itineraryNameCol . ' AS source_itinerary_name' : 'NULL AS source_itinerary_name';
    $sourceItineraryJoin = $canJoinItineraries ? 'LEFT JOIN itineraries i ON i.id = ci.preferred_itinerary_id' : '';

    $inquiry = fetch_one(
        "SELECT ci.*, {$customerSelect}, {$sourceItineraryNameSelect}
         FROM contact_inquiries ci
         {$sourceItineraryJoin}
         WHERE ci.id = :id
         LIMIT 1",
        ['id' => $inquiryId]
    );

    if (!$inquiry || (int) ($inquiry['preferred_itinerary_id'] ?? 0) <= 0) {
        return null;
    }

    if ($hasCustomerUserId && (int) ($inquiry['customer_user_id'] ?? 0) !== $customerUserId) {
        return null;
    }

    if (customer_column_name('contact_inquiries', 'is_spam') !== null && (int) ($inquiry['is_spam'] ?? 0) === 1) {
        return null;
    }

    $sourceItineraryId = (int) $inquiry['preferred_itinerary_id'];
    $sourceTitle = trim((string) ($inquiry['source_itinerary_name'] ?? ''));
    $preferredTitle = trim((string) ($inquiry['preferred_itinerary_name'] ?? ''));
    $planTitle = $preferredTitle !== '' ? $preferredTitle : ($sourceTitle !== '' ? $sourceTitle : 'Custom Sri Lanka Itinerary');
    $travelerSummary = customer_inquiry_traveler_summary($inquiry);

    execute_query(
        "INSERT INTO customer_itinerary_plans
            (customer_user_id, contact_inquiry_id, source_itinerary_id, plan_title, travel_dates, traveler_summary, trip_support_type, plan_status)
         VALUES
            (:customer_user_id, :contact_inquiry_id, :source_itinerary_id, :plan_title, :travel_dates, :traveler_summary, :trip_support_type, 'planning')",
        [
            'customer_user_id' => $customerUserId,
            'contact_inquiry_id' => $inquiryId,
            'source_itinerary_id' => $sourceItineraryId,
            'plan_title' => $planTitle,
            'travel_dates' => trim((string) ($inquiry['travel_dates'] ?? '')) ?: null,
            'traveler_summary' => $travelerSummary !== '' ? $travelerSummary : null,
            'trip_support_type' => trim((string) ($inquiry['trip_support_type'] ?? '')) ?: null,
        ]
    );

    $planId = (int) last_insert_id();
    $days = [];
    if (db_table_exists('itinerary_days') && db_column_exists('itinerary_days', 'itinerary_id')) {
        $dayNumberCol = customer_column_select('itinerary_days', 'day_number');
        $dayEndCol = customer_column_select('itinerary_days', 'day_end');
        $titleCol = customer_column_select('itinerary_days', 'title', "'Day Plan'");
        $summaryCol = customer_column_select('itinerary_days', 'summary');
        $overnightCol = customer_column_select('itinerary_days', 'overnight_location');
        $travelTimeCol = customer_column_select('itinerary_days', 'travel_time');
        $imageCol = customer_column_select('itinerary_days', 'image_path');
        $orderParts = [];
        if (db_column_exists('itinerary_days', 'day_number')) {
            $orderParts[] = $dayNumberCol . ' ASC';
            $orderParts[] = db_column_exists('itinerary_days', 'day_end')
                ? 'COALESCE(' . $dayEndCol . ', ' . $dayNumberCol . ') ASC'
                : $dayNumberCol . ' ASC';
        }
        $orderParts[] = customer_column_select('itinerary_days', 'id') . ' ASC';

        $days = fetch_all(
            "SELECT {$dayNumberCol}, {$dayEndCol}, {$titleCol}, {$summaryCol}, {$overnightCol}, {$travelTimeCol}, {$imageCol}
             FROM itinerary_days
             WHERE itinerary_id = :itinerary_id
             ORDER BY " . implode(', ', $orderParts),
            ['itinerary_id' => $sourceItineraryId]
        );
    }

    if (!$days) {
        $days = [[
            'id' => null,
            'day_number' => 1,
            'day_end' => 1,
            'title' => 'Custom planning in progress',
            'summary' => 'Our team will shape your day-by-day route after reviewing your inquiry.',
            'overnight_location' => null,
            'travel_time' => null,
            'image_path' => null,
        ]];
    }

    foreach ($days as $index => $day) {
        execute_query(
            "INSERT INTO customer_itinerary_days
                (plan_id, source_itinerary_day_id, day_number, day_end, title, summary, overnight_location, travel_time, image_path, sort_order)
             VALUES
                (:plan_id, :source_itinerary_day_id, :day_number, :day_end, :title, :summary, :overnight_location, :travel_time, :image_path, :sort_order)",
            [
                'plan_id' => $planId,
                'source_itinerary_day_id' => !empty($day['id']) ? (int) $day['id'] : null,
                'day_number' => max(1, (int) ($day['day_number'] ?? 1)),
                'day_end' => max((int) ($day['day_number'] ?? 1), (int) ($day['day_end'] ?? $day['day_number'] ?? 1)),
                'title' => trim((string) ($day['title'] ?? 'Day Plan')) ?: 'Day Plan',
                'summary' => trim((string) ($day['summary'] ?? '')) ?: null,
                'overnight_location' => trim((string) ($day['overnight_location'] ?? '')) ?: null,
                'travel_time' => trim((string) ($day['travel_time'] ?? '')) ?: null,
                'image_path' => trim((string) ($day['image_path'] ?? '')) ?: null,
                'sort_order' => $index + 1,
            ]
        );
    }

    return fetch_one("SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1", ['id' => $planId]);
}

function customer_ensure_plans_for_customer(array $customer): void
{
    ensure_customer_accounts_schema();

    $customerId = (int) ($customer['id'] ?? 0);
    $email = strtolower(trim((string) ($customer['email'] ?? '')));
    if ($customerId <= 0 || $email === '' || !db_table_exists('contact_inquiries')) {
        return;
    }

    $hasCustomerUserId = customer_column_name('contact_inquiries', 'customer_user_id') !== null;
    $spamCol = customer_column_name('contact_inquiries', 'is_spam');
    $ownerClause = $hasCustomerUserId
        ? '(customer_user_id = :customer_user_id OR (customer_user_id IS NULL AND LOWER(email) = :email))'
        : 'LOWER(email) = :email';
    $spamClause = $spamCol !== null ? 'AND ' . $spamCol . ' = 0' : '';
    $params = ['email' => $email];
    if ($hasCustomerUserId) {
        $params['customer_user_id'] = $customerId;
    }

    $inquiries = fetch_all(
        "SELECT id
         FROM contact_inquiries
         WHERE {$ownerClause}
           {$spamClause}
           AND preferred_itinerary_id IS NOT NULL
           AND preferred_itinerary_id > 0
         ORDER BY id DESC",
        $params
    );

    foreach ($inquiries as $inquiry) {
        customer_create_plan_from_inquiry((int) ($inquiry['id'] ?? 0), $customerId);
    }
}

function fetch_customer_plans(array $customer): array
{
    ensure_customer_accounts_schema();
    customer_ensure_plans_for_customer($customer);

    $customerId = (int) ($customer['id'] ?? 0);
    if ($customerId <= 0) {
        return [];
    }

    $canJoinContactInquiries = db_table_exists('contact_inquiries') && db_column_exists('contact_inquiries', 'id');
    $canJoinItineraries = db_table_exists('itineraries') && db_column_exists('itineraries', 'id');
    $inquiryStatusSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'inquiry_status') !== null ? 'ci.inquiry_status' : 'NULL AS inquiry_status';
    $inquiryMessageSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'message') !== null ? 'ci.message AS inquiry_message' : 'NULL AS inquiry_message';
    $inquiryCreatedSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'created_at') !== null ? 'ci.created_at AS inquiry_created_at' : 'NULL AS inquiry_created_at';
    $contactInquiryJoin = $canJoinContactInquiries ? 'LEFT JOIN contact_inquiries ci ON ci.id = p.contact_inquiry_id' : '';
    $itinerarySlugSelect = $canJoinItineraries && customer_column_name('itineraries', 'slug') !== null ? 'i.slug AS source_itinerary_slug' : 'NULL AS source_itinerary_slug';
    $itineraryJoin = $canJoinItineraries ? 'LEFT JOIN itineraries i ON i.id = p.source_itinerary_id' : '';

    return fetch_all(
        "SELECT p.*,
                {$inquiryStatusSelect},
                {$inquiryMessageSelect},
                {$inquiryCreatedSelect},
                {$itinerarySlugSelect},
                COALESCE(day_counts.day_count, 0) AS day_count,
                COALESCE(request_counts.pending_count, 0) AS pending_request_count,
                COALESCE(message_counts.message_count, 0) AS message_count
         FROM customer_itinerary_plans p
         {$contactInquiryJoin}
         {$itineraryJoin}
         LEFT JOIN (
            SELECT plan_id, COUNT(*) AS day_count
            FROM customer_itinerary_days
            GROUP BY plan_id
         ) day_counts ON day_counts.plan_id = p.id
         LEFT JOIN (
            SELECT plan_id, SUM(request_status = 'pending') AS pending_count
            FROM customer_itinerary_change_requests
            GROUP BY plan_id
         ) request_counts ON request_counts.plan_id = p.id
         LEFT JOIN (
            SELECT inquiry_id, COUNT(*) AS message_count
            FROM contact_inquiry_messages
            WHERE is_visible_to_customer = 1
            GROUP BY inquiry_id
         ) message_counts ON message_counts.inquiry_id = p.contact_inquiry_id
         WHERE p.customer_user_id = :customer_user_id
         ORDER BY p.updated_at DESC, p.id DESC",
        ['customer_user_id' => $customerId]
    );
}

function fetch_customer_active_plan(array $customer): ?array
{
    $plans = fetch_customer_plans($customer);
    foreach ($plans as $plan) {
        if (!in_array((string) ($plan['plan_status'] ?? ''), ['completed', 'cancelled'], true)) {
            return $plan;
        }
    }

    return $plans[0] ?? null;
}

function find_customer_plan(int $planId, array $customer): ?array
{
    ensure_customer_accounts_schema();
    customer_ensure_plans_for_customer($customer);

    $customerId = (int) ($customer['id'] ?? 0);
    if ($planId <= 0 || $customerId <= 0) {
        return null;
    }

    $canJoinContactInquiries = db_table_exists('contact_inquiries') && db_column_exists('contact_inquiries', 'id');
    $canJoinItineraries = db_table_exists('itineraries') && db_column_exists('itineraries', 'id');
    $inquiryStatusSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'inquiry_status') !== null ? 'ci.inquiry_status' : 'NULL AS inquiry_status';
    $inquiryMessageSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'message') !== null ? 'ci.message AS inquiry_message' : 'NULL AS inquiry_message';
    $inquiryCreatedSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'created_at') !== null ? 'ci.created_at AS inquiry_created_at' : 'NULL AS inquiry_created_at';
    $emailSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'email') !== null ? 'ci.email' : 'NULL AS email';
    $phoneSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'phone') !== null ? 'ci.phone' : 'NULL AS phone';
    $whatsappSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'whatsapp') !== null ? 'ci.whatsapp' : 'NULL AS whatsapp';
    $countrySelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'country') !== null ? 'ci.country' : 'NULL AS country';
    $interestsSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'interests') !== null ? 'ci.interests' : 'NULL AS interests';
    $accommodationSelect = $canJoinContactInquiries && customer_column_name('contact_inquiries', 'accommodation_preference') !== null ? 'ci.accommodation_preference' : 'NULL AS accommodation_preference';
    $contactInquiryJoin = $canJoinContactInquiries ? 'LEFT JOIN contact_inquiries ci ON ci.id = p.contact_inquiry_id' : '';
    $itinerarySlugSelect = $canJoinItineraries && db_column_exists('itineraries', 'slug') ? 'i.slug AS source_itinerary_slug' : 'NULL AS source_itinerary_slug';
    $itineraryNameSelect = $canJoinItineraries && db_column_exists('itineraries', 'name') ? 'i.name AS source_itinerary_name' : 'NULL AS source_itinerary_name';
    $itineraryJoin = $canJoinItineraries ? 'LEFT JOIN itineraries i ON i.id = p.source_itinerary_id' : '';

    return fetch_one(
        "SELECT p.*, {$inquiryStatusSelect}, {$inquiryMessageSelect}, {$inquiryCreatedSelect},
                {$emailSelect}, {$phoneSelect}, {$whatsappSelect}, {$countrySelect}, {$interestsSelect}, {$accommodationSelect},
                {$itinerarySlugSelect}, {$itineraryNameSelect}
         FROM customer_itinerary_plans p
         {$contactInquiryJoin}
         {$itineraryJoin}
         WHERE p.id = :id
           AND p.customer_user_id = :customer_user_id
         LIMIT 1",
        ['id' => $planId, 'customer_user_id' => $customerId]
    );
}

function fetch_customer_plan_days(int $planId): array
{
    ensure_customer_accounts_schema();

    if ($planId <= 0) {
        return [];
    }

    return fetch_all(
        "SELECT *
         FROM customer_itinerary_days
         WHERE plan_id = :plan_id
         ORDER BY day_number ASC, COALESCE(day_end, day_number) ASC, sort_order ASC, id ASC",
        ['plan_id' => $planId]
    );
}

function fetch_customer_plan_change_requests(int $planId): array
{
    ensure_customer_accounts_schema();

    if ($planId <= 0) {
        return [];
    }

    return fetch_all(
        "SELECT cr.*, d.day_number, d.day_end, d.title AS current_title
         FROM customer_itinerary_change_requests cr
         INNER JOIN customer_itinerary_days d ON d.id = cr.plan_day_id
         WHERE cr.plan_id = :plan_id
         ORDER BY cr.created_at DESC, cr.id DESC",
        ['plan_id' => $planId]
    );
}

function group_customer_change_requests_by_day(array $requests): array
{
    $grouped = [];
    foreach ($requests as $request) {
        $dayId = (int) ($request['plan_day_id'] ?? 0);
        if ($dayId <= 0) {
            continue;
        }
        $grouped[$dayId][] = $request;
    }

    return $grouped;
}

function create_customer_itinerary_change_request(int $planId, int $dayId, array $customer, array $data): int
{
    ensure_customer_accounts_schema();

    $plan = find_customer_plan($planId, $customer);
    if (!$plan) {
        throw new RuntimeException('Plan not found.');
    }

    $day = fetch_one(
        "SELECT * FROM customer_itinerary_days WHERE id = :id AND plan_id = :plan_id LIMIT 1",
        ['id' => $dayId, 'plan_id' => $planId]
    );
    if (!$day) {
        throw new RuntimeException('Plan day not found.');
    }

    $requestedTitle = trim((string) ($data['requested_title'] ?? ''));
    $requestedSummary = trim((string) ($data['requested_summary'] ?? ''));
    $requestedOvernight = trim((string) ($data['requested_overnight_location'] ?? ''));
    $requestedTravelTime = trim((string) ($data['requested_travel_time'] ?? ''));
    $requestReason = trim((string) ($data['request_reason'] ?? ''));

    if ($requestedTitle === '' && $requestedSummary === '' && $requestedOvernight === '' && $requestedTravelTime === '' && $requestReason === '') {
        throw new RuntimeException('Add at least one change or a reason.');
    }

    $summaryLength = function_exists('mb_strlen') ? mb_strlen($requestedSummary) : strlen($requestedSummary);
    if ($summaryLength > CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH) {
        throw new InvalidArgumentException('Suggested activities are too long. Please keep them under ' . CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH . ' characters.');
    }

    $reasonLength = function_exists('mb_strlen') ? mb_strlen($requestReason) : strlen($requestReason);
    if ($reasonLength > CUSTOMER_CHANGE_REASON_MAX_LENGTH) {
        throw new InvalidArgumentException('Change reason is too long. Please keep it under ' . CUSTOMER_CHANGE_REASON_MAX_LENGTH . ' characters.');
    }

    execute_query(
        "INSERT INTO customer_itinerary_change_requests
            (plan_id, plan_day_id, customer_user_id, contact_inquiry_id, requested_title, requested_summary,
             requested_overnight_location, requested_travel_time, request_reason)
         VALUES
            (:plan_id, :plan_day_id, :customer_user_id, :contact_inquiry_id, :requested_title, :requested_summary,
             :requested_overnight_location, :requested_travel_time, :request_reason)",
        [
            'plan_id' => $planId,
            'plan_day_id' => $dayId,
            'customer_user_id' => (int) ($customer['id'] ?? 0),
            'contact_inquiry_id' => (int) ($plan['contact_inquiry_id'] ?? 0),
            'requested_title' => $requestedTitle !== '' ? substr($requestedTitle, 0, 255) : null,
            'requested_summary' => $requestedSummary !== '' ? $requestedSummary : null,
            'requested_overnight_location' => $requestedOvernight !== '' ? substr($requestedOvernight, 0, 180) : null,
            'requested_travel_time' => $requestedTravelTime !== '' ? substr($requestedTravelTime, 0, 120) : null,
            'request_reason' => $requestReason !== '' ? $requestReason : null,
        ]
    );
    $requestId = (int) last_insert_id();

    execute_query(
        "UPDATE customer_itinerary_plans
         SET plan_status = 'reviewing',
             booking_status = CASE
                 WHEN booking_status IN ('awaiting_acceptance', 'accepted') THEN 'planning'
                 ELSE booking_status
             END
         WHERE id = :id",
        ['id' => $planId]
    );

    return $requestId;
}

function fetch_customer_plans_for_inquiries(array $inquiryIds): array
{
    ensure_customer_accounts_schema();

    $ids = array_values(array_unique(array_filter(array_map('intval', $inquiryIds))));
    if (!$ids) {
        return [];
    }

    $rows = fetch_all(
        "SELECT p.*,
                COALESCE(day_counts.day_count, 0) AS day_count,
                COALESCE(request_counts.pending_count, 0) AS pending_request_count
         FROM customer_itinerary_plans p
         LEFT JOIN (
            SELECT plan_id, COUNT(*) AS day_count
            FROM customer_itinerary_days
            GROUP BY plan_id
         ) day_counts ON day_counts.plan_id = p.id
         LEFT JOIN (
            SELECT plan_id, SUM(request_status = 'pending') AS pending_count
            FROM customer_itinerary_change_requests
            GROUP BY plan_id
         ) request_counts ON request_counts.plan_id = p.id
         WHERE p.contact_inquiry_id IN (" . implode(',', $ids) . ")
         ORDER BY p.id DESC"
    );

    $byInquiry = [];
    foreach ($rows as $row) {
        $byInquiry[(int) ($row['contact_inquiry_id'] ?? 0)] = $row;
    }

    return $byInquiry;
}

function fetch_customer_change_requests_for_plans(array $planIds): array
{
    ensure_customer_accounts_schema();

    $ids = array_values(array_unique(array_filter(array_map('intval', $planIds))));
    if (!$ids) {
        return [];
    }

    $rows = fetch_all(
        "SELECT cr.*, d.day_number, d.day_end, d.title AS current_title, d.summary AS current_summary,
                d.overnight_location AS current_overnight_location, d.travel_time AS current_travel_time
         FROM customer_itinerary_change_requests cr
         INNER JOIN customer_itinerary_days d ON d.id = cr.plan_day_id
         WHERE cr.plan_id IN (" . implode(',', $ids) . ")
         ORDER BY FIELD(cr.request_status, 'pending', 'approved', 'applied', 'rejected'), cr.created_at DESC, cr.id DESC"
    );

    $byPlan = [];
    foreach ($rows as $row) {
        $planId = (int) ($row['plan_id'] ?? 0);
        if ($planId > 0) {
            $byPlan[$planId][] = $row;
        }
    }

    return $byPlan;
}

function customer_refresh_plan_review_status(int $planId): void
{
    if ($planId <= 0) {
        return;
    }

    execute_query(
        "UPDATE customer_itinerary_plans
         SET plan_status = CASE
                WHEN EXISTS (
                    SELECT 1 FROM customer_itinerary_change_requests
                    WHERE plan_id = :pending_plan_id AND request_status = 'pending'
                    LIMIT 1
                ) THEN 'reviewing'
                ELSE 'confirmed'
             END,
             last_admin_update_at = NOW()
         WHERE id = :plan_id",
        [
            'pending_plan_id' => $planId,
            'plan_id' => $planId,
        ]
    );
}

function admin_apply_customer_change_request(int $requestId, string $action, int $adminUserId, array $data): array
{
    ensure_customer_accounts_schema();

    $request = fetch_one(
        "SELECT cr.*, d.title AS current_title, d.summary AS current_summary,
                d.overnight_location AS current_overnight_location, d.travel_time AS current_travel_time,
                p.contact_inquiry_id
         FROM customer_itinerary_change_requests cr
         INNER JOIN customer_itinerary_days d ON d.id = cr.plan_day_id
         INNER JOIN customer_itinerary_plans p ON p.id = cr.plan_id
         WHERE cr.id = :id
         LIMIT 1",
        ['id' => $requestId]
    );
    if (!$request) {
        throw new RuntimeException('Change request not found.');
    }

    if ((string) ($request['request_status'] ?? '') !== 'pending') {
        throw new RuntimeException('Only pending requests can be reviewed.');
    }

    $adminResponse = trim((string) ($data['admin_response'] ?? ''));
    $adminResponseLength = function_exists('mb_strlen') ? mb_strlen($adminResponse) : strlen($adminResponse);
    if ($adminResponseLength > CUSTOMER_MESSAGE_MAX_LENGTH) {
        throw new InvalidArgumentException('Admin response is too long. Please keep it under ' . CUSTOMER_MESSAGE_MAX_LENGTH . ' characters.');
    }

    if ($action === 'reject') {
        execute_query(
            "UPDATE customer_itinerary_change_requests
             SET request_status = 'rejected',
                 admin_response = :admin_response,
                 reviewed_by_admin_user_id = :admin_user_id,
                 reviewed_at = NOW()
             WHERE id = :id",
            [
                'admin_response' => $adminResponse !== '' ? $adminResponse : null,
                'admin_user_id' => $adminUserId > 0 ? $adminUserId : null,
                'id' => $requestId,
            ]
        );
        customer_refresh_plan_review_status((int) ($request['plan_id'] ?? 0));
        return array_merge($request, ['review_action' => 'rejected', 'admin_response_final' => $adminResponse]);
    }

    if ($action !== 'apply') {
        throw new RuntimeException('Invalid review action.');
    }

    $finalTitle = trim((string) ($data['final_title'] ?? ($request['requested_title'] ?: $request['current_title'])));
    if ($finalTitle === '') {
        throw new RuntimeException('Final day title is required.');
    }

    $finalSummary = trim((string) ($data['final_summary'] ?? ($request['requested_summary'] ?: $request['current_summary'])));
    $finalOvernight = trim((string) ($data['final_overnight_location'] ?? ($request['requested_overnight_location'] ?: $request['current_overnight_location'])));
    $finalTravelTime = trim((string) ($data['final_travel_time'] ?? ($request['requested_travel_time'] ?: $request['current_travel_time'])));

    $finalTitleLength = function_exists('mb_strlen') ? mb_strlen($finalTitle) : strlen($finalTitle);
    $finalSummaryLength = function_exists('mb_strlen') ? mb_strlen($finalSummary) : strlen($finalSummary);
    $finalOvernightLength = function_exists('mb_strlen') ? mb_strlen($finalOvernight) : strlen($finalOvernight);
    $finalTravelTimeLength = function_exists('mb_strlen') ? mb_strlen($finalTravelTime) : strlen($finalTravelTime);

    if ($finalTitleLength > 255) {
        throw new InvalidArgumentException('Final day title is too long. Please keep it under 255 characters.');
    }
    if ($finalSummaryLength > CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH) {
        throw new InvalidArgumentException('Final day summary is too long. Please keep it under ' . CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH . ' characters.');
    }
    if ($finalOvernightLength > 180) {
        throw new InvalidArgumentException('Final overnight location is too long. Please keep it under 180 characters.');
    }
    if ($finalTravelTimeLength > 120) {
        throw new InvalidArgumentException('Final travel time note is too long. Please keep it under 120 characters.');
    }

    execute_query(
        "UPDATE customer_itinerary_days
         SET title = :title,
             summary = :summary,
             overnight_location = :overnight_location,
             travel_time = :travel_time,
             admin_updated_at = NOW()
         WHERE id = :id
           AND plan_id = :plan_id",
        [
            'title' => $finalTitle,
            'summary' => $finalSummary !== '' ? $finalSummary : null,
            'overnight_location' => $finalOvernight !== '' ? $finalOvernight : null,
            'travel_time' => $finalTravelTime !== '' ? $finalTravelTime : null,
            'id' => (int) $request['plan_day_id'],
            'plan_id' => (int) $request['plan_id'],
        ]
    );

    execute_query(
        "UPDATE customer_itinerary_change_requests
         SET request_status = 'applied',
             admin_response = :admin_response,
             reviewed_by_admin_user_id = :admin_user_id,
             reviewed_at = NOW(),
             applied_at = NOW()
         WHERE id = :id",
        [
            'admin_response' => $adminResponse !== '' ? $adminResponse : null,
            'admin_user_id' => $adminUserId > 0 ? $adminUserId : null,
            'id' => $requestId,
        ]
    );

    customer_refresh_plan_review_status((int) $request['plan_id']);

    return array_merge($request, ['review_action' => 'applied', 'admin_response_final' => $adminResponse]);
}
