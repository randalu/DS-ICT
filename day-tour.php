<?php
declare(strict_types=1);

/**
 * /day-tours/{slug} — Daylight (v3) day tour detail.
 *
 * Controller: app/Pages/DayTourPageV3.php
 * Query:      app/Content/DayTourRepository.php
 * Template:   views/v3/pages/day-tours/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\DayTourPageV3((string) ($_GET['slug'] ?? '')))->run();
