<?php
declare(strict_types=1);

/**
 * /tours/{slug} — Daylight (v3) tour detail.
 *
 * Controller: app/Pages/TourPageV3.php
 * Query:      app/Content/TourRepository.php
 * Template:   views/v3/pages/tours/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\TourPageV3((string) ($_GET['slug'] ?? '')))->run();
