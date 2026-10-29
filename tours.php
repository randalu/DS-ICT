<?php
declare(strict_types=1);

/**
 * /tours — discovery index (Phase 2).
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('tours'))->run();
