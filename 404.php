<?php
declare(strict_types=1);

/**
 * Catch-all 404.
 *
 * .htaccess sends every unmatched URL here, so this is the page most broken
 * links land on. It now renders through the Daylight (v3) shell via
 * Renderer::notFound(), which sets HTTP 404 and noindex,follow.
 */

require __DIR__ . '/app/bootstrap.php';

ICT\View\Renderer::notFound('The page you were looking for is not available.');
