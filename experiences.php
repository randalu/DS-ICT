<?php
declare(strict_types=1);

/**
 * /experiences — discovery index (Phase 2).
 *
 * Controller: app/Pages/CollectionIndexPage.php
 * Query:      app/Content/CollectionRepository.php
 * Template:   views/pages/collections/index.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\CollectionIndexPage('experiences'))->run();
