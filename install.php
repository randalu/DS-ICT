<?php
declare(strict_types=1);

/**
 * Island Culture Travels — One-Time Installer
 *
 * Visit /install.php in a browser.
 * After a successful install this file self-deletes.
 * Includes all schema + seeded data; existing rows are skipped (INSERT IGNORE).
 */

// ── Lock guard ───────────────────────────────────────────────────────────────
define('LOCK_FILE', __DIR__ . '/install.lock');

if (file_exists(LOCK_FILE)) {
    http_response_code(410);
    die('<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Already Installed</title>
<style>body{font-family:sans-serif;max-width:540px;margin:80px auto;text-align:center;color:#334155}
h2{color:#0f766e}a{color:#0f766e}</style><link rel="stylesheet" href="assets/css/typography.css"></head>
<body><h2>Already Installed</h2>
<p>This installer has already completed. The lock file <code>install.lock</code> exists.</p>
<p><a href="/">Go to the site →</a></p></body></html>');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ── Helpers ──────────────────────────────────────────────────────────────────
function installer_h(string $v): string
{
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

function installer_old(string $key, string $default = ''): string
{
    return installer_h((string) ($_POST[$key] ?? $default));
}

function installer_csrf_token(): string
{
    if (empty($_SESSION['_ict_install_csrf'])) {
        $_SESSION['_ict_install_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_ict_install_csrf'];
}

function installer_verify_csrf(?string $token): bool
{
    $sessionToken = $_SESSION['_ict_install_csrf'] ?? '';

    return is_string($token) && $sessionToken !== '' && hash_equals((string) $sessionToken, $token);
}

function installer_env_value(string $value): string
{
    return str_replace(["\r", "\n"], '', $value);
}

// ── Process form ──────────────────────────────────────────────────────────────
$errors  = [];
$success = false;
$log     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dbHost    = trim((string) ($_POST['db_host']    ?? '127.0.0.1'));
    $dbPort    = trim((string) ($_POST['db_port']    ?? '3306'));
    $dbName    = trim((string) ($_POST['db_name']    ?? ''));
    $dbUser    = trim((string) ($_POST['db_user']    ?? ''));
    $dbPass    = trim((string) ($_POST['db_pass']    ?? ''));
    $siteUrl   = rtrim(trim((string) ($_POST['site_url'] ?? '')), '/');
    $adminUser = trim((string) ($_POST['admin_user'] ?? 'admin'));
    $adminPass = trim((string) ($_POST['admin_pass'] ?? ''));
    $adminEmail= trim((string) ($_POST['admin_email']?? ''));
    $adminName = trim((string) ($_POST['admin_name'] ?? 'Admin'));
    $envWrite  = isset($_POST['write_env']);

    // Validate
    if (!installer_verify_csrf(is_string($_POST['_token'] ?? null) ? $_POST['_token'] : null)) {
        $errors[] = 'Invalid or expired installer token. Please refresh and try again.';
    }
    if ($dbName === '')    { $errors[] = 'Database name is required.'; }
    elseif (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) { $errors[] = 'Database name may contain only letters, numbers, and underscores.'; }
    if ($dbUser === '')    { $errors[] = 'Database username is required.'; }
    if ($dbPort === '' || !ctype_digit($dbPort) || (int) $dbPort < 1 || (int) $dbPort > 65535) {
        $errors[] = 'Database port must be a valid TCP port.';
    }
    if ($adminPass === '') { $errors[] = 'Admin password is required (min 8 chars).'; }
    elseif (strlen($adminPass) < 8) { $errors[] = 'Admin password must be at least 8 characters.'; }
    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $adminUser)) {
        $errors[] = 'Admin username must be 3-80 characters using letters, numbers, dots, underscores, or hyphens.';
    }
    if ($adminEmail !== '' && !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Admin email must be a valid email address.';
    }
    if ($siteUrl === '')   { $errors[] = 'Site URL is required (e.g. https://srilankaprivatetravels.com).'; }
    elseif (!filter_var($siteUrl, FILTER_VALIDATE_URL) || !in_array(parse_url($siteUrl, PHP_URL_SCHEME), ['http', 'https'], true)) {
        $errors[] = 'Site URL must be a valid http or https URL.';
    }

    if (!$errors) {
        try {
            // ── Connect to MySQL (create DB if missing) ─────────────────────
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $dbHost, $dbPort);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            $pdo->exec("SET NAMES utf8mb4");
            $pdo->exec("SET time_zone = '+05:30'");
            $log[] = '✔ Connected to MySQL and selected database <strong>' . installer_h($dbName) . '</strong>.';

            // ── Schema ──────────────────────────────────────────────────────
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

            $schema = <<<'SQL'
CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admins_email (email),
    UNIQUE KEY uq_admins_username (username),
    KEY idx_admins_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destinations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    why_visit LONGTEXT NULL,
    things_to_do LONGTEXT NULL,
    best_time LONGTEXT NULL,
    how_to_get_there LONGTEXT NULL,
    suggested_itinerary LONGTEXT NULL,
    where_to_stay LONGTEXT NULL,
    travel_tips LONGTEXT NULL,
    nearby_travel_times LONGTEXT NULL,
    works_well_with LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    province VARCHAR(200) NULL,
    temperature VARCHAR(60) NULL,
    destination_type VARCHAR(120) NULL,
    recommended_stay VARCHAR(80) NULL,
    hero_image VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_destinations_slug (slug),
    KEY idx_destinations_published (is_published),
    KEY idx_destinations_featured (is_featured),
    KEY idx_destinations_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    highlights LONGTEXT NULL,
    sample_itinerary LONGTEXT NULL,
    coverage_json LONGTEXT NULL,
    program_json LONGTEXT NULL,
    pricing_notes LONGTEXT NULL,
    price_from DECIMAL(10,2) NULL,
    price_to DECIMAL(10,2) NULL,
    price_currency CHAR(3) NOT NULL DEFAULT 'USD',
    price_unit VARCHAR(80) NULL,
    vehicle_options LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tours_slug (slug),
    KEY idx_tours_published (is_published),
    KEY idx_tours_featured (is_featured),
    KEY idx_tours_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itineraries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    days INT UNSIGNED NOT NULL DEFAULT 1,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    route_summary LONGTEXT NULL,
    nearby_travel_times LONGTEXT NULL,
    works_well_with LONGTEXT NULL,
    best_for LONGTEXT NULL,
    travel_notes LONGTEXT NULL,
    price_from DECIMAL(10,2) NULL,
    price_to DECIMAL(10,2) NULL,
    price_currency CHAR(3) NOT NULL DEFAULT 'USD',
    price_unit VARCHAR(80) NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itineraries_slug (slug),
    KEY idx_itineraries_days (days),
    KEY idx_itineraries_published (is_published),
    KEY idx_itineraries_featured (is_featured),
    KEY idx_itineraries_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_days (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    day_number INT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    summary LONGTEXT NULL,
    overnight_location VARCHAR(150) NULL,
    travel_time VARCHAR(100) NULL,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itinerary_days_unique_day (itinerary_id, day_number),
    KEY idx_itinerary_days_itinerary (itinerary_id),
    CONSTRAINT fk_itinerary_days_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(120) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    why_sri_lanka LONGTEXT NULL,
    recommended_itinerary LONGTEXT NULL,
    travel_info LONGTEXT NULL,
    best_time LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_pages_slug (slug),
    KEY idx_country_pages_published (is_published),
    KEY idx_country_pages_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS static_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    content LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_static_pages_slug (slug),
    KEY idx_static_pages_published (is_published),
    KEY idx_static_pages_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    country VARCHAR(120) NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    review_text TEXT NOT NULL,
    trip_type VARCHAR(120) NULL,
    tour_done_month CHAR(7) NULL,
    review_source VARCHAR(120) NULL,
    review_source_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reviews_published (is_published),
    KEY idx_reviews_featured (is_featured),
    KEY idx_reviews_sort (sort_order, id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    country VARCHAR(120) NULL,
    travel_dates VARCHAR(120) NULL,
    group_size VARCHAR(50) NULL,
    interests TEXT NULL,
    preferred_itinerary_id BIGINT UNSIGNED NULL,
    preferred_itinerary_name VARCHAR(180) NULL,
    message TEXT NOT NULL,
    source_page VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_inquiries_created (created_at),
    KEY idx_contact_inquiries_email (email),
    KEY idx_contact_inquiries_phone (phone),
    KEY idx_contact_inquiries_itinerary (preferred_itinerary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_related (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    related_destination_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_related_pair (destination_id, related_destination_id),
    KEY idx_destination_related_destination (destination_id),
    KEY idx_destination_related_related (related_destination_id),
    CONSTRAINT fk_destination_related_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    CONSTRAINT fk_destination_related_related
        FOREIGN KEY (related_destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    CONSTRAINT chk_destination_related_not_same CHECK (destination_id <> related_destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_tour (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_tour_pair (destination_id, tour_id),
    KEY idx_destination_tour_destination (destination_id),
    KEY idx_destination_tour_tour (tour_id),
    CONSTRAINT fk_destination_tour_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    CONSTRAINT fk_destination_tour_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_itinerary (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_itinerary_pair (destination_id, itinerary_id),
    KEY idx_destination_itinerary_destination (destination_id),
    KEY idx_destination_itinerary_itinerary (itinerary_id),
    CONSTRAINT fk_destination_itinerary_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE,
    CONSTRAINT fk_destination_itinerary_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_destination (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    destination_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_itinerary_destination_pair (itinerary_id, destination_id),
    KEY idx_itinerary_destination_itinerary (itinerary_id),
    KEY idx_itinerary_destination_destination (destination_id),
    CONSTRAINT fk_itinerary_destination_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE CASCADE,
    CONSTRAINT fk_itinerary_destination_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_tour (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_itinerary_tour_pair (itinerary_id, tour_id),
    KEY idx_itinerary_tour_itinerary (itinerary_id),
    KEY idx_itinerary_tour_tour (tour_id),
    CONSTRAINT fk_itinerary_tour_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id) ON DELETE CASCADE,
    CONSTRAINT fk_itinerary_tour_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(20) NOT NULL DEFAULT '',
    title VARCHAR(200) NOT NULL DEFAULT '',
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_services_sort (sort_order, id),
    KEY idx_services_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_key VARCHAR(80) NOT NULL,
    label VARCHAR(120) NOT NULL DEFAULT '',
    url VARCHAR(255) NULL,
    icon_text VARCHAR(20) NULL,
    icon_image VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_custom TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_social_links_slot (slot_key),
    KEY idx_social_links_sort (sort_order, id),
    KEY idx_social_links_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(300) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dest_photos_destination (destination_id),
    KEY idx_dest_photos_sort (destination_id, sort_order),
    CONSTRAINT fk_dest_photos_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_blog_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    title VARCHAR(250) NOT NULL,
    slug VARCHAR(280) NOT NULL,
    excerpt VARCHAR(500) NULL,
    content LONGTEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    author VARCHAR(120) NULL DEFAULT 'Island Culture Travels',
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sidebar_widgets_json LONGTEXT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_blog_posts_slug (slug),
    KEY idx_blog_posts_published (is_published),
    KEY idx_blog_posts_category (category_id),
    KEY idx_blog_posts_published_at (published_at),
    CONSTRAINT fk_blog_posts_category
        FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS seasonal_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('destination','tour') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    season_label VARCHAR(80) NOT NULL DEFAULT '',
    season_note TEXT NULL,
    KEY idx_seasonal_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    country VARCHAR(80) NOT NULL DEFAULT '',
    referrer VARCHAR(500) NOT NULL DEFAULT '',
    user_agent VARCHAR(300) NOT NULL DEFAULT '',
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pv_path (path(200)),
    KEY idx_pv_viewed_at (viewed_at),
    KEY idx_pv_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(80) NOT NULL,
    path VARCHAR(500) NOT NULL DEFAULT '',
    target_url VARCHAR(700) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(300) NOT NULL DEFAULT '',
    referrer VARCHAR(500) NOT NULL DEFAULT '',
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_analytics_events_type (event_type),
    KEY idx_analytics_events_created (created_at),
    KEY idx_analytics_events_path (path(200))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

            foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
                if ($stmt !== '') {
                    $pdo->exec($stmt);
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $log[] = '✔ Database tables created (or already exist).';

            // ── Seed data ───────────────────────────────────────────────────
            $seedStatements = [
                // Static pages
                "INSERT IGNORE INTO static_pages (title,slug,content,meta_title,meta_description,canonical_url,sort_order,is_published) VALUES
('About Us','about-us','Island Culture Travels creates private Sri Lanka journeys for couples, families, and small groups. We focus on flexible itineraries, comfortable vehicles, and practical travel planning across the island.','About Island Culture Travels — Private Chauffeur-Guided Tours','A professional private chauffeur-guided travel service offering safe vehicles, local expertise, and flexible itineraries across Sri Lanka.',NULL,1,1)",

                "INSERT IGNORE INTO static_pages (title,slug,content,meta_title,meta_description,canonical_url,sort_order,is_published) VALUES
('Why Choose Us','why-choose-us','Island Culture Travels focuses on private Sri Lanka journeys designed around real traveler needs rather than generic package tourism.\n\nWhy travelers choose us:\n\n• Private chauffeur and comfortable vehicle\n• Flexible route planning based on your dates and interests\n• Comfortable vehicles for couples, families, and small groups\n• Driver-guide support with local knowledge\n• Family-friendly pacing and practical itinerary planning\n• Easy communication before and during the trip\n• Customized travel support across Sri Lanka','Why Choose Island Culture Travels for Private Sri Lanka Tours','Discover why travelers choose Island Culture Travels — private vehicles, professional chauffeurs, flexible itineraries, and reliable service across Sri Lanka.',NULL,2,1)",

                "INSERT IGNORE INTO static_pages (title,slug,content,meta_title,meta_description,canonical_url,sort_order,is_published) VALUES
('FAQ','faq','Find answers to common questions about private tours, itinerary planning, family travel, airport pickup, and safari routes in Sri Lanka.','FAQ — Private Sri Lanka Tours with Chauffeur and Vehicle','Frequently asked questions about private Sri Lanka tours, chauffeur services, transport options, and itinerary planning.',NULL,3,1)",

                // FAQ entries
                "INSERT INTO faq_entries (question, answer, sort_order, is_published)
SELECT 'Can we customize the itinerary?', 'Yes. Every private tour can be adjusted based on your dates, interests, travel pace, and group size.', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM faq_entries WHERE question = 'Can we customize the itinerary?' LIMIT 1)",
                "INSERT INTO faq_entries (question, answer, sort_order, is_published)
SELECT 'Do you provide airport pickup?', 'Yes. Airport pickup and onward private travel support can be arranged.', 2, 1
WHERE NOT EXISTS (SELECT 1 FROM faq_entries WHERE question = 'Do you provide airport pickup?' LIMIT 1)",
                "INSERT INTO faq_entries (question, answer, sort_order, is_published)
SELECT 'Are your tours suitable for families?', 'Yes. We plan routes with sensible pacing for families, couples, and small groups.', 3, 1
WHERE NOT EXISTS (SELECT 1 FROM faq_entries WHERE question = 'Are your tours suitable for families?' LIMIT 1)",
                "INSERT INTO faq_entries (question, answer, sort_order, is_published)
SELECT 'Can we book a vehicle with driver only?', 'Yes. Depending on your plan, we can arrange private transport with driver support.', 4, 1
WHERE NOT EXISTS (SELECT 1 FROM faq_entries WHERE question = 'Can we book a vehicle with driver only?' LIMIT 1)",

                // Services
                "INSERT IGNORE INTO services (id, icon, title, description, sort_order, is_active) VALUES
(1, 'car', 'Private Tours & Transfers', '100% private travel in comfortable, air-conditioned vehicles with professional, friendly English-speaking drivers.', 1, 1),
(2, 'hotel', 'Hotel Reservations', 'We assist with hotel suggestions from boutique guesthouses to luxury resorts, personally vetted for quality and location.', 2, 1),
(3, 'guide', 'Expert Local Guidance', 'Driver-guides with deep local knowledge bringing landscapes, history, and culture to life throughout your journey.', 3, 1),
(4, 'plan', 'Custom Itinerary Planning', 'Free, no-obligation itinerary design built around your interests, pace, group size, and travel dates.', 4, 1),
(5, 'airport', 'Airport Transfers', 'Meet-and-greet at Bandaranaike International Airport, departure transfers, and inter-city transfers with reliable timing.', 5, 1),
(6, 'chat', 'WhatsApp Support', 'Our team is reachable on WhatsApp before and throughout your journey for practical updates and support.', 6, 1)",

                // Footer social links
                "INSERT IGNORE INTO social_links (slot_key, label, url, icon_text, icon_image, sort_order, is_active, is_custom) VALUES
('facebook', 'Facebook', NULL, 'f', NULL, 1, 1, 0),
('instagram', 'Instagram', NULL, 'ig', NULL, 2, 1, 0),
('youtube', 'YouTube', NULL, 'yt', NULL, 3, 1, 0),
('pinterest', 'Pinterest', NULL, 'p', NULL, 4, 1, 0),
('linkedin', 'LinkedIn', NULL, 'in', NULL, 5, 1, 0),
('tripadvisor', 'Tripadvisor', NULL, '*', NULL, 6, 1, 0),
('custom_1', 'Custom 1', NULL, '', NULL, 101, 0, 1),
('custom_2', 'Custom 2', NULL, '', NULL, 102, 0, 1),
('custom_3', 'Custom 3', NULL, '', NULL, 103, 0, 1),
('custom_4', 'Custom 4', NULL, '', NULL, 104, 0, 1),
('custom_5', 'Custom 5', NULL, '', NULL, 105, 0, 1)",

                // Tours
                "INSERT IGNORE INTO tours (name,slug,excerpt,intro,highlights,sample_itinerary,pricing_notes,vehicle_options,faq_json,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Private Sri Lanka Tour','private-sri-lanka-tour','Flexible private Sri Lanka tours with comfortable vehicles, driver-guides, and custom planning for couples and families.','Private Sri Lanka Tour is ideal for travelers who want to explore the island at their own pace with a private vehicle, local support, and a route tailored to their interests.','• Private vehicle with driver-guide\n• Custom route planning\n• Family-friendly travel pacing\n• Flexible start and end points','Day 1 arrival, Day 2–N cultural sites, scenic travel, beach or safari extensions depending on the route.','Pricing depends on group size, vehicle type, hotel category, and travel season.','• Sedan\n• Hybrid car\n• KDH van\n• Hiace van','[{\"question\":\"Can the route be customized?\",\"answer\":\"Yes. Every private tour can be adjusted to your dates, interests, and travel style.\"}]','Hire a Private Driver in Sri Lanka with Comfortable Vehicle Options | Island Culture Travels','Travel anywhere in Sri Lanka with a professional chauffeur, modern vehicle, and flexible itinerary designed around your travel plans.',NULL,1,1,1)",

                "INSERT IGNORE INTO tours (name,slug,excerpt,intro,highlights,sample_itinerary,pricing_notes,vehicle_options,faq_json,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Sri Lanka Family Tour','sri-lanka-family-tour','Family-friendly Sri Lanka tours with comfortable transport, sensible pacing, and private travel support.','Sri Lanka Family Tour is designed for parents, children, and multigenerational travelers who want a smoother private travel experience across the island.','• Family-friendly pacing\n• Comfortable vehicle options\n• Flexible sightseeing\n• Child-friendly route planning','Day 1 arrival, followed by a balanced mix of culture, scenery, wildlife, and relaxing overnight stops.','Pricing depends on family size, hotel level, and travel season.','• Sedan for small families\n• KDH van\n• Hiace van','[{\"question\":\"Are family tours customizable?\",\"answer\":\"Yes. We adjust the route, pace, and stop choices based on your family\'s needs.\"}]','Private Family Tour in Sri Lanka with Chauffeur and Comfortable Vehicle | Island Culture Travels','Travel safely with your family using spacious vehicles, flexible schedules, and a trusted private driver throughout your Sri Lanka holiday.',NULL,1,2,1)",

                "INSERT IGNORE INTO tours (name,slug,excerpt,intro,highlights,sample_itinerary,pricing_notes,vehicle_options,faq_json,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Sri Lanka Wildlife Safari Tour','sri-lanka-wildlife-safari-tour','Explore Sri Lanka\'s top wildlife parks with private transport, safari planning, and flexible travel.','Sri Lanka Wildlife Safari Tour is designed for travelers who want to experience the island\'s leading safari regions with the comfort of private transport and a well-paced route.','• Leopard and elephant safari regions\n• Private vehicle with driver-guide\n• Flexible multi-park planning\n• Family and couple friendly','Day 1 arrival and transfer, followed by safari-focused travel through one or more of Sri Lanka\'s top wildlife destinations.','Pricing depends on parks included, jeep charges, hotel category, and group size.','• Sedan\n• Hybrid car\n• KDH van\n• Hiace van','[{\"question\":\"Which parks can be included?\",\"answer\":\"This tour can include Yala, Udawalawe, Wilpattu, Minneriya, and other wildlife regions.\"}]','Private Wildlife Safari Tour in Sri Lanka with Experienced Driver Guide | Island Culture Travels','Explore Sri Lanka\'s national parks with safe private transport, early-morning departures, and knowledgeable local guidance.',NULL,1,3,1)",

                // Destinations
                "INSERT IGNORE INTO destinations (name,slug,excerpt,intro,why_visit,things_to_do,best_time,how_to_get_there,suggested_itinerary,where_to_stay,travel_tips,faq_json,cta_title,cta_text,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Yala','yala','Yala is Sri Lanka\'s most famous safari destination for leopard sightings, rich wildlife, and private safari travel.','Yala is one of Sri Lanka\'s best-known wildlife destinations and a popular choice for safari-focused private travel.','Yala is ideal for travelers who want classic Sri Lanka safari experiences with strong wildlife appeal and practical routing.','Jeep safari, wildlife viewing, leopard search, elephant sightings, nature stays, and southern route combinations.','Travel timing depends on season and route, but private planning helps make the most of the destination.','Yala is best reached by private vehicle from Ella, Tangalle, Udawalawe, or Colombo.','Pair Yala with Udawalawe, Ella, Tangalle, or Galle for a stronger multi-stop route.','Yala offers safari lodges, mid-range stays, and upscale nature-focused accommodation.','Use early safari timings, wear light clothing, and keep expectations realistic for wildlife sightings.','[{\"question\":\"What is Yala best known for?\",\"answer\":\"Yala is best known for wildlife safaris, especially leopard sightings.\"}]','Plan Your Yala Safari','We\'ll help you add Yala to a private Sri Lanka route with the right safari timing and transport.','Visit Yala with a Private Driver and Comfortable Vehicle | Island Culture Travels','Explore Yala National Park with a professional chauffeur guide, private safari planning, and flexible itinerary support.',NULL,1,5,1)",

                "INSERT IGNORE INTO destinations (name,slug,excerpt,intro,why_visit,things_to_do,best_time,how_to_get_there,suggested_itinerary,where_to_stay,travel_tips,faq_json,cta_title,cta_text,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Udawalawe','udawalawe','Udawalawe is one of the best safari destinations in Sri Lanka, especially for elephants and easy private wildlife travel.','Udawalawe is one of Sri Lanka\'s most accessible and rewarding safari destinations, especially for elephant-focused travel.','It is especially attractive for families and first-time safari travelers who want a practical wildlife stop.','Elephant safari, open-terrain wildlife viewing, countryside stays, and southern route combinations.','Udawalawe can work well year-round depending on route and weather patterns.','Udawalawe is easiest to reach by private vehicle from Colombo, Ella, Tangalle, or Yala.','Combine Udawalawe with Yala, Ella, Tangalle, or Colombo in a private route.','Accommodation ranges from budget lodges to more comfortable safari stays.','Use private transport and keep enough time between transfers and safaris.','[{\"question\":\"What is Udawalawe best known for?\",\"answer\":\"Udawalawe is best known for elephant safaris and open wildlife viewing.\"}]','Plan Your Udawalawe Safari','We\'ll help you include Udawalawe in a smooth and flexible wildlife itinerary.','Visit Udawalawe with a Private Driver and Comfortable Vehicle | Island Culture Travels','Explore Udawalawe National Park with a professional chauffeur guide, elephant safaris, and flexible private wildlife travel.',NULL,0,12,1)",

                "INSERT IGNORE INTO destinations (name,slug,excerpt,intro,why_visit,things_to_do,best_time,how_to_get_there,suggested_itinerary,where_to_stay,travel_tips,faq_json,cta_title,cta_text,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Ella','ella','Discover Ella\'s viewpoints, train scenery, hiking trails, and relaxed hill-country atmosphere in Sri Lanka.','Ella is one of Sri Lanka\'s most popular hill-country destinations, loved for its scenic beauty and relaxed mood.','Ella is ideal for travelers looking for cooler weather, viewpoints, tea-country scenery, and a slower pace.','Viewpoints, hiking, train journeys, tea-country scenery, waterfalls, and scenic drives.','Ella can be visited in different seasons depending on your broader route and weather preferences.','Ella is typically reached by private vehicle or train from Kandy, Nuwara Eliya, or the southern region.','Combine Ella with Nuwara Eliya, Yala, or Belihuloya in a scenic private journey.','Ella offers guesthouses, boutique hotels, and scenic hill-country stays.','Plan driving times carefully if combining Ella with safari destinations in a short trip.','[{\"question\":\"What is Ella best known for?\",\"answer\":\"Ella is best known for scenic hill-country views, hiking, and train-route appeal.\"}]','Plan Your Ella Trip','We\'ll help you include Ella in a smooth private Sri Lanka route.','Travel to Ella with a Private Driver and Scenic Mountain Routes | Island Culture Travels','Experience the beauty of Ella with reliable private transport, flexible travel timing, and expert local guidance throughout your journey.',NULL,1,2,1)",

                "INSERT IGNORE INTO destinations (name,slug,excerpt,intro,why_visit,things_to_do,best_time,how_to_get_there,suggested_itinerary,where_to_stay,travel_tips,faq_json,cta_title,cta_text,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Colombo','colombo','Colombo is Sri Lanka\'s commercial capital, ideal for city experiences, shopping, dining, and convenient arrival or departure stays.','Colombo is the main arrival point for many Sri Lanka trips and works well as both a starting and ending destination.','Travelers visit Colombo for city sightseeing, food, architecture, temples, shopping, and practical travel convenience.','City tours, food experiences, shopping malls, colonial buildings, temples, and waterfront areas.','Colombo can be visited year-round, especially as a flexible city stop before or after longer routes.','Colombo is easily reached from the airport and connects smoothly with the rest of the island by road.','Use Colombo as a short stop before heading inland or along the southern coast.','City hotels range from budget stays to premium business and leisure hotels.','Do not overload Colombo into a long route unless you specifically want city exploration.','[{\"question\":\"What is Colombo best known for?\",\"answer\":\"Colombo is known for city experiences, food, shopping, and travel convenience.\"}]','Plan Your Colombo Stay','We\'ll help you include Colombo in a private route that fits your timing and travel style.','Visit Colombo with a Private Driver and Comfortable Vehicle | Island Culture Travels','Explore Colombo with a professional chauffeur guide, private city travel, and flexible Sri Lanka itinerary support.',NULL,1,6,1)",

                // Itineraries
                "INSERT IGNORE INTO itineraries (name,slug,days,excerpt,intro,route_summary,best_for,travel_notes,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Sri Lanka Itinerary 7 Days','sri-lanka-itinerary-7-days',7,'Best for short first visits with a balanced mix of scenery, culture, and private travel comfort.','A 7 day Sri Lanka itinerary works well for first-time visitors who want a short but meaningful private journey across the island.','Colombo / Airport → Cultural or scenic stop → hill country or safari → southern or city return','First-time visitors, couples, families, short private holidays','Private vehicle travel is recommended to keep this route practical and comfortable.','7 Day Sri Lanka Itinerary with Private Driver and Vehicle | Island Culture Travels','Discover cultural sites, wildlife parks, and scenic landscapes with a private chauffeur and reliable transport throughout your 7 day Sri Lanka journey.',NULL,1,1,1)",

                "INSERT IGNORE INTO itineraries (name,slug,days,excerpt,intro,route_summary,best_for,travel_notes,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Sri Lanka Itinerary 10 Days','sri-lanka-itinerary-10-days',10,'Ideal for couples and families wanting a fuller Sri Lanka route with culture, scenery, wildlife, or beaches.','A 10 day Sri Lanka itinerary gives enough time for a deeper and better-paced island journey.','Colombo / Airport → cultural region → hill country → safari or beach → Colombo','Families, couples, first-time visitors, travelers wanting more variety','Ten days gives enough flexibility for a more rewarding multi-region route.','10 Day Sri Lanka Itinerary — Private Travel Across the Island | Island Culture Travels','Travel comfortably across Sri Lanka with a professional driver, modern vehicle, and a balanced itinerary covering culture, nature, and relaxation.',NULL,1,2,1)",

                "INSERT IGNORE INTO itineraries (name,slug,days,excerpt,intro,route_summary,best_for,travel_notes,meta_title,meta_description,canonical_url,is_featured,sort_order,is_published) VALUES
('Sri Lanka Wildlife Safari Itinerary 7 Days','sri-lanka-wildlife-safari-itinerary-7-days',7,'A 7 day wildlife itinerary covering major safari regions with private transport and flexible planning.','This safari-focused itinerary is ideal for travelers who want to experience Sri Lanka\'s best wildlife regions in a short but well-paced private journey.','Colombo / Airport → Udawalawe → Yala → Southern Coast / Galle → Colombo','Wildlife lovers, couples, families, nature photographers','Morning and evening safari timing should be planned carefully depending on season and route flow.','7 Day Sri Lanka Wildlife Safari Itinerary with Private Driver | Island Culture Travels','Explore Sri Lanka\'s national parks with private transport, flexible safari planning, and expert local guidance.',NULL,1,3,1)",

                // Country pages
                "INSERT IGNORE INTO country_pages (country_name,slug,excerpt,intro,why_sri_lanka,recommended_itinerary,travel_info,best_time,faq_json,meta_title,meta_description,canonical_url,sort_order,is_published) VALUES
('India','india','Plan a private Sri Lanka tour from India with flexible itineraries, comfortable vehicles, and short or extended travel options.','Sri Lanka is one of the easiest and most rewarding international holiday options for travelers from India.','Sri Lanka suits Indian travelers because it offers short travel time, varied landscapes, familiar cultural touchpoints, and flexible private routes.','A good starting option is a 4 to 7 day private tour covering hill country, city highlights, wildlife, or a short scenic route.','Private vehicle travel is the most comfortable way to explore Sri Lanka for families and small groups.','Sri Lanka can be visited throughout the year depending on the route and travel focus.','[{\"question\":\"Is Sri Lanka good for a short holiday from India?\",\"answer\":\"Yes. Sri Lanka works very well for short private holidays thanks to manageable flight times and varied experiences.\"}]','Private Sri Lanka Tours for Travelers from India | Island Culture Travels','Plan your Sri Lanka holiday from India with a trusted chauffeur service, comfortable vehicle options, and personalized travel itineraries.',NULL,1,1)",

                // Reviews
                "INSERT IGNORE INTO reviews (customer_name,country,rating,review_text,trip_type,review_source,review_source_url,is_featured,is_published,sort_order) VALUES
('Priya M.','India',5,'We had a very comfortable private trip across Sri Lanka and really enjoyed the safari experiences. The planning was smooth, the vehicle was comfortable, and the driver was helpful throughout the journey.','Wildlife Tour','Google Reviews',NULL,1,1,1)",

                // Relations
                "INSERT IGNORE INTO destination_related (destination_id,related_destination_id) SELECT d1.id,d2.id FROM destinations d1,destinations d2 WHERE d1.slug='yala' AND d2.slug='udawalawe'",
                "INSERT IGNORE INTO destination_related (destination_id,related_destination_id) SELECT d1.id,d2.id FROM destinations d1,destinations d2 WHERE d1.slug='yala' AND d2.slug='ella'",
                "INSERT IGNORE INTO destination_related (destination_id,related_destination_id) SELECT d1.id,d2.id FROM destinations d1,destinations d2 WHERE d1.slug='udawalawe' AND d2.slug='yala'",
                "INSERT IGNORE INTO destination_related (destination_id,related_destination_id) SELECT d1.id,d2.id FROM destinations d1,destinations d2 WHERE d1.slug='udawalawe' AND d2.slug='ella'",
                "INSERT IGNORE INTO destination_tour (destination_id,tour_id) SELECT d.id,t.id FROM destinations d,tours t WHERE d.slug='yala' AND t.slug='sri-lanka-wildlife-safari-tour'",
                "INSERT IGNORE INTO destination_tour (destination_id,tour_id) SELECT d.id,t.id FROM destinations d,tours t WHERE d.slug='udawalawe' AND t.slug='sri-lanka-wildlife-safari-tour'",
                "INSERT IGNORE INTO destination_tour (destination_id,tour_id) SELECT d.id,t.id FROM destinations d,tours t WHERE d.slug='ella' AND t.slug='private-sri-lanka-tour'",
                "INSERT IGNORE INTO destination_itinerary (destination_id,itinerary_id) SELECT d.id,i.id FROM destinations d,itineraries i WHERE d.slug='yala' AND i.slug='sri-lanka-wildlife-safari-itinerary-7-days'",
                "INSERT IGNORE INTO destination_itinerary (destination_id,itinerary_id) SELECT d.id,i.id FROM destinations d,itineraries i WHERE d.slug='udawalawe' AND i.slug='sri-lanka-wildlife-safari-itinerary-7-days'",
                "INSERT IGNORE INTO destination_itinerary (destination_id,itinerary_id) SELECT d.id,i.id FROM destinations d,itineraries i WHERE d.slug='ella' AND i.slug='sri-lanka-itinerary-10-days'",
                "INSERT IGNORE INTO itinerary_destination (itinerary_id,destination_id) SELECT i.id,d.id FROM itineraries i,destinations d WHERE i.slug='sri-lanka-wildlife-safari-itinerary-7-days' AND d.slug='yala'",
                "INSERT IGNORE INTO itinerary_destination (itinerary_id,destination_id) SELECT i.id,d.id FROM itineraries i,destinations d WHERE i.slug='sri-lanka-wildlife-safari-itinerary-7-days' AND d.slug='udawalawe'",
                "INSERT IGNORE INTO itinerary_destination (itinerary_id,destination_id) SELECT i.id,d.id FROM itineraries i,destinations d WHERE i.slug='sri-lanka-itinerary-10-days' AND d.slug='ella'",
                "INSERT IGNORE INTO itinerary_tour (itinerary_id,tour_id) SELECT i.id,t.id FROM itineraries i,tours t WHERE i.slug='sri-lanka-wildlife-safari-itinerary-7-days' AND t.slug='sri-lanka-wildlife-safari-tour'",
                "INSERT IGNORE INTO itinerary_tour (itinerary_id,tour_id) SELECT i.id,t.id FROM itineraries i,tours t WHERE i.slug='sri-lanka-itinerary-10-days' AND t.slug='private-sri-lanka-tour'",
            ];

            foreach ($seedStatements as $stmt) {
                $pdo->exec($stmt);
            }
            $log[] = '✔ Seed data inserted (existing rows skipped).';

            // ── Admin account ────────────────────────────────────────────────
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $emailVal = $adminEmail !== '' ? $adminEmail : $adminUser . '@' . parse_url($siteUrl, PHP_URL_HOST);
            $stmt = $pdo->prepare("
                INSERT INTO admins (name, email, username, password_hash, is_active)
                VALUES (:name, :email, :username, :hash, 1)
                ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), is_active = 1
            ");
            $stmt->execute([
                'name'     => $adminName,
                'email'    => $emailVal,
                'username' => $adminUser,
                'hash'     => $hash,
            ]);
            $log[] = '✔ Admin account <strong>' . installer_h($adminUser) . '</strong> created / updated.';

            // Secondary admin account (dev convenience; same credentials as schema.sql seed)
            // WARNING: This creates a known shared admin. Remove before production deployment.
            $imranHash = '$2y$10$rdX/3jowvni7NGH5pihAse7g/BfEA25/.WKkP7fvQNhkXDLCB1Tuu';
            $stmt = $pdo->prepare("
                INSERT IGNORE INTO admins (name, email, username, password_hash, is_active)
                VALUES (:name, :email, :username, :hash, 1)
            ");
            $stmt->execute([
                'name'     => 'Imran',
                'email'    => 'imran.admin@islandculturetravels.com',
                'username' => 'imran',
                'hash'     => $imranHash,
            ]);
            $log[] = "✔ Secondary admin account <strong>imran</strong> ensured.";

            // ── Write .env ───────────────────────────────────────────────────
            if ($envWrite) {
                $envSiteUrl = installer_env_value($siteUrl);
                $envDbHost = installer_env_value($dbHost);
                $envDbPort = installer_env_value($dbPort);
                $envDbName = installer_env_value($dbName);
                $envDbUser = installer_env_value($dbUser);
                $envDbPass = installer_env_value($dbPass);
                $envHost = preg_replace('/[^A-Za-z0-9.-]/', '', (string) ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?: 'localhost';
                $envContent = <<<ENV
APP_URL={$envSiteUrl}
APP_ENV=production
APP_DEBUG=false

DB_HOST={$envDbHost}
DB_PORT={$envDbPort}
DB_NAME={$envDbName}
DB_USERNAME={$envDbUser}
DB_PASSWORD={$envDbPass}

SITE_EMAIL=hello@{$envHost}
SITE_PHONE=+94 76 919 2427
SITE_PHONE_SECONDARY=+94 77 949 2427
WHATSAPP_NUMBER=94769192427
DEFAULT_WHATSAPP_TEXT=Hello Island Culture Travels, I would like to plan a Sri Lanka tour.
TAWK_TO_ENABLED=false
TAWK_TO_PROPERTY_ID=
TAWK_TO_WIDGET_ID=default
ENV;
                file_put_contents(__DIR__ . '/.env', $envContent);
                $log[] = '✔ <code>.env</code> file written.';
            }

            // ── Lock + self-delete ───────────────────────────────────────────
            file_put_contents(LOCK_FILE, date('Y-m-d H:i:s') . ' — installed by ' . ($adminUser) . "\n");
            $log[] = '✔ Lock file <code>install.lock</code> created.';

            @unlink(__FILE__);
            $log[] = '✔ Installer self-deleted.';

            $success = true;

        } catch (Throwable $e) {
            error_log('[install] ' . $e->getMessage());
            $errors[] = 'Installation failed. Check database credentials, permissions, and server configuration, then try again.';
        }
    }
}

// ── Render ────────────────────────────────────────────────────────────────────
$phpOk  = PHP_MAJOR_VERSION >= 8;
$pdoOk  = extension_loaded('pdo_mysql');
$mbOk   = extension_loaded('mbstring');
$jsonOk = extension_loaded('json');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Island Culture Travels — Installer</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#f8fafc;font-family:system-ui,sans-serif}
.installer-wrap{max-width:680px;margin:60px auto 100px}
.check-ok{color:#16a34a}
.check-fail{color:#dc2626}
.install-log li{font-family:monospace;font-size:.9rem}
</style>
<link rel="stylesheet" href="assets/css/typography.css">
</head>
<body>
<div class="installer-wrap px-3">

    <div class="text-center mb-4">
        <h1 class="h3 fw-bold">Island Culture Travels</h1>
        <p class="text-secondary">One-Time Installer</p>
    </div>

    <?php if ($success): ?>
        <!-- ── Success ─────────────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4">
            <h2 class="h5 text-success mb-3">✔ Installation Complete</h2>
            <ul class="install-log ps-3">
                <?php foreach ($log as $line): ?>
                    <li class="mb-1"><?= $line ?></li>
                <?php endforeach; ?>
            </ul>
            <hr>
            <p class="mb-2"><strong>Admin login:</strong> <code>/admin/</code></p>
            <p class="mb-3 text-secondary small">Username and password you entered above.</p>
            <a href="/" class="btn btn-success">Go to Site →</a>
            <a href="/admin/" class="btn btn-outline-dark ms-2">Go to Admin →</a>
        </div>

    <?php else: ?>
        <!-- ── Requirements ────────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4 mb-4">
            <h2 class="h6 fw-bold mb-3">Server Requirements</h2>
            <ul class="list-unstyled mb-0">
                <li class="mb-1 <?= $phpOk ? 'check-ok' : 'check-fail' ?>">
                    <?= $phpOk ? '✔' : '✘' ?> PHP 8.0+ (current: <?= PHP_VERSION ?>)
                </li>
                <li class="mb-1 <?= $pdoOk ? 'check-ok' : 'check-fail' ?>">
                    <?= $pdoOk ? '✔' : '✘' ?> PDO MySQL extension
                </li>
                <li class="mb-1 <?= $mbOk ? 'check-ok' : 'check-fail' ?>">
                    <?= $mbOk ? '✔' : '✘' ?> mbstring extension
                </li>
                <li class="<?= $jsonOk ? 'check-ok' : 'check-fail' ?>">
                    <?= $jsonOk ? '✔' : '✘' ?> JSON extension
                </li>
            </ul>
        </div>

        <!-- ── Errors ──────────────────────────────────────────────────── -->
        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= installer_h($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ── Form ───────────────────────────────────────────────────── -->
        <div class="card border-0 shadow-sm p-4">
            <form method="post" novalidate>
                <input type="hidden" name="_token" value="<?= installer_h(installer_csrf_token()) ?>">

                <h2 class="h6 fw-bold mb-3 border-bottom pb-2">Database</h2>
                <div class="row g-3 mb-4">
                    <div class="col-sm-8">
                        <label class="form-label">Host</label>
                        <input type="text" name="db_host" class="form-control" value="<?= installer_old('db_host','127.0.0.1') ?>">
                    </div>
                    <div class="col-sm-4">
                        <label class="form-label">Port</label>
                        <input type="text" name="db_port" class="form-control" value="<?= installer_old('db_port','3306') ?>">
                    </div>
                    <div class="col-sm-12">
                        <label class="form-label">Database Name <span class="text-danger">*</span></label>
                        <input type="text" name="db_name" class="form-control" value="<?= installer_old('db_name','island_culture') ?>" required>
                        <div class="form-text">Created automatically if it does not exist.</div>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="db_user" class="form-control" value="<?= installer_old('db_user','root') ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="db_pass" class="form-control" value="">
                    </div>
                </div>

                <h2 class="h6 fw-bold mb-3 border-bottom pb-2">Site</h2>
                <div class="mb-4">
                    <label class="form-label">Site URL <span class="text-danger">*</span></label>
                    <input type="url" name="site_url" class="form-control"
                           value="<?= installer_old('site_url','https://srilankaprivatetravels.com') ?>" required>
                    <div class="form-text">No trailing slash. Used for canonical URLs and OG tags.</div>
                </div>

                <h2 class="h6 fw-bold mb-3 border-bottom pb-2">Admin Account</h2>
                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="admin_name" class="form-control" value="<?= installer_old('admin_name','Admin') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="admin_email" class="form-control" value="<?= installer_old('admin_email') ?>">
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" name="admin_user" class="form-control" value="<?= installer_old('admin_user','admin') ?>" required>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" name="admin_pass" class="form-control" required minlength="8"
                               placeholder="Min. 8 characters">
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="write_env" id="writeEnv" checked>
                    <label class="form-check-label" for="writeEnv">
                        Write <code>.env</code> file with DB credentials
                    </label>
                    <div class="form-text">Recommended. Keeps credentials out of PHP source code.</div>
                </div>

                <button type="submit" class="btn btn-success w-100">
                    Install Island Culture Travels
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
