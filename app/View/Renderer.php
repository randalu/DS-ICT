<?php
declare(strict_types=1);

namespace ICT\View;

use RuntimeException;

/**
 * Renders a Page through views/layouts/public.php.
 *
 * The renderer owns the response-level concerns (cache headers, 404 status)
 * that used to be scattered across the legacy root pages, so every new
 * template behaves the same way.
 */
final class Renderer
{
    private const LAYOUT = '/views/layouts/public.php';

    /**
     * @param string $layout layout path relative to the project root, so a page
     *                       family can opt into the v3 shell while the rest of
     *                       the site still renders through the v2 one
     */
    public static function render(Page $page, string $layout = self::LAYOUT): void
    {
        if (!headers_sent()) {
            // Public HTML is personalised by the privacy banner and the
            // homepage rotation cookie, so it must not be cached publicly.
            header('Cache-Control: private, no-store');
            header('Vary: Cookie', false);
        }

        $layout = APP_ROOT . '/' . ltrim($layout, '/');

        if (!is_file($layout)) {
            throw new RuntimeException('Public layout missing: ' . $layout);
        }

        require $layout;
    }

    /**
     * Render a template file in isolation. Used by partials that need a small
     * reusable block, and by the render tests.
     *
     * @param array<string,mixed> $data
     */
    public static function view(string $template, array $data = []): string
    {
        $path = APP_ROOT . '/views/' . ltrim($template, '/');

        if (!is_file($path)) {
            throw new RuntimeException('View missing: ' . $path);
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }

    /** Send a 404 for an unknown or unpublished record. */
    public static function notFound(string $message = 'Page not found'): void
    {
        if (!headers_sent()) {
            http_response_code(404);
        }

        self::render(new Page(
            template: 'v3/pages/errors/404',
            meta: [
                'title' => 'Page not found',
                'description' => 'The page you were looking for is not available.',
                'robots' => 'noindex,follow',
            ],
            data: ['message' => $message],
            bodyClass: 'ix-page-404',
        ), 'views/v3/layout.php');
    }
}
