<?php
declare(strict_types=1);

/**
 * /blog/{slug} — Daylight (v3) blog article.
 *
 * Controller: app/Pages/ArticlePageV3.php
 * Query:      app/Content/ArticleRepository.php
 * Template:   views/v3/pages/blog/show.php
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\ArticlePageV3((string) ($_GET['slug'] ?? '')))->run();
