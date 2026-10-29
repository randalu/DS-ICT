<?php
declare(strict_types=1);

/**
 * /destinations — discovery index.
 *
 * Phase 2 of the remodel: the controller, query and markup now live in
 * app/Pages/CollectionIndexPage.php, app/Content/CollectionRepository.php and
 * views/pages/collections/index.php. The URL, canonical rules and JSON-LD are
 * unchanged.
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('destinations'))->run();
