<?php
declare(strict_types=1);

/**
 * Home route.
 *
 * Everything that used to live in this file (907 lines of queries, markup and
 * inline JavaScript) now sits behind the v2 stack:
 *
 *   app/Pages/HomePage.php      controller: builds the Page model
 *   app/Content/HomeRepository  queries: prepared statements, memoised
 *   views/pages/home.php        template: declarative markup
 *
 * URLs, SEO output and schema emission are unchanged: the layout still calls
 * includes/seo.php and schema_output().
 */

require __DIR__ . '/app/bootstrap.php';

try {
    (new ICT\Pages\HomePage())->run();
} catch (Throwable $e) {
    error_log('[ICT home] ' . $e->getMessage());

    if (defined('APP_DEBUG') && APP_DEBUG) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Home page error: {$e->getMessage()}\n";
        exit;
    }

    ICT\View\Renderer::notFound('This page is temporarily unavailable.');
}
