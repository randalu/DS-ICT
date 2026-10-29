<?php
declare(strict_types=1);

/**
 * /blog — Daylight (v3) journal index.
 *
 * Controller: app/Pages/BlogIndexPageV3.php
 * Query:      app/Content/ArticleRepository.php
 * Template:   views/v3/pages/blog/index.php
 *
 * Pagination keeps the 9-per-page contract pinned by
 * BlogPaginationCanonicalTest.
 */

require __DIR__ . '/app/bootstrap.php';

(new ICT\Pages\BlogIndexPageV3())->run();
