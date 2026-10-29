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

// includes/seo.php doubles as the <head> partial: it defines the canonical
// helpers *and* echoes meta tags. Controllers need the helpers before any
// output, so load it once with output captured and discarded. The layout then
// includes it normally (require, not require_once) to emit the real tags, so
// there is still exactly one implementation of the canonical rules.
ob_start();
require_once APP_ROOT . '/includes/seo.php';
ob_end_clean();

require_once __DIR__ . '/Autoload.php';
