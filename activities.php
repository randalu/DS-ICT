<?php
declare(strict_types=1);

/**
 * /activities — discovery index (Phase 2).
 *
 * The hub row in activity_pages supplies this page's heading, meta and image.
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('activities'))->run();
