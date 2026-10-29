<?php
declare(strict_types=1);

/**
 * Application bootstrap for the v2 public templates.
 *
 * Root pages include this file first, then hand control to a page controller:
 *
 *   require __DIR__ . '/app/bootstrap.php';
 *   (new ICT\Pages\HomePage())->run();
 *
 * What this file guarantees, in order:
 *  - APP_ROOT is defined for view paths
 *  - the legacy helper layer is loaded (config, PDO, media, privacy, analytics)
 *  - schema helpers are loaded so schema_output() stays the single emitter
 *  - ICT\* classes autoload from app/
 *
 * Nothing here changes existing behaviour for pages that do not opt in: the
 * legacy header/footer keep working because they call the same helpers.
 */

if (defined('ICT_APP_BOOTSTRAPPED')) {
    return;
}

define('ICT_APP_BOOTSTRAPPED', true);
define('APP_ROOT', dirname(__DIR__));

require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/schema.php';
require_once APP_ROOT . '/includes/responsive_image.php';

require_once __DIR__ . '/Autoload.php';
