<?php
declare(strict_types=1);

/**
 * /itineraries — discovery index (Phase 2).
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('itineraries'))->run();
