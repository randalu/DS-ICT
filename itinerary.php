<?php
declare(strict_types=1);

/**
 * /itineraries/{slug} — Daylight (v3) itinerary detail.
 *
 * Controller: app/Pages/ItineraryPageV3.php
 * Query:      app/Content/ItineraryRepository.php
 * Template:   views/v3/pages/itineraries/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\ItineraryPageV3((string) ($_GET['slug'] ?? '')))->run();
