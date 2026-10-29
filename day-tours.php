<?php
declare(strict_types=1);

/**
 * /day-tours — discovery index (Phase 2).
 *
 * Keeps the legacy filter vocabulary (q, difficulty, price) so existing links
 * and any indexed filtered URLs behave the same way.
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('day-tours'))->run();
