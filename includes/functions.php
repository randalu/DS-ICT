<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// --- Barrel: load focused modules ---
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/forms.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/schema_helpers.php';
require_once __DIR__ . '/sessions.php';
require_once __DIR__ . '/privacy_controls.php';
require_once __DIR__ . '/web_analytics.php';
require_once __DIR__ . '/analytics_retention_review.php';
