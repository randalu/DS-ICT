<?php
declare(strict_types=1);

/**
 * Simple key/value settings store for site-level contact values.
 */
function ensure_site_settings_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value LONGTEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = [
        'contact_phone_primary' => SITE_PHONE,
        'contact_phone_secondary' => SITE_PHONE_SECONDARY,
        'contact_email' => SITE_EMAIL,
        'contact_whatsapp_number' => WHATSAPP_NUMBER,
        'contact_whatsapp_default_text' => DEFAULT_WHATSAPP_TEXT,
    ];

    foreach ($defaults as $key => $value) {
        execute_query(
            "INSERT INTO site_settings (setting_key, setting_value)
             SELECT :setting_key, :setting_value
             WHERE NOT EXISTS (
                 SELECT 1 FROM site_settings WHERE setting_key = :setting_key_exists
             )",
            [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_key_exists' => $key,
            ]
        );
    }

    $ready = true;
}

/**
 * ⚡ Bolt Optimization: Request-wide In-memory Settings Cache
 * Reduces database query complexity from O(N) to O(1) on page requests.
 * Instead of querying the database for every single setting individually,
 * we query all settings once and keep them in $GLOBALS['_site_settings_cache'].
 */
function site_setting_get(string $key, ?string $default = null): ?string
{
    if (!isset($GLOBALS['_site_settings_cache']) || !is_array($GLOBALS['_site_settings_cache'])) {
        $GLOBALS['_site_settings_cache'] = [];
        try {
            ensure_site_settings_schema();
            $rows = fetch_all("SELECT setting_key, setting_value FROM site_settings");
            foreach ($rows as $row) {
                if (isset($row['setting_key'])) {
                    $GLOBALS['_site_settings_cache'][$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Throwable) {
            // Safe fallback: leave cache empty to prevent repeating failed schema queries, but return default
        }
    }

    if (array_key_exists($key, $GLOBALS['_site_settings_cache'])) {
        return (string) ($GLOBALS['_site_settings_cache'][$key] ?? $default);
    }

    return $default;
}

/**
 * Updates a site setting in the database and synchronizes the in-memory cache.
 */
function site_setting_set(string $key, string $value): void
{
    // Always update the in-memory cache first to guarantee instant availability
    if (isset($GLOBALS['_site_settings_cache']) && is_array($GLOBALS['_site_settings_cache'])) {
        $GLOBALS['_site_settings_cache'][$key] = $value;
    }

    ensure_site_settings_schema();
    execute_query(
        "INSERT INTO site_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [
            'setting_key' => $key,
            'setting_value' => $value,
        ]
    );
}

function site_under_construction_default_message(): string
{
    return 'We are updating this website with final Sri Lanka travel photos. All pages remain available for review, and trip inquiries are still open.';
}

function site_under_construction_notice_enabled(): bool
{
    return site_setting_get('site_under_construction_notice_enabled', '0') === '1';
}

function site_under_construction_notice_message(): string
{
    $message = trim((string) site_setting_get(
        'site_under_construction_notice_message',
        site_under_construction_default_message()
    ));

    return $message !== '' ? $message : site_under_construction_default_message();
}

function homepage_section_defaults(): array
{
    return [
        'trust' => ['label' => 'Trust Strip', 'enabled' => true, 'sort_order' => 10],
        'itineraries' => ['label' => 'Featured Itineraries', 'enabled' => true, 'sort_order' => 20],
        'experiences' => ['label' => 'Experiences', 'enabled' => true, 'sort_order' => 30],
        'day-tours' => ['label' => 'Day Tours', 'enabled' => true, 'sort_order' => 35],
        'destinations' => ['label' => 'Destinations', 'enabled' => true, 'sort_order' => 40],
        'categories' => ['label' => 'Tour Categories', 'enabled' => false, 'sort_order' => 45],
        'approach' => ['label' => 'Why Travel With Us', 'enabled' => false, 'sort_order' => 50],
        'reviews' => ['label' => 'Reviews', 'enabled' => true, 'sort_order' => 60],
        'cta' => ['label' => 'CTA Banner', 'enabled' => true, 'sort_order' => 70],
    ];
}

function homepage_default_settings(): array
{
    return [
        'homepage_new_seo_title' => 'Sri Lanka Private Tours & Private Driver | Island Culture Travels',
        'homepage_new_seo_description' => 'Plan a private Sri Lanka tour with your own driver-guide, flexible routes, cultural sites, wildlife, beaches, and practical support from arrival to departure.',
        'homepage_new_og_title' => 'Sri Lanka Private Tours & Private Driver | Island Culture Travels',
        'homepage_new_og_description' => 'Explore private Sri Lanka tours, chauffeur-guided travel, custom itineraries, and practical route ideas for couples, families, wildlife, beaches, and culture.',
        'homepage_new_og_image' => '',
        'homepage_new_cta_heading' => 'Ready to Plan Your Sri Lanka Journey?',
        'homepage_new_cta_body' => 'Let our travel experts create the perfect itinerary for your unforgettable trip.',
        'homepage_new_cta_button_label' => 'Plan My Trip',
        'homepage_new_cta_button_url' => 'contact',
        'homepage_new_cta_image' => 'assets/images/home-hero/home-5.jpg',
        'homepage_new_pinned_hero_id' => '',
        'homepage_new_approach_default_hidden_applied' => '0',
    ];
}

function ensure_homepage_manager_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    ensure_site_settings_schema();
    ensure_social_marketing_schema();

    db()->exec("
        CREATE TABLE IF NOT EXISTS homepage_hero_media (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            media_asset_id BIGINT UNSIGNED NULL,
            media_type VARCHAR(20) NOT NULL DEFAULT 'image',
            file_path VARCHAR(255) NOT NULL,
            poster_path VARCHAR(255) NULL,
            headline_script VARCHAR(180) NULL,
            headline_main VARCHAR(255) NULL,
            supporting_text TEXT NULL,
            primary_cta_label VARCHAR(120) NULL,
            primary_cta_url VARCHAR(255) NULL,
            secondary_cta_label VARCHAR(120) NULL,
            secondary_cta_url VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_homepage_hero_active (is_active, sort_order, id),
            KEY idx_homepage_hero_asset (media_asset_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = homepage_default_settings();
    $defaults['homepage_new_layout_json'] = json_encode(homepage_section_defaults(), JSON_UNESCAPED_SLASHES);
    foreach ($defaults as $key => $value) {
        execute_query(
            "INSERT INTO site_settings (setting_key, setting_value)
             SELECT :setting_key, :setting_value
             WHERE NOT EXISTS (
                 SELECT 1 FROM site_settings WHERE setting_key = :setting_key_exists
             )",
            [
                'setting_key' => $key,
                'setting_value' => (string) $value,
                'setting_key_exists' => $key,
            ]
        );
    }

    if (site_setting_get('homepage_new_approach_default_hidden_applied', '0') !== '1') {
        $layoutRaw = site_setting_get('homepage_new_layout_json', '');
        $layout = json_decode((string) $layoutRaw, true);
        if (is_array($layout) && isset($layout['approach']) && is_array($layout['approach'])) {
            $layout['approach']['enabled'] = false;
            site_setting_set('homepage_new_layout_json', (string) json_encode($layout, JSON_UNESCAPED_SLASHES));
        }
        site_setting_set('homepage_new_approach_default_hidden_applied', '1');
    }

    $ready = true;
}

function homepage_new_settings(): array
{
    ensure_homepage_manager_schema();
    $settings = [];
    foreach (homepage_default_settings() as $key => $default) {
        $settings[$key] = (string) site_setting_get($key, (string) $default);
    }

    return $settings;
}

function homepage_new_section_config(): array
{
    ensure_homepage_manager_schema();
    $defaults = homepage_section_defaults();
    $raw = site_setting_get('homepage_new_layout_json', '');
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        $decoded = [];
    }

    $config = [];
    foreach ($defaults as $key => $default) {
        $row = isset($decoded[$key]) && is_array($decoded[$key]) ? $decoded[$key] : [];
        $config[$key] = [
            'label' => $default['label'],
            'enabled' => array_key_exists('enabled', $row) ? (bool) $row['enabled'] : (bool) $default['enabled'],
            'sort_order' => isset($row['sort_order']) ? (int) $row['sort_order'] : (int) $default['sort_order'],
        ];
    }

    uasort($config, static function (array $a, array $b): int {
        return ((int) $a['sort_order']) <=> ((int) $b['sort_order']);
    });

    return $config;
}

function homepage_new_active_sections(): array
{
    return array_keys(array_filter(
        homepage_new_section_config(),
        static fn(array $section): bool => !empty($section['enabled'])
    ));
}

function homepage_new_hero_rows(bool $activeOnly = false): array
{
    ensure_homepage_manager_schema();
    $where = $activeOnly ? 'WHERE h.is_active = 1' : '';

    return fetch_all(
        "SELECT h.*, m.file_name, m.mime_type, m.file_size
         FROM homepage_hero_media h
         LEFT JOIN media_assets m ON m.id = h.media_asset_id
         {$where}
         ORDER BY h.sort_order ASC, h.id ASC"
    );
}

function homepage_new_default_hero(): array
{
    return [
        'id' => 0,
        'media_type' => 'image',
        'file_path' => 'assets/images/galle-lighthouse.jpg',
        'poster_path' => '',
        'headline_script' => 'The Beauty of Sri Lanka',
        'headline_main' => 'Private Sri Lanka Tours with Your Own Driver-Guide',
        'supporting_text' => 'Explore private Sri Lanka tours and chauffeur-guided travel for couples, families, wildlife, beaches, and culture—with a flexible itinerary shaped around your dates and pace.',
        'primary_cta_label' => 'Explore Itineraries',
        'primary_cta_url' => 'itineraries',
        'secondary_cta_label' => 'Plan Your Trip',
        'secondary_cta_url' => 'contact',
    ];
}

function homepage_new_active_hero(): array
{
    $rows = homepage_new_hero_rows(true);
    if (!$rows) {
        return homepage_new_default_hero();
    }

    $pinnedHeroId = (int) site_setting_get('homepage_new_pinned_hero_id', '0');
    if ($pinnedHeroId > 0) {
        foreach ($rows as $row) {
            if ((int) ($row['id'] ?? 0) === $pinnedHeroId) {
                return array_merge(homepage_new_default_hero(), $row);
            }
        }
    }

    $dayIndex = (int) floor(strtotime(date('Y-m-d')) / 86400);
    $selected = $rows[$dayIndex % count($rows)];

    return array_merge(homepage_new_default_hero(), $selected);
}

function business_partners_public_enabled(): bool
{
    return site_setting_get('business_partners_public_enabled', '1') === '1';
}

/**
 * Footer links: admin-managed link groups stored as JSON in site_settings.
 */
function ensure_footer_links_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    ensure_site_settings_schema();

    if (site_setting_get('footer_bottom_links', '') === '') {
        site_setting_set('footer_bottom_links', json_encode([
            ['label' => 'FAQ', 'url' => 'faq'],
            ['label' => 'When to Visit', 'url' => 'seasonal'],
            ['label' => 'Trip Planner', 'url' => 'plan'],
            ['label' => 'Privacy Policy', 'url' => 'privacy-policy'],
            ['label' => 'Account', 'url' => 'account'],
            ['label' => 'Reviews', 'url' => 'reviews'],
            ['label' => 'Partners', 'url' => 'business-partners'],
            ['label' => 'Contact', 'url' => 'contact'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
    }

    if (site_setting_get('footer_service_links', '') === '') {
        site_setting_set('footer_service_links', json_encode([
            ['label' => 'When to Visit', 'url' => 'seasonal'],
            ['label' => 'Trip Planner', 'url' => 'plan'],
            ['label' => 'Why Choose Us', 'url' => 'why-choose-us'],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
    }

    $ready = true;
}

function fetch_footer_bottom_links(): array
{
    ensure_footer_links_schema();
    $raw = site_setting_get('footer_bottom_links', '[]');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function fetch_footer_service_links(): array
{
    ensure_footer_links_schema();
    $raw = site_setting_get('footer_service_links', '[]');
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function footer_bottom_links_text(): string
{
    $lines = [];
    foreach (fetch_footer_bottom_links() as $link) {
        $label = trim((string) ($link['label'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        if ($label !== '' && $url !== '') {
            $lines[] = $url . ' | ' . $label;
        }
    }
    return implode("\n", $lines);
}

function footer_service_links_text(): string
{
    $lines = [];
    foreach (fetch_footer_service_links() as $link) {
        $label = trim((string) ($link['label'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        if ($label !== '' && $url !== '') {
            $lines[] = $url . ' | ' . $label;
        }
    }
    return implode("\n", $lines);
}

function parse_footer_links_text(string $text): array
{
    $links = [];
    foreach (preg_split('/\\r?\\n+/', trim($text)) ?: [] as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        $url = trim((string) ($parts[0] ?? ''));
        $label = trim((string) ($parts[1] ?? ''));
        if ($url === '' || $label === '') {
            continue;
        }
        $links[] = ['label' => $label, 'url' => $url];
        if (count($links) >= 30) {
            break;
        }
    }
    return $links;
}

/**
 * Seasonal guides: admin-managed content for /sri-lanka-in-{month} pages.
 */
function seasonal_allowed_months(): array
{
    return [
        'january', 'february', 'march', 'april', 'may', 'june',
        'july', 'august', 'september', 'october', 'november', 'december',
    ];
}

function ensure_seasonal_guides_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS seasonal_guides (
            month_key VARCHAR(20) PRIMARY KEY,
            title VARCHAR(180) NULL,
            intro TEXT NULL,
            highlights_json LONGTEXT NULL,
            best_regions_json LONGTEXT NULL,
            avoid_regions_json LONGTEXT NULL,
            meta_title VARCHAR(180) NULL,
            meta_description VARCHAR(300) NULL,
            hero_image VARCHAR(255) NULL,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Seed 12 months from data/month_weather.php if the rows don't exist yet.
    $existing = [];
    foreach (fetch_all("SELECT month_key FROM seasonal_guides") as $row) {
        $k = strtolower(trim((string) ($row['month_key'] ?? '')));
        if ($k !== '') {
            $existing[$k] = true;
        }
    }

    $seedFile = __DIR__ . '/../data/month_weather.php';
    /** @var array<string, array> $seed */
    $seed = is_file($seedFile) ? (require $seedFile) : [];

    foreach (seasonal_allowed_months() as $month) {
        if (isset($existing[$month])) {
            continue;
        }

        $info = isset($seed[$month]) && is_array($seed[$month]) ? $seed[$month] : [];
        $title = trim((string) ($info['title'] ?? 'Sri Lanka in ' . ucfirst($month)));
        $intro = trim((string) ($info['intro'] ?? 'Plan the best routes and regions for this month in Sri Lanka.'));
        $highlights = isset($info['highlights']) && is_array($info['highlights']) ? array_values($info['highlights']) : [];
        $bestRegions = isset($info['best_regions']) && is_array($info['best_regions']) ? array_values($info['best_regions']) : [];
        $avoidRegions = isset($info['avoid_regions']) && is_array($info['avoid_regions']) ? array_values($info['avoid_regions']) : [];

        execute_query(
            "INSERT INTO seasonal_guides
            (month_key, title, intro, highlights_json, best_regions_json, avoid_regions_json, is_published)
            VALUES
            (:month_key, :title, :intro, :highlights_json, :best_regions_json, :avoid_regions_json, 1)",
            [
                'month_key' => $month,
                'title' => $title,
                'intro' => $intro,
                'highlights_json' => $highlights ? json_encode($highlights, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'best_regions_json' => $bestRegions ? json_encode($bestRegions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'avoid_regions_json' => $avoidRegions ? json_encode($avoidRegions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }

    $ready = true;
}

function fetch_seasonal_guide(string $month, bool $publishedOnly = true): ?array
{
    $month = strtolower(trim($month));
    if ($month === '') {
        return null;
    }

    ensure_seasonal_guides_schema();

    $where = "month_key = :month_key";
    if ($publishedOnly) {
        $where .= " AND is_published = 1";
    }

    return fetch_one("SELECT * FROM seasonal_guides WHERE {$where} LIMIT 1", ['month_key' => $month]) ?: null;
}

function fetch_all_seasonal_guides(bool $publishedOnly = true): array
{
    ensure_seasonal_guides_schema();
    $where = $publishedOnly ? 'WHERE is_published = 1' : '';
    return fetch_all("SELECT * FROM seasonal_guides {$where} ORDER BY FIELD(month_key,
        'january','february','march','april','may','june','july','august','september','october','november','december'
    )");
}

/**
 * Destination weather: Google Weather API cache + admin-managed monthly climate notes.
 */
function ensure_destination_weather_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS weather_api_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value LONGTEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaults = [
        'weather_api_enabled' => '0',
        'weather_api_key' => '',
        'weather_units_system' => 'METRIC',
        'weather_language_code' => 'en',
        'weather_current_cache_minutes' => '60',
        'weather_forecast_cache_minutes' => '360',
        'weather_timeout_seconds' => '8',
    ];

    foreach ($defaults as $key => $value) {
        execute_query(
            "INSERT INTO weather_api_settings (setting_key, setting_value)
             SELECT :setting_key, :setting_value
             WHERE NOT EXISTS (
                 SELECT 1 FROM weather_api_settings WHERE setting_key = :setting_key_exists
             )",
            [
                'setting_key' => $key,
                'setting_value' => $value,
                'setting_key_exists' => $key,
            ]
        );
    }

    if (db_table_exists('destinations')) {
        $destinationColumns = [
            'weather_enabled' => 'ADD COLUMN weather_enabled TINYINT(1) NOT NULL DEFAULT 0',
            'weather_latitude' => 'ADD COLUMN weather_latitude DECIMAL(10,7) NULL',
            'weather_longitude' => 'ADD COLUMN weather_longitude DECIMAL(10,7) NULL',
            'weather_location_label' => 'ADD COLUMN weather_location_label VARCHAR(180) NULL',
            'weather_admin_note' => 'ADD COLUMN weather_admin_note TEXT NULL',
        ];
        $alterParts = [];
        foreach ($destinationColumns as $column => $definition) {
            if (!db_column_exists('destinations', $column)) {
                $alterParts[] = $definition;
            }
        }
        if ($alterParts) {
            try {
                db()->exec('ALTER TABLE destinations ' . implode(', ', $alterParts));
            } catch (Throwable $e) {
                if (!str_contains(strtolower($e->getMessage()), 'duplicate column')) {
                    throw $e;
                }
            }
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS destination_weather_cache (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            destination_id BIGINT UNSIGNED NOT NULL,
            cache_type VARCHAR(40) NOT NULL,
            request_hash VARCHAR(64) NOT NULL,
            payload_json LONGTEXT NULL,
            http_status INT NULL,
            error_message VARCHAR(500) NULL,
            fetched_at DATETIME NULL,
            expires_at DATETIME NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_destination_weather_cache (destination_id, cache_type, request_hash),
            KEY idx_destination_weather_cache_expiry (destination_id, cache_type, expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS destination_weather_monthly_notes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            destination_id BIGINT UNSIGNED NOT NULL,
            month_key VARCHAR(20) NOT NULL,
            climate_summary TEXT NULL,
            temperature_note VARCHAR(255) NULL,
            rain_note VARCHAR(255) NULL,
            sea_note VARCHAR(255) NULL,
            best_for VARCHAR(255) NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_destination_weather_month (destination_id, month_key),
            KEY idx_destination_weather_month_destination (destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ready = true;
}

function destination_weather_setting_get(string $key, ?string $default = null): ?string
{
    ensure_destination_weather_schema();
    $row = fetch_one(
        "SELECT setting_value FROM weather_api_settings WHERE setting_key = :setting_key LIMIT 1",
        ['setting_key' => $key]
    );

    return $row ? (string) ($row['setting_value'] ?? $default) : $default;
}

function destination_weather_setting_set(string $key, string $value): void
{
    ensure_destination_weather_schema();
    execute_query(
        "INSERT INTO weather_api_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        ['setting_key' => $key, 'setting_value' => $value]
    );
}

function destination_weather_settings(): array
{
    ensure_destination_weather_schema();

    $settings = [];
    foreach (fetch_all("SELECT setting_key, setting_value FROM weather_api_settings") as $row) {
        $settings[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
    }

    $settings['weather_api_enabled'] = ($settings['weather_api_enabled'] ?? '0') === '1' ? '1' : '0';
    $settings['weather_units_system'] = strtoupper($settings['weather_units_system'] ?? 'METRIC');
    if (!in_array($settings['weather_units_system'], ['METRIC', 'IMPERIAL'], true)) {
        $settings['weather_units_system'] = 'METRIC';
    }
    $settings['weather_language_code'] = preg_replace('/[^a-zA-Z-]/', '', $settings['weather_language_code'] ?? 'en') ?: 'en';
    $settings['weather_current_cache_minutes'] = (string) max(10, min(1440, (int) ($settings['weather_current_cache_minutes'] ?? 60)));
    $settings['weather_forecast_cache_minutes'] = (string) max(60, min(2880, (int) ($settings['weather_forecast_cache_minutes'] ?? 360)));
    $settings['weather_timeout_seconds'] = (string) max(3, min(20, (int) ($settings['weather_timeout_seconds'] ?? 8)));

    return $settings;
}

function destination_weather_month_labels(): array
{
    return [
        'january' => 'January',
        'february' => 'February',
        'march' => 'March',
        'april' => 'April',
        'may' => 'May',
        'june' => 'June',
        'july' => 'July',
        'august' => 'August',
        'september' => 'September',
        'october' => 'October',
        'november' => 'November',
        'december' => 'December',
    ];
}

function destination_weather_is_configured(array $destination): bool
{
    return (int) ($destination['weather_enabled'] ?? 0) === 1
        && is_numeric($destination['weather_latitude'] ?? null)
        && is_numeric($destination['weather_longitude'] ?? null);
}

function destination_weather_request_hash(string $cacheType, array $destination, array $params): string
{
    return sha1(json_encode([
        'type' => $cacheType,
        'lat' => round((float) ($destination['weather_latitude'] ?? 0), 5),
        'lng' => round((float) ($destination['weather_longitude'] ?? 0), 5),
        'params' => $params,
    ], JSON_UNESCAPED_SLASHES));
}

function destination_weather_api_lookup(array $destination, string $endpoint, array $params = []): array
{
    $settings = destination_weather_settings();
    $apiKey = trim((string) ($settings['weather_api_key'] ?? ''));
    if ($apiKey === '') {
        return ['ok' => false, 'status' => 0, 'data' => null, 'error' => 'Google Weather API key is not configured.'];
    }

    $params = array_merge([
        'key' => $apiKey,
        'location.latitude' => (string) $destination['weather_latitude'],
        'location.longitude' => (string) $destination['weather_longitude'],
        'unitsSystem' => (string) $settings['weather_units_system'],
        'languageCode' => (string) $settings['weather_language_code'],
    ], $params);

    $url = 'https://weather.googleapis.com/v1/' . ltrim($endpoint, '/') . '?' . http_build_query($params);
    $timeout = (int) $settings['weather_timeout_seconds'];
    $body = false;
    $status = 0;
    $error = '';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $error = curl_error($ch) ?: 'Weather API request failed.';
        }
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'timeout' => $timeout,
                'header' => "Accept: application/json\r\n",
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('~^HTTP/\S+\s+(\d+)~', $header, $m)) {
                    $status = (int) $m[1];
                    break;
                }
            }
        }
        if ($body === false) {
            $error = 'Weather API request failed.';
        }
    }

    if ($body === false || $body === '') {
        return ['ok' => false, 'status' => $status, 'data' => null, 'error' => destination_weather_safe_error_message($status, $error ?: 'Empty Weather API response.')];
    }

    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded)) {
        return ['ok' => false, 'status' => $status, 'data' => null, 'error' => 'Weather API returned invalid JSON.'];
    }

    if ($status < 200 || $status >= 300) {
        return ['ok' => false, 'status' => $status, 'data' => $decoded, 'error' => destination_weather_safe_error_message($status)];
    }

    return ['ok' => true, 'status' => $status, 'data' => $decoded, 'error' => ''];
}

function destination_weather_safe_error_message(int $status = 0, string $error = ''): string
{
    $error = trim($error);
    $errorLower = strtolower($error);

    if (str_contains($errorLower, 'key is not configured')) {
        return 'Google Weather API key is not configured.';
    }

    if (str_contains($errorLower, 'weather api is disabled')) {
        return 'Weather API is disabled.';
    }

    if (str_contains($errorLower, 'location is not configured')) {
        return 'Weather location is not configured.';
    }

    if (str_contains($errorLower, 'invalid json')) {
        return 'Weather API returned invalid JSON.';
    }

    if (str_contains($errorLower, 'empty')) {
        return 'Weather API returned an empty response.';
    }

    if (in_array($status, [401, 403], true)) {
        return 'Weather API authorization failed. Check the saved API key and API access.';
    }

    if ($status === 400) {
        return 'Weather API rejected the request. Check destination coordinates and weather settings.';
    }

    if ($status === 404) {
        return 'Weather API endpoint was not found.';
    }

    if ($status === 429) {
        return 'Weather API rate limit was reached. Try again later.';
    }

    if ($status >= 500) {
        return 'Weather API service is temporarily unavailable.';
    }

    if ($status > 0) {
        return 'Weather API request failed with HTTP status ' . $status . '.';
    }

    if ($error !== '') {
        return 'Weather API request failed before a response was received.';
    }

    return 'Weather API request failed.';
}

function destination_weather_cache_row(int $destinationId, string $cacheType, string $requestHash): ?array
{
    ensure_destination_weather_schema();
    return fetch_one(
        "SELECT * FROM destination_weather_cache
         WHERE destination_id = :destination_id
           AND cache_type = :cache_type
           AND request_hash = :request_hash
         LIMIT 1",
        [
            'destination_id' => $destinationId,
            'cache_type' => $cacheType,
            'request_hash' => $requestHash,
        ]
    );
}

function destination_weather_cache_store(int $destinationId, string $cacheType, string $requestHash, ?array $payload, int $httpStatus, string $errorMessage, int $ttlMinutes): void
{
    $payloadJson = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    execute_query(
        "INSERT INTO destination_weather_cache
            (destination_id, cache_type, request_hash, payload_json, http_status, error_message, fetched_at, expires_at)
         VALUES
            (:destination_id, :cache_type, :request_hash, :payload_json, :http_status, :error_message, :fetched_at, :expires_at)
         ON DUPLICATE KEY UPDATE
            payload_json = VALUES(payload_json),
            http_status = VALUES(http_status),
            error_message = VALUES(error_message),
            fetched_at = VALUES(fetched_at),
            expires_at = VALUES(expires_at)",
        [
            'destination_id' => $destinationId,
            'cache_type' => $cacheType,
            'request_hash' => $requestHash,
            'payload_json' => $payloadJson,
            'http_status' => $httpStatus ?: null,
            'error_message' => $errorMessage !== '' ? substr($errorMessage, 0, 500) : null,
            'fetched_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', time() + ($ttlMinutes * 60)),
        ]
    );
}

function destination_weather_cache_mark_error(int $destinationId, string $cacheType, string $requestHash, int $httpStatus, string $errorMessage): void
{
    execute_query(
        "INSERT INTO destination_weather_cache
            (destination_id, cache_type, request_hash, payload_json, http_status, error_message, fetched_at, expires_at)
         VALUES
            (:destination_id, :cache_type, :request_hash, NULL, :http_status, :error_message, :fetched_at, :expires_at)
         ON DUPLICATE KEY UPDATE
            http_status = VALUES(http_status),
            error_message = VALUES(error_message),
            fetched_at = VALUES(fetched_at)",
        [
            'destination_id' => $destinationId,
            'cache_type' => $cacheType,
            'request_hash' => $requestHash,
            'http_status' => $httpStatus ?: null,
            'error_message' => substr($errorMessage, 0, 500),
            'fetched_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s'),
        ]
    );
}

function destination_weather_fetch_payload(array $destination, string $cacheType, bool $forceRefresh = false): array
{
    ensure_destination_weather_schema();
    $destinationId = (int) ($destination['id'] ?? 0);
    if ($destinationId <= 0 || !destination_weather_is_configured($destination)) {
        return ['available' => false, 'payload' => null, 'stale' => false, 'error' => 'Weather location is not configured.'];
    }

    $settings = destination_weather_settings();
    $apiEnabled = ($settings['weather_api_enabled'] ?? '0') === '1';

    if ($cacheType === 'current') {
        $endpoint = 'currentConditions:lookup';
        $params = [];
        $ttlMinutes = (int) $settings['weather_current_cache_minutes'];
    } else {
        $endpoint = 'forecast/days:lookup';
        $params = ['days' => '7', 'pageSize' => '7'];
        $ttlMinutes = (int) $settings['weather_forecast_cache_minutes'];
        $cacheType = 'forecast_7_day';
    }

    $requestHash = destination_weather_request_hash($cacheType, $destination, $params);
    $row = destination_weather_cache_row($destinationId, $cacheType, $requestHash);
    $payload = null;
    if ($row && !empty($row['payload_json'])) {
        $decoded = json_decode((string) $row['payload_json'], true);
        $payload = is_array($decoded) ? $decoded : null;
    }

    $fresh = $row && !empty($row['expires_at']) && strtotime((string) $row['expires_at']) > time();
    if (!$forceRefresh && $fresh && $payload !== null) {
        return ['available' => true, 'payload' => $payload, 'stale' => false, 'error' => ''];
    }

    if (!$apiEnabled) {
        return [
            'available' => $payload !== null,
            'payload' => $payload,
            'stale' => $payload !== null,
            'error' => 'Weather API is disabled.',
        ];
    }

    $result = destination_weather_api_lookup($destination, $endpoint, $params);
    if (!empty($result['ok']) && is_array($result['data'])) {
        destination_weather_cache_store($destinationId, $cacheType, $requestHash, $result['data'], (int) $result['status'], '', $ttlMinutes);
        return ['available' => true, 'payload' => $result['data'], 'stale' => false, 'error' => ''];
    }

    $error = destination_weather_safe_error_message((int) ($result['status'] ?? 0), (string) ($result['error'] ?? 'Weather API request failed.'));
    destination_weather_cache_mark_error($destinationId, $cacheType, $requestHash, (int) ($result['status'] ?? 0), $error);

    return [
        'available' => $payload !== null,
        'payload' => $payload,
        'stale' => $payload !== null,
        'error' => $error,
    ];
}

function destination_weather_monthly_notes(int $destinationId): array
{
    ensure_destination_weather_schema();
    $customRows = [];
    foreach (fetch_all(
        "SELECT * FROM destination_weather_monthly_notes WHERE destination_id = :destination_id",
        ['destination_id' => $destinationId]
    ) as $row) {
        $customRows[strtolower((string) $row['month_key'])] = $row;
    }

    $notes = [];
    foreach (destination_weather_month_labels() as $monthKey => $monthLabel) {
        $row = $customRows[$monthKey] ?? [];
        $guide = fetch_seasonal_guide($monthKey, true);
        $highlights = decode_string_list_json($guide['highlights_json'] ?? null);
        $fallbackSummary = trim((string) ($guide['intro'] ?? ''));
        $fallbackBestFor = $highlights ? implode(' ', array_slice($highlights, 0, 2)) : '';

        $notes[$monthKey] = [
            'month_key' => $monthKey,
            'label' => $monthLabel,
            'summary' => trim((string) ($row['climate_summary'] ?? '')) ?: $fallbackSummary,
            'temperature_note' => trim((string) ($row['temperature_note'] ?? '')),
            'rain_note' => trim((string) ($row['rain_note'] ?? '')),
            'sea_note' => trim((string) ($row['sea_note'] ?? '')),
            'best_for' => trim((string) ($row['best_for'] ?? '')) ?: $fallbackBestFor,
            'guide_url' => base_url('sri-lanka-in-' . $monthKey),
            'has_custom' => !empty($row),
        ];
    }

    return $notes;
}

function destination_weather_public_bundle(array $destination): array
{
    $current = destination_weather_fetch_payload($destination, 'current');
    $forecast = destination_weather_fetch_payload($destination, 'forecast_7_day');

    return [
        'configured' => destination_weather_is_configured($destination),
        'location_label' => trim((string) ($destination['weather_location_label'] ?? '')) ?: (string) ($destination['name'] ?? 'this destination'),
        'current' => $current,
        'forecast' => $forecast,
        'monthly' => destination_weather_monthly_notes((int) ($destination['id'] ?? 0)),
    ];
}

function destination_weather_admin_rows(): array
{
    ensure_destination_weather_schema();
    if (!db_table_exists('destinations')) {
        return [];
    }

    $hasName = db_column_exists('destinations', 'name');
    $hasSlug = db_column_exists('destinations', 'slug');
    $hasSortOrder = db_column_exists('destinations', 'sort_order');

    $nameSelect = $hasName ? 'd.name' : "CONCAT('Destination #', d.id)";
    $slugSelect = $hasSlug ? 'd.slug' : "''";
    $groupParts = [
        'd.id',
        'd.weather_enabled',
        'd.weather_latitude',
        'd.weather_longitude',
        'd.weather_location_label',
        'd.weather_admin_note',
    ];
    if ($hasName) {
        $groupParts[] = 'd.name';
    }
    if ($hasSlug) {
        $groupParts[] = 'd.slug';
    }

    $orderParts = [];
    if ($hasSortOrder) {
        $orderParts[] = 'd.sort_order ASC';
    }
    if ($hasName) {
        $orderParts[] = 'd.name ASC';
    }
    $orderParts[] = 'd.id ASC';

    $rows = fetch_all("
        SELECT
            d.id, {$nameSelect} AS name, {$slugSelect} AS slug, d.weather_enabled, d.weather_latitude, d.weather_longitude,
            d.weather_location_label, d.weather_admin_note,
            MAX(c.fetched_at) AS last_weather_fetch,
            MAX(c.expires_at) AS next_weather_expiry,
            MAX(c.http_status) AS last_weather_status,
            MAX(c.error_message) AS last_weather_error
        FROM destinations d
        LEFT JOIN destination_weather_cache c ON c.destination_id = d.id
        GROUP BY " . implode(', ', $groupParts) . "
        ORDER BY " . implode(', ', $orderParts) . "
    ");

    foreach ($rows as &$row) {
        if (!empty($row['last_weather_error'])) {
            $row['last_weather_error'] = destination_weather_safe_error_message(
                (int) ($row['last_weather_status'] ?? 0),
                (string) $row['last_weather_error']
            );
        }
    }
    unset($row);

    return $rows;
}

function destination_weather_temperature(?array $node): string
{
    if (!$node || !isset($node['degrees'])) {
        return '';
    }

    $degrees = round((float) $node['degrees']);
    $unit = strtoupper((string) ($node['unit'] ?? 'CELSIUS'));
    $suffix = $unit === 'FAHRENHEIT' ? 'F' : 'C';

    return $degrees . '&deg;' . $suffix;
}

function destination_weather_condition_text(?array $node): string
{
    return trim((string) ($node['weatherCondition']['description']['text'] ?? ''));
}

function destination_weather_icon_url(?array $node): string
{
    $base = trim((string) ($node['weatherCondition']['iconBaseUri'] ?? ''));
    return $base !== '' ? $base . '.svg' : '';
}

/**
 * Trip planner settings (stored in site_settings).
 */
function trip_planner_days_options(): array
{
    $raw = trim((string) site_setting_get('trip_planner_days_csv', '3,4,5,6,7,8,9,10,12,14'));
    $items = preg_split('/\\s*,\\s*/', $raw) ?: [];
    $out = [];
    foreach ($items as $value) {
        $n = (int) trim((string) $value);
        if ($n >= 1 && $n <= 30) {
            $out[$n] = $n;
        }
    }
    $out = array_values($out);
    sort($out);
    return $out ?: [3, 4, 5, 6, 7, 8, 9, 10, 12, 14];
}

function trip_planner_theme_options(): array
{
    $raw = trim((string) site_setting_get('trip_planner_themes_json', ''));
    if ($raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $out = [];
            foreach ($decoded as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $slug = strtolower(trim((string) ($row['slug'] ?? '')));
                $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?: '';
                $label = trim((string) ($row['label'] ?? ''));
                if ($slug === '' || $label === '') {
                    continue;
                }
                $out[] = ['slug' => $slug, 'label' => $label];
            }
            if ($out) {
                return $out;
            }
        }
    }

    return [
        ['slug' => 'honeymoon-tours', 'label' => 'Honeymoon'],
        ['slug' => 'family-tours', 'label' => 'Family'],
        ['slug' => 'adventure-tours', 'label' => 'Adventure'],
        ['slug' => 'wellness-tours', 'label' => 'Wellness'],
        ['slug' => 'luxury-tours', 'label' => 'Luxury'],
        ['slug' => 'wildlife-tours', 'label' => 'Wildlife'],
    ];
}

function trip_planner_intro_text(): string
{
    return trim((string) site_setting_get('trip_planner_intro', 'Pick a few preferences and start from a matching itinerary.'));
}

function trip_planner_meta_title(): string
{
    $title = trim((string) site_setting_get('trip_planner_meta_title', ''));
    return $title !== '' ? $title : ('Trip Planner | ' . APP_NAME);
}

function trip_planner_meta_description(): string
{
    $desc = trim((string) site_setting_get('trip_planner_meta_description', ''));
    return $desc !== '' ? $desc : 'Plan a private Sri Lanka trip by days and travel style, then send your preferences as an inquiry.';
}

function site_primary_phone(): string
{
    $value = trim((string) site_setting_get('contact_phone_primary', SITE_PHONE));
    return $value !== '' ? $value : SITE_PHONE;
}

function site_secondary_phone(): string
{
    $value = trim((string) site_setting_get('contact_phone_secondary', SITE_PHONE_SECONDARY));
    return $value !== '' ? $value : SITE_PHONE_SECONDARY;
}

function site_contact_email(): string
{
    $value = trim((string) site_setting_get('contact_email', SITE_EMAIL));
    if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return $value;
    }
    return SITE_EMAIL;
}

function normalize_whatsapp_number(string $value): string
{
    $digits = preg_replace('/\D+/', '', $value) ?? '';
    if ($digits === '') {
        $digits = preg_replace('/\D+/', '', WHATSAPP_NUMBER) ?? '';
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    }

    if (strlen($digits) === 10 && str_starts_with($digits, '0')) {
        $digits = '94' . substr($digits, 1);
    } elseif (strlen($digits) === 9 && !str_starts_with($digits, '94')) {
        $digits = '94' . $digits;
    }

    return $digits !== '' ? $digits : (preg_replace('/\D+/', '', WHATSAPP_NUMBER) ?? '94769192427');
}

function site_whatsapp_display_number(): string
{
    $value = trim((string) site_setting_get('contact_whatsapp_number', WHATSAPP_NUMBER));
    return $value !== '' ? $value : WHATSAPP_NUMBER;
}

function site_whatsapp_number(): string
{
    return normalize_whatsapp_number(site_whatsapp_display_number());
}

function site_default_whatsapp_text(): string
{
    $value = trim((string) site_setting_get('contact_whatsapp_default_text', DEFAULT_WHATSAPP_TEXT));
    return $value !== '' ? $value : DEFAULT_WHATSAPP_TEXT;
}

function site_whatsapp_url(?string $message = null): string
{
    $text = $message !== null && trim($message) !== '' ? trim($message) : site_default_whatsapp_text();
    return 'https://wa.me/' . rawurlencode(site_whatsapp_number()) . '?text=' . rawurlencode($text);
}

/**
 * Ensure static_pages has CTA and FAQ columns for per-page management.
 */
function ensure_static_pages_extended_schema(): void
{
    if (!db_table_exists('static_pages')) {
        return;
    }

    $alterParts = [];

    if (!db_column_exists('static_pages', 'faq_json')) {
        $alterParts[] = 'ADD COLUMN faq_json LONGTEXT NULL AFTER content';
    }

    if (!db_column_exists('static_pages', 'cta_title')) {
        $alterParts[] = 'ADD COLUMN cta_title VARCHAR(255) NULL AFTER faq_json';
    }

    if (!db_column_exists('static_pages', 'cta_text')) {
        $alterParts[] = 'ADD COLUMN cta_text TEXT NULL AFTER cta_title';
    }

    if (!$alterParts) {
        return;
    }

    try {
        db()->exec('ALTER TABLE static_pages ' . implode(', ', $alterParts));
    } catch (Throwable $e) {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'duplicate column')) {
            return;
        }
        throw $e;
    }
}

/**
 * Resolve CMS static-page slugs to their public URL path.
 *
 * The database keeps a stable single slug for the nested commercial landing
 * page while the public route preserves the planned, human-readable URL.
 */
function static_page_public_path(string $slug): string
{
    $normalized = trim($slug, " /\t\r\n");

    return match ($normalized) {
        'private-driver-sri-lanka-cost' => 'private-driver-sri-lanka/cost',
        default => $normalized,
    };
}

function static_page_public_url(string $slug, ?string $canonicalUrl = null): string
{
    $canonical = trim((string) $canonicalUrl);
    if ($canonical !== '') {
        return $canonical;
    }

    return base_url(static_page_public_path($slug));
}

/**
 * Ensure blog_posts has optional sidebar widget configuration fields.
 */
function ensure_blog_posts_sidebar_schema(): void
{
    if (!db_table_exists('blog_posts')) {
        return;
    }

    $alterParts = [];

    if (!db_column_exists('blog_posts', 'sidebar_widgets_json')) {
        $alterParts[] = 'ADD COLUMN sidebar_widgets_json LONGTEXT NULL AFTER canonical_url';
    }

    if (!$alterParts) {
        return;
    }

    try {
        db()->exec('ALTER TABLE blog_posts ' . implode(', ', $alterParts));
    } catch (Throwable $e) {
        $message = strtolower($e->getMessage());
        if (str_contains($message, 'duplicate column')) {
            return;
        }
        throw $e;
    }
}

/**
 * Normalize blog sidebar widget settings.
 */
function normalize_blog_sidebar_widget_settings(array $settings): array
{
    $normalizeIds = static function (mixed $ids): array {
        $values = is_array($ids) ? $ids : [];
        $values = array_values(array_unique(array_filter(array_map('intval', $values), static fn(int $id): bool => $id > 0)));
        return array_slice($values, 0, 20);
    };

    return [
        'show_related_posts' => !empty($settings['show_related_posts']) ? 1 : 0,
        'related_posts_count' => max(1, min(10, (int) ($settings['related_posts_count'] ?? 4))),
        'related_post_ids' => $normalizeIds($settings['related_post_ids'] ?? []),
        'show_related_itineraries' => !empty($settings['show_related_itineraries']) ? 1 : 0,
        'related_itineraries_count' => max(1, min(10, (int) ($settings['related_itineraries_count'] ?? 3))),
        'related_itinerary_ids' => $normalizeIds($settings['related_itinerary_ids'] ?? []),
        'show_related_destinations' => !empty($settings['show_related_destinations']) ? 1 : 0,
        'related_destinations_count' => max(1, min(10, (int) ($settings['related_destinations_count'] ?? 3))),
        'related_destination_ids' => $normalizeIds($settings['related_destination_ids'] ?? []),
        'show_ad' => !empty($settings['show_ad']) ? 1 : 0,
        'ad_title' => substr(trim((string) ($settings['ad_title'] ?? 'Recommended For You')), 0, 120),
        'ad_html' => trim((string) ($settings['ad_html'] ?? '')),
    ];
}

/**
 * Decode and sanitize sidebar widget settings from blog_posts.sidebar_widgets_json.
 */
function decode_blog_sidebar_widgets(?string $json): array
{
    $defaults = [
        'show_related_posts' => 1,
        'related_posts_count' => 4,
        'related_post_ids' => [],
        'show_related_itineraries' => 1,
        'related_itineraries_count' => 3,
        'related_itinerary_ids' => [],
        'show_related_destinations' => 1,
        'related_destinations_count' => 3,
        'related_destination_ids' => [],
        'show_ad' => 0,
        'ad_title' => 'Recommended For You',
        'ad_html' => '',
    ];

    $json = trim((string) $json);
    if ($json === '') {
        return $defaults;
    }

    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        return $defaults;
    }

    return normalize_blog_sidebar_widget_settings(array_merge($defaults, $decoded));
}

/**
 * Ensure contact_inquiries and related workflow tables include extended CRM fields.
 */
function ensure_contact_inquiries_extended_schema(): void
{
    if (!db_table_exists('contact_inquiries')) {
        return;
    }

    $alterParts = [];

    if (!db_column_exists('contact_inquiries', 'preferred_itinerary_id')) {
        $alterParts[] = 'ADD COLUMN preferred_itinerary_id BIGINT UNSIGNED NULL AFTER interests';
    }

    if (!db_column_exists('contact_inquiries', 'preferred_itinerary_name')) {
        $alterParts[] = 'ADD COLUMN preferred_itinerary_name VARCHAR(180) NULL AFTER preferred_itinerary_id';
    }

    if (!db_column_exists('contact_inquiries', 'trip_support_type')) {
        $alterParts[] = 'ADD COLUMN trip_support_type VARCHAR(40) NULL AFTER preferred_itinerary_name';
    }

    if (!db_column_exists('contact_inquiries', 'whatsapp')) {
        $alterParts[] = 'ADD COLUMN whatsapp VARCHAR(50) NULL AFTER phone';
    }

    if (!db_column_exists('contact_inquiries', 'adults_count')) {
        $alterParts[] = 'ADD COLUMN adults_count INT UNSIGNED NULL AFTER group_size';
    }

    if (!db_column_exists('contact_inquiries', 'children_count')) {
        $alterParts[] = 'ADD COLUMN children_count INT UNSIGNED NULL AFTER adults_count';
    }

    if (!db_column_exists('contact_inquiries', 'budget_min')) {
        $alterParts[] = 'ADD COLUMN budget_min DECIMAL(10,2) NULL AFTER children_count';
    }

    if (!db_column_exists('contact_inquiries', 'budget_max')) {
        $alterParts[] = 'ADD COLUMN budget_max DECIMAL(10,2) NULL AFTER budget_min';
    }

    if (!db_column_exists('contact_inquiries', 'budget_currency')) {
        $alterParts[] = "ADD COLUMN budget_currency CHAR(3) NULL AFTER budget_max";
    }

    if (!db_column_exists('contact_inquiries', 'accommodation_preference')) {
        $alterParts[] = 'ADD COLUMN accommodation_preference VARCHAR(120) NULL AFTER budget_currency';
    }

    if (!db_column_exists('contact_inquiries', 'inquiry_status')) {
        $alterParts[] = "ADD COLUMN inquiry_status VARCHAR(30) NOT NULL DEFAULT 'new' AFTER source_page";
    }

    if (!db_column_exists('contact_inquiries', 'admin_notes')) {
        $alterParts[] = 'ADD COLUMN admin_notes LONGTEXT NULL AFTER inquiry_status';
    }

    if (!db_column_exists('contact_inquiries', 'is_spam')) {
        $alterParts[] = 'ADD COLUMN is_spam TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_notes';
    }

    if (!db_column_exists('contact_inquiries', 'spam_reason')) {
        $alterParts[] = 'ADD COLUMN spam_reason VARCHAR(255) NULL AFTER is_spam';
    }

    if (!db_column_exists('contact_inquiries', 'follow_up_at')) {
        $alterParts[] = 'ADD COLUMN follow_up_at DATETIME NULL AFTER spam_reason';
    }

    if (!db_column_exists('contact_inquiries', 'last_contacted_at')) {
        $alterParts[] = 'ADD COLUMN last_contacted_at DATETIME NULL AFTER follow_up_at';
    }

    if (!db_column_exists('contact_inquiries', 'admin_updated_at')) {
        $alterParts[] = 'ADD COLUMN admin_updated_at DATETIME NULL AFTER last_contacted_at';
    }

    if (!db_column_exists('contact_inquiries', 'email_sent_to_customer')) {
        $alterParts[] = 'ADD COLUMN email_sent_to_customer TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_updated_at';
    }

    if (!db_column_exists('contact_inquiries', 'email_sent_to_admin')) {
        $alterParts[] = 'ADD COLUMN email_sent_to_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER email_sent_to_customer';
    }

    if (!db_column_exists('contact_inquiries', 'tracking_meta_json')) {
        $alterParts[] = 'ADD COLUMN tracking_meta_json LONGTEXT NULL AFTER email_sent_to_admin';
    }

    if ($alterParts) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ' . implode(', ', $alterParts));
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (!str_contains($message, 'duplicate column')) {
                throw $e;
            }
        }
    }

    if (!db_index_exists('contact_inquiries', 'idx_contact_inquiries_itinerary')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD KEY idx_contact_inquiries_itinerary (preferred_itinerary_id)');
        } catch (Throwable) {
        }
    }

    if (!db_index_exists('contact_inquiries', 'idx_contact_inquiries_status')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD KEY idx_contact_inquiries_status (inquiry_status)');
        } catch (Throwable) {
        }
    }

    if (!db_index_exists('contact_inquiries', 'idx_contact_inquiries_spam')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD KEY idx_contact_inquiries_spam (is_spam)');
        } catch (Throwable) {
        }
    }

    if (!db_index_exists('contact_inquiries', 'idx_contact_inquiries_follow_up')) {
        try {
            db()->exec('ALTER TABLE contact_inquiries ADD KEY idx_contact_inquiries_follow_up (follow_up_at)');
        } catch (Throwable) {
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS contact_inquiry_activity (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            inquiry_id BIGINT UNSIGNED NOT NULL,
            action_type VARCHAR(80) NOT NULL,
            old_value VARCHAR(190) NULL,
            new_value VARCHAR(190) NULL,
            note TEXT NULL,
            admin_user_id BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_contact_inquiry_activity_inquiry (inquiry_id, created_at),
            KEY idx_contact_inquiry_activity_action (action_type),
            KEY idx_contact_inquiry_activity_admin (admin_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS form_submission_limits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_key VARCHAR(80) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_form_submission_limits_form_time (form_key, created_at),
            KEY idx_form_submission_limits_ip_time (ip_address, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function log_contact_inquiry_activity(
    int $inquiryId,
    string $actionType,
    ?string $oldValue = null,
    ?string $newValue = null,
    ?string $note = null,
    ?int $adminUserId = null
): void {
    if ($inquiryId <= 0) {
        return;
    }

    try {
        ensure_contact_inquiries_extended_schema();
        execute_query(
            "INSERT INTO contact_inquiry_activity
            (inquiry_id, action_type, old_value, new_value, note, admin_user_id)
            VALUES
            (:inquiry_id, :action_type, :old_value, :new_value, :note, :admin_user_id)",
            [
                'inquiry_id' => $inquiryId,
                'action_type' => substr(trim($actionType), 0, 80),
                'old_value' => $oldValue !== null ? substr($oldValue, 0, 190) : null,
                'new_value' => $newValue !== null ? substr($newValue, 0, 190) : null,
                'note' => $note !== null ? trim($note) : null,
                'admin_user_id' => $adminUserId,
            ]
        );
    } catch (Throwable) {
        // activity logging must never interrupt request flow
    }
}

/**
 * Build default contact-form values from query params (planner/compare/itinerary context).
 *
 * Supported params:
 * - itinerary_id=123
 * - from=plan&d=7&theme=honeymoon-tours
 * - from_compare=1,2,3 (or compare_ids=1,2,3)
 *
 * @return array{preferred_itinerary_id?:int,message?:string,interests?:string}
 */
function contact_prefill_from_query(array $query, array $publishedItineraryMap = []): array
{
    $result = [];

    $preferredId = (int) ($query['itinerary_id'] ?? 0);
    if ($preferredId <= 0) {
        $preferredId = (int) ($query['preferred_itinerary_id'] ?? 0);
    }

    $from = strtolower(trim((string) ($query['from'] ?? '')));
    $days = (int) ($query['d'] ?? 0);
    $theme = strtolower(trim((string) ($query['theme'] ?? '')));
    $theme = preg_replace('/[^a-z0-9-]+/', '', $theme) ?: '';

    $compareIdsRaw = trim((string) ($query['from_compare'] ?? ($query['compare_ids'] ?? '')));
    $compareIds = [];
    if ($compareIdsRaw !== '') {
        foreach (preg_split('/\\s*,\\s*/', $compareIdsRaw) ?: [] as $part) {
            $id = (int) trim((string) $part);
            if ($id > 0) {
                $compareIds[] = $id;
            }
        }
        $compareIds = array_values(array_unique($compareIds));
        $compareIds = array_slice($compareIds, 0, 6);
    }

    $messageLines = [];

    if ($preferredId > 0) {
        $result['preferred_itinerary_id'] = $preferredId;

        $name = '';
        if (isset($publishedItineraryMap[$preferredId])) {
            $name = trim((string) ($publishedItineraryMap[$preferredId]['name'] ?? ''));
        } elseif (db_table_exists('itineraries')) {
            $row = fetch_one(
                "SELECT name FROM itineraries WHERE id = :id AND is_published = 1 LIMIT 1",
                ['id' => $preferredId]
            );
            $name = trim((string) ($row['name'] ?? ''));
        }

        if ($name !== '') {
            $messageLines[] = "I'm enquiring about this itinerary: {$name}.";
        }
    }

    if ($compareIds) {
        $names = [];
        foreach ($compareIds as $id) {
            if (isset($publishedItineraryMap[$id])) {
                $n = trim((string) ($publishedItineraryMap[$id]['name'] ?? ''));
                if ($n !== '') {
                    $names[] = $n;
                }
            }
        }
        if (!$names && db_table_exists('itineraries')) {
            $ph = [];
            $params = [];
            foreach ($compareIds as $idx => $id) {
                $key = 'id' . $idx;
                $ph[] = ':' . $key;
                $params[$key] = $id;
            }
            $rows = fetch_all(
                "SELECT id, name FROM itineraries WHERE is_published = 1 AND id IN (" . implode(',', $ph) . ")",
                $params
            );
            $byId = [];
            foreach ($rows as $r) {
                $byId[(int) ($r['id'] ?? 0)] = trim((string) ($r['name'] ?? ''));
            }
            foreach ($compareIds as $id) {
                $n = trim((string) ($byId[$id] ?? ''));
                if ($n !== '') {
                    $names[] = $n;
                }
            }
        }

        if ($names) {
            $messageLines[] = "I compared these itineraries:";
            foreach (array_slice($names, 0, 6) as $n) {
                $messageLines[] = "- {$n}";
            }
        }
    }

    if ($from === 'plan') {
        $messageLines[] = 'Trip-planner preferences:'
            . ($days > 0 ? ' Days: ' . $days . '.' : ' Days: (any).')
            . ($theme !== '' ? ' Theme: ' . ucwords(str_replace('-', ' ', str_replace('-tours', '', $theme))) . '.' : ' Theme: (any).');

        // Light mapping from theme -> interest keywords.
        $interestHints = [];
        if (str_contains($theme, 'honeymoon')) $interestHints[] = 'honeymoon';
        if (str_contains($theme, 'family')) $interestHints[] = 'family travel';
        if (str_contains($theme, 'adventure')) $interestHints[] = 'adventure';
        if (str_contains($theme, 'wellness')) $interestHints[] = 'wellness';
        if (str_contains($theme, 'luxury')) $interestHints[] = 'luxury';

        if ($interestHints) {
            $result['interests'] = implode(', ', $interestHints);
        }
    }

    if ($messageLines) {
        $result['message'] = implode("\n", $messageLines) . "\n\n" . 'Travel dates:' . "\n" . 'Group size:' . "\n" . 'Must-see places:';
    }

    return $result;
}

function send_html_email(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    $to = trim($to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $subject = trim($subject);
    if ($subject === '') {
        return false;
    }

    $fromAddress = trim(site_contact_email());
    if (!filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
        $fromAddress = 'no-reply@' . (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost');
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . APP_NAME . ' <' . $fromAddress . '>',
    ];

    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: <' . trim($replyTo) . '>';
    }

    try {
        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    } catch (Throwable) {
        return false;
    }
}

function render_contact_inquiry_email_html(array $data, bool $forCustomer = false): string
{
    $name = trim((string) ($data['name'] ?? 'Traveler'));
    $inquiryId = (int) ($data['id'] ?? 0);
    $preferredItinerary = trim((string) ($data['preferred_itinerary_name'] ?? ''));
    $travelDates = trim((string) ($data['travel_dates'] ?? ''));
    $groupSize = trim((string) ($data['group_size'] ?? ''));
    $adults = (int) ($data['adults_count'] ?? 0);
    $children = (int) ($data['children_count'] ?? 0);
    $budgetMin = isset($data['budget_min']) && (float) $data['budget_min'] > 0 ? (float) $data['budget_min'] : null;
    $budgetMax = isset($data['budget_max']) && (float) $data['budget_max'] > 0 ? (float) $data['budget_max'] : null;
    $budgetCurrency = strtoupper(trim((string) ($data['budget_currency'] ?? 'USD')));
    $accommodationPreference = trim((string) ($data['accommodation_preference'] ?? ''));
    $interests = trim((string) ($data['interests'] ?? ''));
    $message = nl2br(e(trim((string) ($data['message'] ?? ''))));
    $country = trim((string) ($data['country'] ?? ''));
    $phone = trim((string) ($data['phone'] ?? ''));
    $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));

    $budgetLabel = '';
    if ($budgetMin !== null && $budgetMax !== null && $budgetMax >= $budgetMin) {
        $budgetLabel = $budgetCurrency . ' ' . format_price_amount($budgetMin) . ' - ' . $budgetCurrency . ' ' . format_price_amount($budgetMax);
    } elseif ($budgetMin !== null) {
        $budgetLabel = 'From ' . $budgetCurrency . ' ' . format_price_amount($budgetMin);
    } elseif ($budgetMax !== null) {
        $budgetLabel = 'Up to ' . $budgetCurrency . ' ' . format_price_amount($budgetMax);
    }

    if ($forCustomer) {
        return '
            <div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">
                <h2 style="margin:0 0 12px 0;">Thank you for your inquiry, ' . e($name) . '.</h2>
                <p style="margin:0 0 10px 0;">We received your request and our team will respond shortly with itinerary suggestions.</p>
                <p style="margin:0 0 10px 0;"><strong>Reference ID:</strong> #' . e((string) $inquiryId) . '</p>
                <p style="margin:0 0 14px 0;"><a href="' . e(base_url('contact')) . '" style="color:#0f766e;">You can send additional details here.</a></p>
                <p style="margin:0;">Warm regards,<br>' . e(APP_NAME) . '</p>
            </div>
        ';
    }

    return '
        <div style="font-family:Arial,sans-serif;line-height:1.6;color:#0f172a">
            <h2 style="margin:0 0 12px 0;">New Inquiry #' . e((string) $inquiryId) . '</h2>
            <p style="margin:0 0 10px 0;"><strong>Name:</strong> ' . e($name) . '</p>
            <p style="margin:0 0 10px 0;"><strong>Email:</strong> ' . e($email !== '' ? $email : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Phone:</strong> ' . e($phone !== '' ? $phone : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>WhatsApp:</strong> ' . e($whatsapp !== '' ? $whatsapp : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Country:</strong> ' . e($country !== '' ? $country : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Travel Dates:</strong> ' . e($travelDates !== '' ? $travelDates : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Group Size:</strong> ' . e($groupSize !== '' ? $groupSize : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Adults/Children:</strong> ' . e((string) $adults) . ' / ' . e((string) $children) . '</p>
            <p style="margin:0 0 10px 0;"><strong>Preferred Itinerary:</strong> ' . e($preferredItinerary !== '' ? $preferredItinerary : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Budget:</strong> ' . e($budgetLabel !== '' ? $budgetLabel : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Accommodation:</strong> ' . e($accommodationPreference !== '' ? $accommodationPreference : '-') . '</p>
            <p style="margin:0 0 10px 0;"><strong>Interests:</strong> ' . e($interests !== '' ? $interests : '-') . '</p>
            <p style="margin:0 0 5px 0;"><strong>Message:</strong></p>
            <div style="padding:10px;border:1px solid #cbd5e1;border-radius:8px;background:#f8fafc;">' . $message . '</div>
            <p style="margin:12px 0 0 0;"><a href="' . e(base_url('admin/inquiries.php')) . '" style="color:#0f766e;">Open inquiries in admin</a></p>
        </div>
    ';
}

function destination_card_select_fields(): string
{
    if (db_column_exists('destinations', 'province')) {
        return 'id, name, slug, excerpt, province, cover_image, hero_image';
    }

    return 'id, name, slug, excerpt, NULL AS province, cover_image, hero_image';
}

function find_published_destination_by_slug(string $slug): ?array
{
    return fetch_one(
        "SELECT * FROM destinations WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function find_published_tour_by_slug(string $slug): ?array
{
    return fetch_one(
        "SELECT * FROM tours WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function find_published_itinerary_by_slug(string $slug): ?array
{
    return fetch_one(
        "SELECT * FROM itineraries WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function fetch_itinerary_categories(bool $publishedOnly = true): array
{
    if (!db_table_exists('itinerary_categories')) {
        return [];
    }

    $where = '';
    if ($publishedOnly && db_column_exists('itinerary_categories', 'is_published')) {
        $where = 'WHERE is_published = 1';
    }

    return fetch_all("
        SELECT
            id,
            name,
            slug,
            description,
            hero_image,
            og_image,
            meta_title,
            meta_description,
            canonical_url,
            sort_order,
            " . (db_column_exists('itinerary_categories', 'is_published') ? 'is_published' : '1 AS is_published') . "
        FROM itinerary_categories
        {$where}
        ORDER BY sort_order ASC, name ASC
    ");
}

function fetch_published_itinerary_categories_with_counts(): array
{
    if (!db_table_exists('itinerary_categories')) {
        return [];
    }

    $hasCategoryOnItineraries = db_column_exists('itineraries', 'category_id');
    $hasThemeSlug = db_column_exists('itinerary_categories', 'theme_slug');
    $hasIsTheme = db_column_exists('itinerary_categories', 'is_theme');
    $publishedFilter = db_column_exists('itinerary_categories', 'is_published') ? 'WHERE c.is_published = 1' : '';

    $countExpr = $hasCategoryOnItineraries
        ? 'COUNT(i.id)'
        : '0';
    $itineraryPublishedFilter = db_column_exists('itineraries', 'is_published')
        ? 'AND i.is_published = 1'
        : '';

    $join = $hasCategoryOnItineraries
        ? "LEFT JOIN itineraries i
             ON i.category_id = c.id
            {$itineraryPublishedFilter}"
        : '';

    return fetch_all("
        SELECT
            c.id,
            c.name,
            c.slug,
            c.description,
            c.hero_image,
            c.og_image,
            c.meta_title,
            c.meta_description,
            c.canonical_url,
            c.sort_order,
            " . (db_column_exists('itinerary_categories', 'is_published') ? 'c.is_published' : '1 AS is_published') . ",
            " . ($hasThemeSlug ? 'c.theme_slug' : 'NULL AS theme_slug') . ",
            " . ($hasIsTheme ? 'c.is_theme' : ($hasThemeSlug ? "CASE WHEN c.theme_slug IS NOT NULL AND c.theme_slug <> '' THEN 1 ELSE 0 END AS is_theme" : '0 AS is_theme')) . ",
            {$countExpr} AS itinerary_count
        FROM itinerary_categories c
        {$join}
        {$publishedFilter}
        GROUP BY
            c.id, c.name, c.slug, c.description, c.hero_image, c.og_image,
            c.meta_title, c.meta_description, c.canonical_url, c.sort_order" .
            (db_column_exists('itinerary_categories', 'is_published') ? ', c.is_published' : '') .
            ($hasThemeSlug ? ', c.theme_slug' : '') . ($hasIsTheme ? ', c.is_theme' : '') . "
        ORDER BY c.sort_order ASC, c.name ASC
    ");
}

function find_itinerary_category_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    if (!db_table_exists('itinerary_categories')) {
        return null;
    }

    $where = 'slug = :slug';
    if ($publishedOnly && db_column_exists('itinerary_categories', 'is_published')) {
        $where .= ' AND is_published = 1';
    }

    return fetch_one(
        "SELECT * FROM itinerary_categories WHERE {$where} LIMIT 1",
        ['slug' => $slug]
    );
}

function itinerary_category_url(array $category): string
{
    $slug = trim((string) ($category['slug'] ?? ''));
    $themeSlugs = [
        'honeymoon' => 'honeymoon-tours',
        'family' => 'family-tours',
        'adventure' => 'adventure-tours',
        'wellness' => 'wellness-tours',
        'luxury' => 'luxury-tours',
        'wildlife' => 'wildlife-tours',
        'day-tours' => 'day-tours',
    ];

    if (isset($themeSlugs[$slug])) {
        return base_url($themeSlugs[$slug]);
    }

    return base_url('itineraries') . ($slug !== '' ? '?theme=' . rawurlencode($slug) : '');
}

function itinerary_category_public_canonical_url(array $category): string
{
    $slug = trim((string) ($category['slug'] ?? ''));
    if ($slug === '') {
        return base_url('tours');
    }

    $selfUrl = base_url('tours/' . $slug);
    $themeSlug = trim((string) ($category['theme_slug'] ?? ''));
    $isTheme = array_key_exists('is_theme', $category)
        ? (int) $category['is_theme'] === 1
        : $themeSlug !== '';
    if ($themeSlug === '' || !$isTheme) {
        return $selfUrl;
    }

    $preferredUrl = itinerary_category_url($category);
    $preferredParts = parse_url($preferredUrl);
    if (!is_array($preferredParts) || !empty($preferredParts['query']) || !isset($preferredParts['path'])) {
        return $selfUrl;
    }

    return $preferredUrl;
}

function itinerary_category_sitemap_indexable(array $category): bool
{
    $slug = trim((string) ($category['slug'] ?? ''));
    if ($slug === '' || itinerary_category_public_canonical_url($category) !== base_url('tours/' . $slug)) {
        return false;
    }

    return !array_key_exists('itinerary_count', $category) || (int) $category['itinerary_count'] > 0;
}

function ensure_experiences_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS experiences (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            excerpt TEXT NULL,
            intro LONGTEXT NULL,
            where_to_experience LONGTEXT NULL,
            tips_json LONGTEXT NULL,
            best_time TEXT NULL,
            hero_image VARCHAR(255) NULL,
            og_image VARCHAR(255) NULL,
            meta_title VARCHAR(255) NULL,
            meta_description VARCHAR(320) NULL,
            canonical_url VARCHAR(255) NULL,
            cta_title VARCHAR(255) NULL,
            cta_text TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_experiences_published_sort (is_published, sort_order),
            KEY idx_experiences_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $experienceColumns = [
        'category_id' => 'ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER slug',
        'excerpt' => 'ADD COLUMN excerpt TEXT NULL AFTER slug',
        'intro' => 'ADD COLUMN intro LONGTEXT NULL AFTER excerpt',
        'where_to_experience' => 'ADD COLUMN where_to_experience LONGTEXT NULL AFTER intro',
        'tips_json' => 'ADD COLUMN tips_json LONGTEXT NULL AFTER where_to_experience',
        'best_time' => 'ADD COLUMN best_time TEXT NULL AFTER tips_json',
        'hero_image' => 'ADD COLUMN hero_image VARCHAR(255) NULL AFTER best_time',
        'og_image' => 'ADD COLUMN og_image VARCHAR(255) NULL AFTER hero_image',
        'meta_title' => 'ADD COLUMN meta_title VARCHAR(255) NULL AFTER og_image',
        'meta_description' => 'ADD COLUMN meta_description VARCHAR(320) NULL AFTER meta_title',
        'canonical_url' => 'ADD COLUMN canonical_url VARCHAR(255) NULL AFTER meta_description',
        'cta_title' => 'ADD COLUMN cta_title VARCHAR(255) NULL AFTER canonical_url',
        'cta_text' => 'ADD COLUMN cta_text TEXT NULL AFTER cta_title',
        'is_featured' => 'ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER cta_text',
        'is_published' => 'ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured',
        'sort_order' => 'ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_published',
        'itinerary_count' => 'ADD COLUMN itinerary_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER sort_order',
        'destination_count' => 'ADD COLUMN destination_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER itinerary_count',
        'created_at' => 'ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER destination_count',
        'updated_at' => 'ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];

    $alterParts = [];
    foreach ($experienceColumns as $column => $definition) {
        if (!db_column_exists('experiences', $column)) {
            $alterParts[] = $definition;
        }
    }

    if ($alterParts) {
        try {
            db()->exec('ALTER TABLE experiences ' . implode(', ', $alterParts));
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (!str_contains($message, 'duplicate column')) {
                throw $e;
            }
        }
    }

    foreach (array_keys($experienceColumns) as $column) {
        db_column_exists('experiences', $column, true);
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            slug VARCHAR(140) NOT NULL,
            emoji VARCHAR(16) NULL,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_experience_categories_slug (slug),
            KEY idx_experience_categories_active_sort (is_active, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    db_table_exists('experience_categories', true);

    $experienceCategoryDefaults = [
        ['Wildlife', 'wildlife', 'ðŸ˜', 'Safari drives, national parks, elephants, leopards, birding, and wild landscapes.', 10],
        ['Culture', 'culture', 'ðŸ›', 'UNESCO sites, temples, historic towns, Buddhist heritage, and local traditions.', 20],
        ['Hill Country', 'hill', 'â›°', 'Tea country, rail journeys, waterfalls, mountain viewpoints, and cool climate routes.', 30],
        ['Food & Village', 'food', 'ðŸ›', 'Market walks, cooking, village life, spice gardens, and Sri Lankan food culture.', 40],
        ['Beach & Coast', 'beach', 'ðŸ–', 'Southern beaches, coastal towns, whale watching, surfing, and sea-view stays.', 50],
        ['Off-Route Gems', 'off-route', 'ðŸ§­', 'Lesser-known stops, soft adventure, scenic detours, and private-driver discoveries.', 60],
        ['Slow Travel', 'slow', 'ðŸ§˜', 'Wellness, ayurveda, boutique stays, honeymoon pacing, and luxury slow routes.', 70],
    ];

    foreach ($experienceCategoryDefaults as [$name, $slug, $emoji, $description, $sortOrder]) {
        execute_query(
            "INSERT IGNORE INTO experience_categories (name, slug, emoji, description, sort_order, is_active)
             VALUES (:name, :slug, :emoji, :description, :sort_order, 1)",
            [
                'name' => $name,
                'slug' => $slug,
                'emoji' => $emoji,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]
        );
    }

    try {
        db()->exec('ALTER TABLE experiences ADD KEY idx_experiences_category (category_id, is_published, sort_order)');
    } catch (Throwable $e) {
        $message = strtolower($e->getMessage());
        if (!str_contains($message, 'duplicate') && !str_contains($message, 'exists')) {
            throw $e;
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_destination (
            experience_id BIGINT UNSIGNED NOT NULL,
            destination_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (experience_id, destination_id),
            KEY idx_experience_destination_destination (destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_category_pivot (
            experience_id BIGINT UNSIGNED NOT NULL,
            category_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (experience_id, category_id),
            KEY idx_experience_category_pivot_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_itinerary (
            experience_id BIGINT UNSIGNED NOT NULL,
            itinerary_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (experience_id, itinerary_id),
            KEY idx_experience_itinerary_itinerary (itinerary_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_related (
            experience_id BIGINT UNSIGNED NOT NULL,
            related_experience_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (experience_id, related_experience_id),
            KEY idx_experience_related_related (related_experience_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS experience_gallery (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            experience_id BIGINT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_experience_gallery_experience (experience_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if (db_column_exists('experiences', 'category_id', true)) {
        $categoryBackfillRules = [
            'wildlife' => ['wildlife', 'safari', 'leopard', 'elephant', 'minneriya', 'sinharaja', 'birding', 'family-wildlife'],
            'culture' => ['sigiriya', 'pidurangala', 'ancient', 'culture', 'temple', 'buddhist', 'heritage', 'pilgrimage', 'galle-fort'],
            'hill' => ['train', 'tea', 'nuwara', 'ella', 'hill', 'waterfall', 'plantation', 'kandy-to-ella', 'scenic'],
            'food' => ['food', 'cooking', 'market', 'village'],
            'beach' => ['beach', 'coast', 'mirissa', 'whale', 'surf', 'honeymoon'],
            'off-route' => ['hidden', 'off-route', 'adventure'],
            'slow' => ['ayurveda', 'wellness', 'slow', 'luxury'],
        ];

        foreach ($categoryBackfillRules as $categorySlug => $keywords) {
            $conditions = [];
            $params = ['category_slug' => $categorySlug];
            foreach ($keywords as $index => $keyword) {
                $param = 'keyword_' . $index;
                $conditions[] = "LOWER(CONCAT_WS(' ', e.slug, e.title)) LIKE :{$param}";
                $params[$param] = '%' . strtolower($keyword) . '%';
            }

            execute_query(
                "UPDATE experiences e
                 INNER JOIN experience_categories c ON c.slug = :category_slug
                 SET e.category_id = c.id
                 WHERE (e.category_id IS NULL OR e.category_id = 0)
                   AND (" . implode(' OR ', $conditions) . ")",
                $params
            );
        }

        execute_query(
            "INSERT IGNORE INTO experience_category_pivot (experience_id, category_id)
             SELECT e.id, e.category_id
             FROM experiences e
             WHERE e.category_id IS NOT NULL AND e.category_id > 0"
        );
    }

    if (db_column_exists('experiences', 'itinerary_count', true) && db_table_exists('experience_itinerary', true)) {
        execute_query(
            "UPDATE experiences e
             SET e.itinerary_count = (
                 SELECT COUNT(*) FROM experience_itinerary ei WHERE ei.experience_id = e.id
             )"
        );
    }

    if (db_column_exists('experiences', 'destination_count', true) && db_table_exists('experience_destination', true)) {
        execute_query(
            "UPDATE experiences e
             SET e.destination_count = (
                 SELECT COUNT(*) FROM experience_destination ed WHERE ed.experience_id = e.id
             )"
        );
    }

    $ready = true;
}

function fetch_experiences(bool $publishedOnly = true): array
{
    ensure_experiences_schema();

    $where = $publishedOnly ? 'WHERE is_published = 1' : '';
    return fetch_all("
        SELECT *
        FROM experiences
        {$where}
        ORDER BY is_featured DESC, sort_order ASC, title ASC
    ");
}

function ensure_reviews_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    if (!db_table_exists('reviews')) {
        db()->exec("
            CREATE TABLE reviews (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(180) NOT NULL,
                country VARCHAR(120) NULL,
                country_code CHAR(2) NULL,
                rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
                review_title VARCHAR(255) NULL,
                review_text LONGTEXT NOT NULL,
                trip_type VARCHAR(120) NULL,
                tour_done_date DATE NULL,
                tour_done_month CHAR(7) NULL,
                review_source VARCHAR(120) NULL,
                review_source_url VARCHAR(255) NULL,
                is_featured TINYINT(1) NOT NULL DEFAULT 0,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_reviews_public_date (is_published, tour_done_date),
                KEY idx_reviews_featured_sort (is_featured, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        $reviewColumns = [
            'country_code' => 'ADD COLUMN country_code CHAR(2) NULL AFTER country',
            'review_title' => 'ADD COLUMN review_title VARCHAR(255) NULL AFTER rating',
            'tour_done_date' => 'ADD COLUMN tour_done_date DATE NULL AFTER trip_type',
            'tour_done_month' => 'ADD COLUMN tour_done_month CHAR(7) NULL AFTER tour_done_date',
            'review_source' => 'ADD COLUMN review_source VARCHAR(120) NULL AFTER tour_done_month',
            'review_source_url' => 'ADD COLUMN review_source_url VARCHAR(255) NULL AFTER review_source',
            'is_featured' => 'ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER review_source_url',
            'is_published' => 'ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 1 AFTER is_featured',
            'sort_order' => 'ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_published',
            'created_at' => 'ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER sort_order',
            'updated_at' => 'ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
        ];

        $alterParts = [];
        foreach ($reviewColumns as $column => $definition) {
            if (!db_column_exists('reviews', $column)) {
                $alterParts[] = $definition;
            }
        }
        if ($alterParts) {
            try {
                db()->exec('ALTER TABLE reviews ' . implode(', ', $alterParts));
            } catch (Throwable $e) {
                $message = strtolower($e->getMessage());
                if (!str_contains($message, 'duplicate column')) {
                    throw $e;
                }
            }
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS review_photos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            review_id BIGINT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_review_photos_review (review_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ready = true;
}

function fetch_review_photos(int $reviewId): array
{
    if ($reviewId <= 0) {
        return [];
    }
    ensure_reviews_schema();
    if (!db_table_exists('review_photos')) {
        return [];
    }

    return fetch_all(
        "SELECT * FROM review_photos WHERE review_id = :review_id ORDER BY sort_order ASC, id ASC",
        ['review_id' => $reviewId]
    );
}

function fetch_review_photos_for_reviews(array $reviewIds): array
{
    $reviewIds = array_values(array_unique(array_filter(array_map('intval', $reviewIds))));
    if (!$reviewIds) {
        return [];
    }
    ensure_reviews_schema();
    if (!db_table_exists('review_photos')) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
    $rows = fetch_all(
        "SELECT * FROM review_photos WHERE review_id IN ({$placeholders}) ORDER BY sort_order ASC, id ASC",
        $reviewIds
    );
    $grouped = [];
    foreach ($rows as $row) {
        $grouped[(int) $row['review_id']][] = $row;
    }
    return $grouped;
}

function country_flag_emoji(?string $countryCode): string
{
    $code = strtoupper(trim((string) $countryCode));
    if (!preg_match('/^[A-Z]{2}$/', $code)) {
        return '';
    }
    $flag = '';
    foreach (str_split($code) as $letter) {
        $flag .= html_entity_decode('&#x' . dechex(0x1F1E6 + (ord($letter) - 65)) . ';', ENT_QUOTES, 'UTF-8');
    }
    return $flag;
}

function review_source_label(?string $source): string
{
    $source = trim((string) $source);
    return $source !== '' ? $source : 'External Review';
}

function review_source_icon(?string $source): string
{
    $value = strtolower(trim((string) $source));
    if (str_contains($value, 'trip')) {
        return 'TA';
    }
    if (str_contains($value, 'google')) {
        return 'G';
    }
    if (str_contains($value, 'facebook')) {
        return 'f';
    }
    if (str_contains($value, 'airbnb')) {
        return 'A';
    }
    return '*';
}

function fetch_experiences_with_counts(bool $publishedOnly = true): array
{
    ensure_experiences_schema();

    $where = $publishedOnly ? 'WHERE e.is_published = 1' : '';
    return fetch_all("
        SELECT e.*,
               COALESCE(ic.itinerary_count, 0) AS itinerary_count,
               COALESCE(dc.destination_count, 0) AS destination_count
        FROM experiences e
        LEFT JOIN (
            SELECT experience_id, COUNT(*) AS itinerary_count
            FROM experience_itinerary
            GROUP BY experience_id
        ) ic ON ic.experience_id = e.id
        LEFT JOIN (
            SELECT experience_id, COUNT(*) AS destination_count
            FROM experience_destination
            GROUP BY experience_id
        ) dc ON dc.experience_id = e.id
        {$where}
        ORDER BY e.is_featured DESC, e.sort_order ASC, e.title ASC
    ");
}

function find_published_experience_by_slug(string $slug): ?array
{
    ensure_experiences_schema();

    return fetch_one(
        "SELECT * FROM experiences WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function experience_url(array $experience): string
{
    return base_url('experiences/' . ($experience['slug'] ?? ''));
}

function fetch_experience_destinations(int $experienceId): array
{
    if ($experienceId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    return fetch_all("
        SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image, d.province
        FROM experience_destination ed
        INNER JOIN destinations d ON d.id = ed.destination_id
        WHERE ed.experience_id = :id
          AND d.is_published = 1
        ORDER BY d.sort_order ASC, d.name ASC
    ", ['id' => $experienceId]);
}

function fetch_experience_gallery(int $experienceId): array
{
    if ($experienceId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    if (!db_table_exists('experience_gallery')) {
        return [];
    }

    return fetch_all(
        "SELECT * FROM experience_gallery WHERE experience_id = :id ORDER BY sort_order ASC, id ASC",
        ['id' => $experienceId]
    );
}

function fetch_experience_itineraries(int $experienceId): array
{
    if ($experienceId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    return fetch_all("
        SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image, i.meta_description,
               " . pricing_select_fragment('itineraries') . "
        FROM experience_itinerary ei
        INNER JOIN itineraries i ON i.id = ei.itinerary_id
        WHERE ei.experience_id = :id
          AND i.is_published = 1
        ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC, i.name ASC
    ", ['id' => $experienceId]);
}

function fetch_related_experiences(int $experienceId, int $limit = 3): array
{
    if ($experienceId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    $limit = max(1, min(12, $limit));
    return fetch_all("
        SELECT e.*
        FROM experience_related er
        INNER JOIN experiences e ON e.id = er.related_experience_id
        WHERE er.experience_id = :id
          AND e.is_published = 1
        ORDER BY e.is_featured DESC, e.sort_order ASC, e.title ASC
        LIMIT {$limit}
    ", ['id' => $experienceId]);
}

function fetch_experiences_for_destination(int $destinationId, int $limit = 4): array
{
    if ($destinationId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    $limit = max(1, min(12, $limit));
    return fetch_all("
        SELECT e.*
        FROM experience_destination ed
        INNER JOIN experiences e ON e.id = ed.experience_id
        WHERE ed.destination_id = :id
          AND e.is_published = 1
        ORDER BY e.is_featured DESC, e.sort_order ASC, e.title ASC
        LIMIT {$limit}
    ", ['id' => $destinationId]);
}

function fetch_experiences_for_itinerary(int $itineraryId, int $limit = 4): array
{
    if ($itineraryId <= 0) {
        return [];
    }

    ensure_experiences_schema();
    $limit = max(1, min(12, $limit));
    return fetch_all("
        SELECT e.*
        FROM experience_itinerary ei
        INNER JOIN experiences e ON e.id = ei.experience_id
        WHERE ei.itinerary_id = :id
          AND e.is_published = 1
        ORDER BY e.is_featured DESC, e.sort_order ASC, e.title ASC
        LIMIT {$limit}
    ", ['id' => $itineraryId]);
}

function business_partner_statuses(): array
{
    return [
        'applied' => 'Applied',
        'contacted' => 'Contacted',
        'negotiating' => 'Negotiating',
        'agreed' => 'Agreed',
        'rejected' => 'Rejected',
        'published' => 'Published',
    ];
}

function business_partner_types(): array
{
    return [
        'hotel' => 'Hotel',
        'villa' => 'Villa',
        'guesthouse' => 'Guesthouse',
        'boutique-stay' => 'Boutique Stay',
        'resort' => 'Resort',
        'homestay' => 'Homestay',
    ];
}

function ensure_business_partners_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS business_partner_categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(180) NOT NULL,
            slug VARCHAR(180) NOT NULL UNIQUE,
            description TEXT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            is_published TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_business_partner_categories_public (is_published, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS business_partners (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id BIGINT UNSIGNED NULL,
            business_name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            business_type VARCHAR(80) NOT NULL DEFAULT 'hotel',
            location VARCHAR(180) NULL,
            destination_id BIGINT UNSIGNED NULL,
            contact_person VARCHAR(180) NULL,
            contact_role VARCHAR(180) NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(80) NULL,
            whatsapp VARCHAR(80) NULL,
            website_url VARCHAR(255) NULL,
            google_maps_url VARCHAR(255) NULL,
            short_description TEXT NULL,
            partner_reason TEXT NULL,
            accommodation_details TEXT NULL,
            price_range VARCHAR(80) NULL,
            admin_notes LONGTEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'applied',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            hero_image VARCHAR(255) NULL,
            og_image VARCHAR(255) NULL,
            meta_title VARCHAR(255) NULL,
            meta_description VARCHAR(320) NULL,
            canonical_url VARCHAR(255) NULL,
            published_at TIMESTAMP NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_business_partners_public (status, sort_order),
            KEY idx_business_partners_category (category_id, status),
            KEY idx_business_partners_destination (destination_id, status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $partnerColumns = [
        'category_id' => 'ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id',
        'business_type' => "ADD COLUMN business_type VARCHAR(80) NOT NULL DEFAULT 'hotel' AFTER slug",
        'location' => 'ADD COLUMN location VARCHAR(180) NULL AFTER business_type',
        'destination_id' => 'ADD COLUMN destination_id BIGINT UNSIGNED NULL AFTER location',
        'contact_person' => 'ADD COLUMN contact_person VARCHAR(180) NULL AFTER destination_id',
        'contact_role' => 'ADD COLUMN contact_role VARCHAR(180) NULL AFTER contact_person',
        'email' => 'ADD COLUMN email VARCHAR(190) NULL AFTER contact_role',
        'phone' => 'ADD COLUMN phone VARCHAR(80) NULL AFTER email',
        'whatsapp' => 'ADD COLUMN whatsapp VARCHAR(80) NULL AFTER phone',
        'website_url' => 'ADD COLUMN website_url VARCHAR(255) NULL AFTER whatsapp',
        'google_maps_url' => 'ADD COLUMN google_maps_url VARCHAR(255) NULL AFTER website_url',
        'short_description' => 'ADD COLUMN short_description TEXT NULL AFTER google_maps_url',
        'partner_reason' => 'ADD COLUMN partner_reason TEXT NULL AFTER short_description',
        'accommodation_details' => 'ADD COLUMN accommodation_details TEXT NULL AFTER partner_reason',
        'price_range' => 'ADD COLUMN price_range VARCHAR(80) NULL AFTER accommodation_details',
        'admin_notes' => 'ADD COLUMN admin_notes LONGTEXT NULL AFTER price_range',
        'status' => "ADD COLUMN status VARCHAR(40) NOT NULL DEFAULT 'applied' AFTER admin_notes",
        'is_featured' => 'ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0 AFTER status',
        'sort_order' => 'ADD COLUMN sort_order INT NOT NULL DEFAULT 0 AFTER is_featured',
        'hero_image' => 'ADD COLUMN hero_image VARCHAR(255) NULL AFTER sort_order',
        'og_image' => 'ADD COLUMN og_image VARCHAR(255) NULL AFTER hero_image',
        'meta_title' => 'ADD COLUMN meta_title VARCHAR(255) NULL AFTER og_image',
        'meta_description' => 'ADD COLUMN meta_description VARCHAR(320) NULL AFTER meta_title',
        'canonical_url' => 'ADD COLUMN canonical_url VARCHAR(255) NULL AFTER meta_description',
        'published_at' => 'ADD COLUMN published_at TIMESTAMP NULL AFTER canonical_url',
        'created_at' => 'ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER published_at',
        'updated_at' => 'ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at',
    ];

    $alterParts = [];
    foreach ($partnerColumns as $column => $definition) {
        if (!db_column_exists('business_partners', $column)) {
            $alterParts[] = $definition;
        }
    }
    if ($alterParts) {
        try {
            db()->exec('ALTER TABLE business_partners ' . implode(', ', $alterParts));
        } catch (Throwable $e) {
            $message = strtolower($e->getMessage());
            if (!str_contains($message, 'duplicate column')) {
                throw $e;
            }
        }
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS business_partner_photos (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            partner_id BIGINT UNSIGNED NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            alt_text VARCHAR(255) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_business_partner_photos_partner (partner_id, sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS business_partner_destination (
            partner_id BIGINT UNSIGNED NOT NULL,
            destination_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (partner_id, destination_id),
            KEY idx_business_partner_destination_destination (destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS business_partner_itinerary (
            partner_id BIGINT UNSIGNED NOT NULL,
            itinerary_id BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (partner_id, itinerary_id),
            KEY idx_business_partner_itinerary_itinerary (itinerary_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    execute_query(
        "INSERT IGNORE INTO business_partner_categories (id, name, slug, description, sort_order, is_published)
         VALUES (1, 'Hotels & Accommodations', 'hotels-accommodations', 'Official hotel, villa, guesthouse, boutique stay, resort, and homestay partners for private Sri Lanka tours.', 10, 1)"
    );

    $ready = true;
}

function business_partner_category_url(array $category): string
{
    return base_url('business-partners/' . ($category['slug'] ?? 'hotels-accommodations'));
}

function business_partner_url(array $partner): string
{
    return base_url('business-partners/partner/' . ($partner['slug'] ?? ''));
}

function fetch_business_partner_categories(bool $publishedOnly = true): array
{
    ensure_business_partners_schema();
    $where = $publishedOnly ? 'WHERE is_published = 1' : '';
    return fetch_all("
        SELECT *
        FROM business_partner_categories
        {$where}
        ORDER BY sort_order ASC, name ASC
    ");
}

function find_business_partner_category_by_slug(string $slug, bool $publishedOnly = true): ?array
{
    ensure_business_partners_schema();
    $where = 'slug = :slug';
    if ($publishedOnly) {
        $where .= ' AND is_published = 1';
    }
    return fetch_one("SELECT * FROM business_partner_categories WHERE {$where} LIMIT 1", ['slug' => $slug]);
}

function fetch_published_business_partners(?int $categoryId = null, int $limit = 100): array
{
    ensure_business_partners_schema();
    $limit = max(1, min(100, $limit));
    $params = [];
    $where = "WHERE bp.status = 'published'";
    if ($categoryId !== null && $categoryId > 0) {
        $where .= ' AND bp.category_id = :category_id';
        $params['category_id'] = $categoryId;
    }
    $hasDestinations = db_table_exists('destinations');
    $destinationNameSelect = $hasDestinations && db_column_exists('destinations', 'name') ? 'd.name AS destination_name' : 'NULL AS destination_name';
    $destinationSlugSelect = $hasDestinations && db_column_exists('destinations', 'slug') ? 'd.slug AS destination_slug' : 'NULL AS destination_slug';
    $destinationJoin = $hasDestinations ? 'LEFT JOIN destinations d ON d.id = bp.destination_id' : '';

    return fetch_all("
        SELECT bp.*, c.name AS category_name, c.slug AS category_slug, {$destinationNameSelect}, {$destinationSlugSelect}
        FROM business_partners bp
        LEFT JOIN business_partner_categories c ON c.id = bp.category_id
        {$destinationJoin}
        {$where}
        ORDER BY bp.is_featured DESC, bp.sort_order ASC, bp.business_name ASC
        LIMIT {$limit}
    ", $params);
}

function find_published_business_partner_by_slug(string $slug): ?array
{
    ensure_business_partners_schema();
    $hasDestinations = db_table_exists('destinations');
    $destinationNameSelect = $hasDestinations && db_column_exists('destinations', 'name') ? 'd.name AS destination_name' : 'NULL AS destination_name';
    $destinationSlugSelect = $hasDestinations && db_column_exists('destinations', 'slug') ? 'd.slug AS destination_slug' : 'NULL AS destination_slug';
    $destinationJoin = $hasDestinations ? 'LEFT JOIN destinations d ON d.id = bp.destination_id' : '';

    return fetch_one("
        SELECT bp.*, c.name AS category_name, c.slug AS category_slug, {$destinationNameSelect}, {$destinationSlugSelect}
        FROM business_partners bp
        LEFT JOIN business_partner_categories c ON c.id = bp.category_id
        {$destinationJoin}
        WHERE bp.slug = :slug
          AND bp.status = 'published'
        LIMIT 1
    ", ['slug' => $slug]);
}

function fetch_business_partner_photos(int $partnerId): array
{
    if ($partnerId <= 0) {
        return [];
    }
    ensure_business_partners_schema();
    if (!db_table_exists('business_partner_photos')) {
        return [];
    }

    return fetch_all(
        "SELECT * FROM business_partner_photos WHERE partner_id = :partner_id ORDER BY sort_order ASC, id ASC",
        ['partner_id' => $partnerId]
    );
}

function fetch_business_partners_for_destination(int $destinationId, int $limit = 4): array
{
    if ($destinationId <= 0) {
        return [];
    }
    ensure_business_partners_schema();
    $limit = max(1, min(12, $limit));
    return fetch_all("
        SELECT DISTINCT bp.*, c.name AS category_name, c.slug AS category_slug
        FROM business_partners bp
        LEFT JOIN business_partner_categories c ON c.id = bp.category_id
        LEFT JOIN business_partner_destination bpd ON bpd.partner_id = bp.id
        WHERE bp.status = 'published'
          AND (bp.destination_id = :primary_destination_id OR bpd.destination_id = :linked_destination_id)
        ORDER BY bp.is_featured DESC, bp.sort_order ASC, bp.business_name ASC
        LIMIT {$limit}
    ", [
        'primary_destination_id' => $destinationId,
        'linked_destination_id' => $destinationId,
    ]);
}

function fetch_business_partners_for_itinerary(int $itineraryId, int $limit = 4): array
{
    if ($itineraryId <= 0) {
        return [];
    }
    ensure_business_partners_schema();
    $limit = max(1, min(12, $limit));
    return fetch_all("
        SELECT bp.*, c.name AS category_name, c.slug AS category_slug
        FROM business_partner_itinerary bpi
        INNER JOIN business_partners bp ON bp.id = bpi.partner_id
        LEFT JOIN business_partner_categories c ON c.id = bp.category_id
        WHERE bpi.itinerary_id = :itinerary_id
          AND bp.status = 'published'
        ORDER BY bp.is_featured DESC, bp.sort_order ASC, bp.business_name ASC
        LIMIT {$limit}
    ", ['itinerary_id' => $itineraryId]);
}

function fetch_business_partners_for_day(int $itineraryId, string $location, int $limit = 3): array
{
    if ($itineraryId <= 0 || $location === '') {
        return [];
    }
    ensure_business_partners_schema();
    $limit = max(1, min(6, $limit));
    $loc = '%' . trim($location) . '%';
    return fetch_all("
        SELECT bp.id, bp.business_name, bp.slug, bp.hero_image, bp.location,
               bp.business_type, bp.category_id,
               c.name AS category_name
        FROM business_partner_itinerary bpi
        INNER JOIN business_partners bp ON bp.id = bpi.partner_id
        LEFT JOIN business_partner_categories c ON c.id = bp.category_id
        WHERE bpi.itinerary_id = :itinerary_id
          AND bp.status = 'published'
          AND (bp.location LIKE :loc1 OR bp.location LIKE :loc2 OR bp.location LIKE :loc3)
        ORDER BY bp.is_featured DESC, bp.sort_order ASC
        LIMIT {$limit}
    ", [
        'itinerary_id' => $itineraryId,
        'loc1' => $loc,
        'loc2' => '%' . $location . '%',
        'loc3' => '%' . strtoupper(substr($location, 0, 1)) . strtolower(substr($location, 1)) . '%',
    ]);
}

function day_range_label(int $startDay, ?int $endDay = null): string
{
    $startDay = max(1, $startDay);
    $endDay = max($startDay, (int) ($endDay ?? $startDay));

    if ($endDay === $startDay) {
        return 'Day ' . $startDay;
    }

    return 'Day ' . $startDay . '-' . $endDay;
}

function find_published_country_page_by_slug(string $slug): ?array
{
    if (!db_table_exists('country_pages')) {
        return null;
    }

    return fetch_one(
        "SELECT * FROM country_pages WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function find_published_static_page_by_slug(string $slug): ?array
{
    require_once __DIR__ . '/retired_package_pages.php';
    if (package_reference_path_retired($slug)) { return null; }
    if (!db_table_exists('static_pages')) {
        return null;
    }

    return fetch_one(
        "SELECT * FROM static_pages WHERE slug = :slug AND is_published = 1 LIMIT 1",
        ['slug' => $slug]
    );
}

function excerpt_words(string $text, int $limit = 22): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

    if ($text === '' || $limit < 1) {
        return '';
    }

    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if (count($words) <= $limit) {
        return $text;
    }

    return implode(' ', array_slice($words, 0, $limit)) . '...';
}

function is_placeholder_text(?string $text): bool
{
    $value = strtolower(trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)) ?? ''));

    if ($value === '') {
        return true;
    }

    $exact = [
        'sample',
        'test',
        'text',
        'placeholder',
        'coming soon',
        'tbd',
        'lorem ipsum',
        'demo',
        'draft',
        'under construction',
    ];

    if (in_array($value, $exact, true)) {
        return true;
    }

    $patterns = [
        '/^(sample|test|text)\b/i',
        '/\b(lorem ipsum|placeholder|under construction|coming soon)\b/i',
        '/^\[.*\]$/',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $value)) {
            return true;
        }
    }

    return false;
}

function clean_public_text(?string $text, string $fallback = ''): string
{
    return is_placeholder_text($text) ? $fallback : trim((string) $text);
}

function sanitize_public_html(string $html): string
{
    $html = preg_replace('~<(script|style|iframe|object|embed|form|input|button)[^>]*>.*?</\1>~is', '', $html) ?? '';
    $allowedTags = '<p><br><ul><ol><li><strong><em><b><i><u><s><h2><h3><h4><blockquote><a><img><figure><figcaption><video><source><span>';
    $html = strip_tags($html, $allowedTags);

    return preg_replace_callback('~<([a-z0-9]+)([^>]*)>~i', static function (array $match): string {
        $tag = strtolower($match[1]);
        $attrs = (string) ($match[2] ?? '');
        $allowed = [
            'a' => ['href', 'title', 'target', 'rel', 'class', 'data-preview-type', 'data-preview-id'],
            'img' => ['src', 'alt', 'title', 'class', 'loading', 'width', 'height'],
            'video' => ['src', 'poster', 'class', 'controls', 'muted', 'loop', 'playsinline', 'preload'],
            'source' => ['src', 'type'],
            'span' => ['class'],
            'figure' => ['class'],
            'figcaption' => ['class'],
            'p' => ['class'],
            'blockquote' => ['class'],
        ];
        if (!isset($allowed[$tag])) {
            return '<' . $tag . '>';
        }

        $cleanAttrs = [];
        if (preg_match_all('~([a-zA-Z0-9:_-]+)(?:\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s"\'>]+)))?~', $attrs, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $attr) {
                $name = strtolower($attr[1]);
                if (!in_array($name, $allowed[$tag], true)) {
                    continue;
                }
                $value = $attr[3] ?? $attr[4] ?? $attr[5] ?? '';
                if (in_array($name, ['href', 'src', 'poster'], true)) {
                    $trimmed = trim((string) $value);
                    if ($trimmed !== '' && !preg_match('~^(https?:)?//|^/|^[a-z0-9_\-/]+\.(jpg|jpeg|png|webp|gif|avif|mp4|webm|mov)$|^assets/|^uploads/~i', $trimmed)) {
                        continue;
                    }
                    if (preg_match('~^\s*javascript:~i', $trimmed)) {
                        continue;
                    }
                }
                if ($name === 'target' && $value !== '_blank') {
                    continue;
                }
                if ($name === 'rel') {
                    $value = 'noopener';
                }
                $cleanAttrs[] = $name . ($value !== '' ? '="' . e($value) . '"' : '');
            }
        }

        if ($tag === 'a' && !preg_grep('/^rel=/', $cleanAttrs)) {
            $cleanAttrs[] = 'rel="noopener"';
        }
        if ($tag === 'img' && !preg_grep('/^loading=/', $cleanAttrs)) {
            $cleanAttrs[] = 'loading="lazy"';
        }

        return '<' . $tag . ($cleanAttrs ? ' ' . implode(' ', $cleanAttrs) : '') . '>';
    }, $html) ?? '';
}

/**
 * Render public CMS text as safe-ish HTML:
 * - plain text => escaped + nl2br
 * - rich text with known tags => strips unknown tags and renders allowed tags
 */
function render_public_content(?string $text, string $fallback = ''): string
{
    require_once __DIR__ . '/retired_package_pages.php';
    $clean = package_reference_rewrite_links(clean_public_text($text, $fallback));
    if ($clean === '') {
        return '';
    }

    $hasHtmlTags = preg_match('/<[^>]+>/', $clean) === 1;
    if (!$hasHtmlTags) {
        return nl2br(e($clean));
    }

    return sanitize_public_html($clean);
}

function clean_public_faq_items(array $items): array
{
    $clean = [];

    foreach ($items as $item) {
        $question = trim((string) ($item['question'] ?? $item['q'] ?? ''));
        $answer = trim((string) ($item['answer'] ?? $item['a'] ?? ''));

        if (is_placeholder_text($question) || is_placeholder_text($answer)) {
            continue;
        }

        $clean[] = [
            'question' => $question,
            'answer' => $answer,
        ];
    }

    return $clean;
}

function decode_faq_json_items(?string $faqJson): array
{
    $faqJson = trim((string) $faqJson);
    if ($faqJson === '') {
        return [];
    }

    $decoded = json_decode($faqJson, true);
    if (!is_array($decoded)) {
        return [];
    }

    return clean_public_faq_items($decoded);
}

function ensure_faq_entries_table(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    if (!db_table_exists('faq_entries')) {
        db()->exec("
            CREATE TABLE IF NOT EXISTS faq_entries (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                question VARCHAR(255) NOT NULL,
                answer LONGTEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_published TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_faq_entries_published (is_published),
                KEY idx_faq_entries_sort (sort_order, id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    $ready = true;
}

function fetch_published_faq_entries(): array
{
    ensure_faq_entries_table();

    return fetch_all("
        SELECT id, question, answer, sort_order
        FROM faq_entries
        WHERE is_published = 1
        ORDER BY sort_order ASC, id ASC
    ");
}

function ensure_faq_static_page_record(): array
{
    ensure_static_pages_extended_schema();

    $record = fetch_one("SELECT * FROM static_pages WHERE slug = 'faq' LIMIT 1");
    if ($record) {
        return $record;
    }

    execute_query(
        "INSERT INTO static_pages
        (title, slug, content, cta_title, cta_text, sort_order, is_published)
        VALUES
        (:title, :slug, :content, :cta_title, :cta_text, :sort_order, :is_published)",
        [
            'title' => 'FAQ',
            'slug' => 'faq',
            'content' => 'Frequently asked questions about our private Sri Lanka tours.',
            'cta_title' => 'Need a Personalized Answer?',
            'cta_text' => 'Send us your dates and interests. We will recommend the best route and vehicle plan.',
            'sort_order' => 0,
            'is_published' => 1,
        ]
    );

    return fetch_one("SELECT * FROM static_pages WHERE id = :id LIMIT 1", ['id' => (int) last_insert_id()]) ?: [];
}

function ensure_analytics_events_table(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS analytics_events (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_type VARCHAR(80) NOT NULL,
            path VARCHAR(500) NOT NULL DEFAULT '',
            target_url VARCHAR(700) NOT NULL DEFAULT '',
            target_group VARCHAR(120) NOT NULL DEFAULT '',
            ip_address VARCHAR(45) NOT NULL DEFAULT '',
            user_agent VARCHAR(300) NOT NULL DEFAULT '',
            referrer VARCHAR(500) NOT NULL DEFAULT '',
            meta_json LONGTEXT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_analytics_events_type (event_type),
            KEY idx_analytics_events_created (created_at),
            KEY idx_analytics_events_path (path(200)),
            KEY idx_analytics_events_target_group (target_group)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    if (!db_column_exists('analytics_events', 'target_group')) {
        try {
            db()->exec("ALTER TABLE analytics_events ADD COLUMN target_group VARCHAR(120) NOT NULL DEFAULT '' AFTER target_url");
        } catch (Throwable) {
        }
    }

    if (!db_index_exists('analytics_events', 'idx_analytics_events_target_group')) {
        try {
            db()->exec('ALTER TABLE analytics_events ADD KEY idx_analytics_events_target_group (target_group)');
        } catch (Throwable) {
        }
    }

    ensure_analytics_configuration_schema();
    $ready = true;
}

function ensure_analytics_configuration_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS analytics_event_types (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            event_key VARCHAR(80) NOT NULL,
            label VARCHAR(120) NOT NULL,
            description VARCHAR(255) NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            retention_days INT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_analytics_event_types_key (event_key),
            KEY idx_analytics_event_types_active (is_active),
            KEY idx_analytics_event_types_sort (sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS analytics_target_rules (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            rule_name VARCHAR(120) NOT NULL,
            match_type VARCHAR(20) NOT NULL DEFAULT 'contains',
            match_value VARCHAR(255) NOT NULL,
            group_label VARCHAR(120) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_analytics_target_rules_active (is_active),
            KEY idx_analytics_target_rules_sort (sort_order, id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS analytics_settings (
            setting_key VARCHAR(120) PRIMARY KEY,
            setting_value LONGTEXT NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $defaultEvents = [
        ['whatsapp_click', 'WhatsApp Click', 'User clicked a WhatsApp contact link.', 1],
        ['phone_click', 'Phone Click', 'User clicked a tel: phone link.', 2],
        ['email_click', 'Email Click', 'User clicked a mailto: link.', 3],
        ['contact_cta_click', 'Contact CTA Click', 'User clicked a primary contact/quote CTA.', 4],
        ['sticky_cta_click', 'Sticky CTA Click', 'User clicked a sticky mobile CTA action (WhatsApp/Call/Quote).', 5],
        ['external_link_click', 'External Link Click', 'User clicked a non-site external URL.', 6],
    ];

    foreach ($defaultEvents as $event) {
        execute_query(
            "INSERT IGNORE INTO analytics_event_types
            (event_key, label, description, is_active, sort_order)
            VALUES
            (:event_key, :label, :description, 1, :sort_order)",
            [
                'event_key' => $event[0],
                'label' => $event[1],
                'description' => $event[2],
                'sort_order' => $event[3],
            ]
        );
    }

    $defaultRules = [
        ['WhatsApp Links', 'contains', 'wa.me/', 'WhatsApp', 10],
        ['Phone Links', 'starts_with', 'tel:', 'Phone', 20],
        ['Email Links', 'starts_with', 'mailto:', 'Email', 30],
        ['Contact Page', 'contains', '/contact', 'Contact Page', 40],
        ['Blog Links', 'contains', '/blog', 'Blog', 50],
    ];

    foreach ($defaultRules as $rule) {
        execute_query(
            "INSERT INTO analytics_target_rules
            (rule_name, match_type, match_value, group_label, is_active, sort_order)
            SELECT :rule_name, :match_type, :match_value, :group_label, 1, :sort_order
            WHERE NOT EXISTS (
                SELECT 1 FROM analytics_target_rules
                WHERE rule_name = :rule_name_exists
                LIMIT 1
            )",
            [
                'rule_name' => $rule[0],
                'match_type' => $rule[1],
                'match_value' => $rule[2],
                'group_label' => $rule[3],
                'sort_order' => $rule[4],
                'rule_name_exists' => $rule[0],
            ]
        );
    }

    execute_query(
        "INSERT INTO analytics_settings (setting_key, setting_value)
         SELECT 'events_retention_days', '365'
         WHERE NOT EXISTS (
             SELECT 1 FROM analytics_settings WHERE setting_key = 'events_retention_days'
         )"
    );
    execute_query(
        "INSERT INTO analytics_settings (setting_key, setting_value)
         SELECT 'google_tag_manager_id', 'GTM-56SFZG95'
         WHERE NOT EXISTS (
             SELECT 1 FROM analytics_settings WHERE setting_key = 'google_tag_manager_id'
         )"
    );
    execute_query(
        "INSERT INTO analytics_settings (setting_key, setting_value)
         SELECT 'google_tag_manager_enabled', '1'
         WHERE NOT EXISTS (
             SELECT 1 FROM analytics_settings WHERE setting_key = 'google_tag_manager_enabled'
         )"
    );

    $ready = true;
}

function analytics_setting_get(string $key, ?string $default = null): ?string
{
    ensure_analytics_configuration_schema();

    $row = fetch_one(
        "SELECT setting_value FROM analytics_settings WHERE setting_key = :setting_key LIMIT 1",
        ['setting_key' => $key]
    );

    if (!$row) {
        return $default;
    }

    return (string) ($row['setting_value'] ?? $default);
}

function analytics_setting_set(string $key, string $value): void
{
    ensure_analytics_configuration_schema();
    execute_query(
        "INSERT INTO analytics_settings (setting_key, setting_value)
         VALUES (:setting_key, :setting_value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [
            'setting_key' => $key,
            'setting_value' => $value,
        ]
    );
}

function google_tag_manager_container_id(): string
{
    ensure_analytics_configuration_schema();

    $envId = trim((string) (getenv('GTM_CONTAINER_ID') ?: getenv('GOOGLE_TAG_MANAGER_ID') ?: ''));
    $settingId = trim((string) analytics_setting_get('google_tag_manager_id', 'GTM-56SFZG95'));
    $containerId = strtoupper($envId !== '' ? $envId : $settingId);
    $enabled = $envId !== '' || analytics_setting_get('google_tag_manager_enabled', '1') === '1';

    if (!$enabled || !preg_match('/^GTM-[A-Z0-9]+$/', $containerId)) {
        return '';
    }

    return $containerId;
}

function render_google_tag_manager_head(): void
{
    if (!ict_analytics_allowed('gtm')) { return; }
    $containerId = google_tag_manager_container_id();
    if ($containerId === '') {
        return;
    }
    ?>
<!-- Google Tag Manager -->
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('consent', 'default', {analytics_storage:'granted', ad_storage:'denied', ad_user_data:'denied', ad_personalization:'denied'});
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?= e($containerId) ?>');
</script>
<!-- End Google Tag Manager -->
    <?php
}

function render_google_tag_manager_body(): void
{
    // The noscript container cannot receive the advertising-denied JS defaults.
    // Keep this compatibility hook silent; C1 permits only the guarded JS loader.
    return;
}

function google_tag_event_queue(string $eventName, array $params = []): void
{
    if (!ict_analytics_allowed('gtm')) { unset($_SESSION['google_tag_events']); return; }
    $allowed = ['generate_lead', 'itinerary_plan_started', 'itinerary_plan_opened', 'trip_type_selected', 'customer_message_submit', 'change_request_submit'];
    if (!in_array($eventName, $allowed, true)) { return; }
    $state = ict_privacy_state();
    $payload = ['event' => $eventName, 'source_page' => ict_analytics_request_group()];
    if (in_array($params['lead_type'] ?? null, ['tour_inquiry', 'itinerary_customizer'], true)) { $payload['lead_type'] = $params['lead_type']; }
    if (is_bool($params['preferred_itinerary_selected'] ?? null)) { $payload['preferred_itinerary_selected'] = $params['preferred_itinerary_selected']; }
    $_SESSION['google_tag_events'] = $_SESSION['google_tag_events'] ?? [];
    if (is_array($_SESSION['google_tag_events']) && count($_SESSION['google_tag_events']) < 20) {
        $_SESSION['google_tag_events'][] = ['choice_token' => $state['choice_token'], 'revision' => ict_privacy_settings()['purpose_revision'], 'payload' => $payload];
    }
}

function render_google_tag_queued_events(): void
{
    $events = $_SESSION['google_tag_events'] ?? [];
    unset($_SESSION['google_tag_events']);
    if (!is_array($events) || !ict_analytics_allowed('gtm') || google_tag_manager_container_id() === '') { return; }
    $state = ict_privacy_state();
    $revision = ict_privacy_settings()['purpose_revision'];
    $events = array_values(array_filter($events, static fn($entry): bool => is_array($entry)
        && ($entry['choice_token'] ?? '') === $state['choice_token'] && ($entry['revision'] ?? null) === $revision
        && is_array($entry['payload'] ?? null) && in_array($entry['payload']['event'] ?? '', ['generate_lead', 'itinerary_plan_started', 'itinerary_plan_opened', 'trip_type_selected', 'customer_message_submit', 'change_request_submit'], true)));
    if (!$events) { return; }
    ?>
<script>
window.dataLayer = window.dataLayer || [];
<?php foreach ($events as $entry): ?>
window.dataLayer.push(<?= json_encode($entry['payload'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>);
<?php endforeach; ?>
</script>
    <?php
}
function fetch_analytics_event_types(bool $activeOnly = true): array
{
    ensure_analytics_configuration_schema();

    $where = $activeOnly ? 'WHERE is_active = 1' : '';
    return fetch_all("
        SELECT id, event_key, label, description, is_active, sort_order, retention_days
        FROM analytics_event_types
        {$where}
        ORDER BY sort_order ASC, id ASC
    ");
}

function analytics_event_allowed(string $eventType): bool
{
    $eventType = strtolower(trim($eventType));
    if ($eventType === '') {
        return false;
    }

    $row = fetch_one(
        "SELECT id
         FROM analytics_event_types
         WHERE event_key = :event_key
           AND is_active = 1
         LIMIT 1",
        ['event_key' => $eventType]
    );

    return $row !== null;
}

function analytics_target_group(string $targetUrl): string
{
    $targetUrl = trim($targetUrl);
    if ($targetUrl === '') {
        return '';
    }

    ensure_analytics_configuration_schema();

    $rules = fetch_all("
        SELECT match_type, match_value, group_label
        FROM analytics_target_rules
        WHERE is_active = 1
        ORDER BY sort_order ASC, id ASC
    ");

    $targetLower = strtolower($targetUrl);
    $targetHost = strtolower((string) parse_url($targetUrl, PHP_URL_HOST));
    $targetPath = strtolower((string) parse_url($targetUrl, PHP_URL_PATH));

    foreach ($rules as $rule) {
        $matchType = strtolower(trim((string) ($rule['match_type'] ?? 'contains')));
        $matchValue = strtolower(trim((string) ($rule['match_value'] ?? '')));
        $groupLabel = trim((string) ($rule['group_label'] ?? ''));

        if ($matchValue === '' || $groupLabel === '') {
            continue;
        }

        $isMatch = false;
        if ($matchType === 'contains') {
            $isMatch = str_contains($targetLower, $matchValue);
        } elseif ($matchType === 'starts_with') {
            $isMatch = str_starts_with($targetLower, $matchValue);
        } elseif ($matchType === 'exact') {
            $isMatch = $targetLower === $matchValue;
        } elseif ($matchType === 'domain') {
            $isMatch = $targetHost !== '' && $targetHost === $matchValue;
        } elseif ($matchType === 'path_prefix') {
            $isMatch = $targetPath !== '' && str_starts_with($targetPath, $matchValue);
        }

        if ($isMatch) {
            return substr($groupLabel, 0, 120);
        }
    }

    return 'Other';
}

/** C1 pauses disposal; explicit hold administration/review belongs to C4. */
function run_analytics_retention_cleanup(): void { return; }

function track_analytics_event(string $eventType, array $meta = []): void
{
    if (!ict_analytics_allowed() || !in_array($eventType, ict_analytics_click_keys(), true)) { return; }
    try {
        if (!db_table_exists('analytics_events') || !db_table_exists('analytics_event_types') || !analytics_event_allowed($eventType)) { return; }
        execute_query(
            'INSERT INTO analytics_events (event_type, path, target_url, target_group, ip_address, user_agent, referrer, meta_json)
             VALUES (:event, :path, :target, :group, :ip, :ua, :ref, :meta)',
            ['event' => $eventType, 'path' => ict_webstats_page_path(), 'target' => '', 'group' => ict_analytics_target_group($eventType),
             'ip' => '', 'ua' => '', 'ref' => '', 'meta' => null]
        );
    } catch (Throwable) { /* Analytics cannot interrupt the visitor's requested action. */ }
}
function fetch_active_services(): array
{
    return fetch_all(
        "SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order ASC, id ASC"
    );
}

function fetch_active_social_links(): array
{
    if (!db_table_exists('social_links')) {
        return [];
    }

    return fetch_all("
        SELECT id, slot_key, label, url, icon_text, icon_image, sort_order, is_custom
        FROM social_links
        WHERE is_active = 1
          AND COALESCE(TRIM(url), '') <> ''
        ORDER BY sort_order ASC, id ASC
    ");
}

function ensure_social_marketing_schema(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS media_assets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            mime_type VARCHAR(120) NOT NULL,
            file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
            alt_text VARCHAR(255) NULL,
            seo_title VARCHAR(255) NULL,
            seo_description VARCHAR(320) NULL,
            tags VARCHAR(255) NULL,
            source_type VARCHAR(40) NULL,
            source_url VARCHAR(500) NULL,
            destination_id BIGINT UNSIGNED NULL,
            uploaded_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_media_assets_created (created_at),
            KEY idx_media_assets_uploaded_by (uploaded_by),
            KEY idx_media_assets_destination (destination_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $hasDestinationColumn = (int) (fetch_one(
        "SELECT COUNT(*) AS total
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'media_assets'
           AND COLUMN_NAME = 'destination_id'"
    )['total'] ?? 0);
    if ($hasDestinationColumn === 0) {
        db()->exec(
            "ALTER TABLE media_assets
             ADD COLUMN destination_id BIGINT UNSIGNED NULL AFTER tags,
             ADD INDEX idx_media_assets_destination (destination_id)"
        );
    }

    if (!db_column_exists('media_assets', 'source_type')) {
        db()->exec("ALTER TABLE media_assets ADD COLUMN source_type VARCHAR(40) NULL AFTER tags");
    }
    if (!db_column_exists('media_assets', 'source_url')) {
        db()->exec("ALTER TABLE media_assets ADD COLUMN source_url VARCHAR(500) NULL AFTER source_type");
    }

    db()->exec("
        CREATE TABLE IF NOT EXISTS social_media_posts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NULL,
            reference_destination_id BIGINT UNSIGNED NULL,
            reference_itinerary_id BIGINT UNSIGNED NULL,
            reference_tour_id BIGINT UNSIGNED NULL,
            experience_name VARCHAR(180) NULL,
            platform_targets_json LONGTEXT NULL,
            hashtags VARCHAR(255) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'draft',
            scheduled_at DATETIME NULL,
            published_at DATETIME NULL,
            blog_post_id BIGINT UNSIGNED NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_social_media_posts_status (status),
            KEY idx_social_media_posts_scheduled (scheduled_at),
            KEY idx_social_media_posts_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS social_media_post_assets (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            asset_id BIGINT UNSIGNED NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_social_media_post_asset (post_id, asset_id),
            KEY idx_social_media_post_assets_post (post_id, sort_order),
            KEY idx_social_media_post_assets_asset (asset_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS social_publish_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED NOT NULL,
            platform VARCHAR(40) NOT NULL,
            publish_status VARCHAR(40) NOT NULL,
            response_json LONGTEXT NULL,
            error_message TEXT NULL,
            retry_count INT UNSIGNED NOT NULL DEFAULT 0,
            attempted_by BIGINT UNSIGNED NULL,
            attempted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_social_publish_logs_post (post_id, attempted_at),
            KEY idx_social_publish_logs_platform (platform, attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    db()->exec("
        CREATE TABLE IF NOT EXISTS social_platform_connections (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            platform VARCHAR(40) NOT NULL,
            auth_mode VARCHAR(30) NOT NULL DEFAULT 'manual',
            account_label VARCHAR(150) NULL,
            access_token TEXT NULL,
            refresh_token TEXT NULL,
            token_expires_at DATETIME NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_social_platform_connections_platform (platform)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $ready = true;
}

function social_media_platform_options(): array
{
    return [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'pinterest' => 'Pinterest',
        'blog' => 'Blog',
    ];
}

function decode_json_array(?string $value): array
{
    $value = trim((string) $value);
    if ($value === '') {
        return [];
    }

    $decoded = json_decode($value, true);
    return is_array($decoded) ? $decoded : [];
}

function format_file_size(int $bytes): string
{
    $bytes = max(0, $bytes);
    $units = ['B', 'KB', 'MB', 'GB'];
    $index = 0;
    $size = (float) $bytes;

    while ($size >= 1024 && $index < count($units) - 1) {
        $size /= 1024;
        $index++;
    }

    return number_format($size, $index === 0 ? 0 : 1) . ' ' . $units[$index];
}

function fetch_public_review_summary(): array
{
    static $summary = null;

    if (is_array($summary)) {
        return $summary;
    }

    $summary = [
        'count' => 0,
        'average' => 0.0,
    ];

    if (!db_table_exists('reviews')) {
        return $summary;
    }
    ensure_reviews_schema();

    $row = fetch_one("
        SELECT COUNT(*) AS review_count, AVG(rating) AS average_rating
        FROM reviews
        WHERE is_published = 1
          AND customer_name NOT LIKE '%test%'
          AND customer_name NOT LIKE '%Test%'
          AND customer_name NOT LIKE '%remove%'
          AND customer_name NOT LIKE '%Remove%'
    ");

    if ($row) {
        $summary['count'] = max(0, (int) ($row['review_count'] ?? 0));
        $summary['average'] = (float) ($row['average_rating'] ?? 0.0);
    }

    return $summary;
}

function site_experience_years(): int
{
    static $years = null;

    if (is_int($years)) {
        return $years;
    }

    $thisYear = (int) date('Y');
    $startYear = defined('SITE_EXPERIENCE_START_YEAR') ? (int) SITE_EXPERIENCE_START_YEAR : 2018;

    if ($startYear <= 0 || $startYear > $thisYear) {
        $startYear = $thisYear;
    }

    $years = max(1, ($thisYear - $startYear) + 1);

    return $years;
}

function pricing_select_fragment(string $table): string
{
    $priceFrom = db_column_exists($table, 'price_from') ? 'price_from' : 'NULL AS price_from';
    $priceTo = db_column_exists($table, 'price_to') ? 'price_to' : 'NULL AS price_to';
    $priceCurrency = db_column_exists($table, 'price_currency') ? 'price_currency' : "'USD' AS price_currency";
    $priceUnit = db_column_exists($table, 'price_unit') ? 'price_unit' : "NULL AS price_unit";

    return "{$priceFrom}, {$priceTo}, {$priceCurrency}, {$priceUnit}";
}

function normalize_price_input(mixed $value): ?float
{
    if ($value === null) {
        return null;
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return null;
    }

    $clean = preg_replace('/[^0-9.]/', '', str_replace(',', '', $raw)) ?? '';
    if ($clean === '' || !is_numeric($clean)) {
        return null;
    }

    $number = (float) $clean;

    return $number >= 0 ? $number : null;
}

function format_price_amount(float $amount): string
{
    if ((int) $amount === (float) $amount) {
        return number_format($amount, 0, '.', ',');
    }

    return number_format($amount, 2, '.', ',');
}

function format_price_range(array $record, bool $withPrefix = true): string
{
    $priceFrom = isset($record['price_from']) ? (float) $record['price_from'] : 0.0;
    $priceTo = isset($record['price_to']) ? (float) $record['price_to'] : 0.0;
    $hasFrom = $priceFrom > 0;
    $hasTo = $priceTo > 0;

    if (!$hasFrom && !$hasTo) {
        return '';
    }

    $currency = strtoupper(trim((string) ($record['price_currency'] ?? 'USD')));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        $currency = 'USD';
    }

    $unit = trim((string) ($record['price_unit'] ?? ''));
    $range = '';

    if ($hasFrom && $hasTo && $priceTo >= $priceFrom) {
        $range = $currency . ' ' . format_price_amount($priceFrom) . ' - ' . $currency . ' ' . format_price_amount($priceTo);
    } elseif ($hasFrom) {
        $range = $currency . ' ' . format_price_amount($priceFrom);
    } else {
        $range = $currency . ' ' . format_price_amount($priceTo);
    }

    if ($withPrefix) {
        $range = ($hasFrom ? 'Starting from ' : 'Up to ') . $range;
    }

    if ($unit !== '') {
        $range .= ' ' . $unit;
    }

    return trim($range);
}

function fetch_published_blog_posts(int $limit = 0, int $offset = 0): array
{
    $sql = "SELECT *
            FROM blog_posts
            WHERE is_published = 1
              AND (published_at IS NULL OR published_at <= NOW())
            ORDER BY COALESCE(published_at, created_at) DESC, id DESC";
    if ($limit > 0) {
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
    }
    return fetch_all($sql);
}

function count_published_blog_posts(): int
{
    $row = fetch_one("
        SELECT COUNT(*) AS cnt
        FROM blog_posts
        WHERE is_published = 1
          AND (published_at IS NULL OR published_at <= NOW())
    ");
    return (int) ($row['cnt'] ?? 0);
}

function record_page_view(): void
{
    if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET'
        || http_response_code() >= 400 || !ict_analytics_allowed()) { return; }
    try {
        if (!db_table_exists('page_views')) { return; }
        $country = request_country_code();
        $params = ['path' => ict_webstats_page_path(), 'ip' => '', 'country' => $country === 'Unknown' ? '' : $country, 'ref' => ict_webstats_referrer(), 'ua' => ''];
        $metadata = ict_webstats_metadata((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (ict_webstats_has_metadata_columns()) {
            execute_query('INSERT INTO page_views (path, ip_address, country, referrer, user_agent, visitor_key, visit_key, device_type, browser_name, os_name) VALUES (:path, :ip, :country, :ref, :ua, :visitor_key, :visit_key, :device_type, :browser_name, :os_name)', $params + ict_webstats_identity() + $metadata);
        } elseif (ict_webstats_has_identity_columns()) {
            execute_query('INSERT INTO page_views (path, ip_address, country, referrer, user_agent, visitor_key, visit_key) VALUES (:path, :ip, :country, :ref, :ua, :visitor_key, :visit_key)', $params + ict_webstats_identity());
        } else {
            execute_query('INSERT INTO page_views (path, ip_address, country, referrer, user_agent) VALUES (:path, :ip, :country, :ref, :ua)', $params);
        }
    } catch (Throwable $exception) {
        error_log('First-party page-view write failed; code=' . $exception->getCode());
    }
}
function request_country_code(): string
{
    $candidates = [
        $_SERVER['HTTP_CF_IPCOUNTRY'] ?? null,
        $_SERVER['HTTP_X_COUNTRY_CODE'] ?? null,
        $_SERVER['HTTP_GEOIP_COUNTRY_CODE'] ?? null,
        $_SERVER['HTTP_X_GEO_COUNTRY'] ?? null,
        $_SERVER['GEOIP_COUNTRY_CODE'] ?? null,
    ];

    foreach ($candidates as $value) {
        $value = strtoupper(trim((string) $value));
        if ($value === '' || $value === 'XX' || $value === 'ZZ' || $value === 'UNKNOWN') {
            continue;
        }

        if (preg_match('/^[A-Z]{2}$/', $value) === 1) {
            return $value;
        }
    }

    return 'Unknown';
}

