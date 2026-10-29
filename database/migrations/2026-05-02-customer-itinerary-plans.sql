-- Customer private itinerary plans and day-by-day change requests.
-- Safe to run more than once.

CREATE TABLE IF NOT EXISTS customer_itinerary_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    contact_inquiry_id BIGINT UNSIGNED NOT NULL,
    source_itinerary_id BIGINT UNSIGNED NULL,
    plan_title VARCHAR(255) NOT NULL,
    travel_dates VARCHAR(190) NULL,
    traveler_summary VARCHAR(255) NULL,
    plan_status VARCHAR(30) NOT NULL DEFAULT 'planning',
    customer_notes TEXT NULL,
    admin_notes TEXT NULL,
    last_admin_update_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customer_plan_inquiry (contact_inquiry_id),
    KEY idx_customer_plans_customer (customer_user_id, plan_status),
    KEY idx_customer_plans_source_itinerary (source_itinerary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
