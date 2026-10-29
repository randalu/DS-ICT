<?php
declare(strict_types=1);

namespace NewSite;

// Deliberately independent: no original config, sessions, helpers or autoloaders.
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/helpers.php';
if (is_file(__DIR__ . '/repository.php')) {
    require_once __DIR__ . '/repository.php';
}
require_once __DIR__ . '/pages.php';
require_once __DIR__ . '/content.php';
