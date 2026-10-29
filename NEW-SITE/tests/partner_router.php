<?php
declare(strict_types=1);

// Test-server process only. Never enable partners or credentials on the original site.
putenv('NEW_SITE_DEMO_PARTNERS=1');
foreach (['NEW_SITE_DB_DSN', 'NEW_SITE_DB_USER', 'NEW_SITE_DB_PASSWORD'] as $name) {
    putenv($name);
}
require dirname(__DIR__) . '/router.php';
