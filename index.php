<?php
declare(strict_types=1);

/**
 * Home route — Deep Coast (v3).
 *
 * The presentation lives in views/v3 (layout + partials + pages/home.php) and
 * the controller in app/Pages/HomePageV3.php. SEO and schema still come from
 * includes/seo.php and schema_output(), so metadata is unchanged.
 */

require __DIR__ . '/app/bootstrap.php';

try {
    (new ICT\Pages\HomePageV3())->run();
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
