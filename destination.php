<?php
declare(strict_types=1);

/**
 * /destinations/{slug} — Daylight (v3) destination detail.
 *
 * Controller: app/Pages/DestinationPageV3.php
 * Query:      app/Content/DestinationRepository.php
 * Template:   views/v3/pages/destinations/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\DestinationPageV3((string) ($_GET['slug'] ?? '')))->run();
