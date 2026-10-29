<?php
declare(strict_types=1);

/**
 * /experiences/{slug} — Daylight (v3) experience detail.
 *
 * Controller: app/Pages/ExperiencePageV3.php
 * Query:      app/Content/ExperienceRepository.php
 * Template:   views/v3/pages/experiences/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\ExperiencePageV3((string) ($_GET['slug'] ?? '')))->run();
