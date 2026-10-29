<?php
declare(strict_types=1);

/**
 * Read-only Release 1/2 audit for published static pages and trust routes.
 *
 * Trust content is deliberately not seeded here. Use --require when the
 * approved CMS records exist and the release should fail for missing routes.
 */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/published_page_audit.php';

// Keep audit progress buffered while the public templates emit their headers.
ob_start();

$only = 'all';
$requireTrustRoutes = false;

foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--only=')) {
        $only = strtolower(trim(substr($argument, 7)));
        if (!in_array($only, ['all', 'placeholders', 'routes'], true)) {
            fwrite(STDERR, "Usage: php cli/trust_page_release_audit.php [--only=all|placeholders|routes] [--require]\n");
            exit(2);
        }
        continue;
    }

    if ($argument === '--require') {
        $requireTrustRoutes = true;
        continue;
    }

    fwrite(STDERR, "Usage: php cli/trust_page_release_audit.php [--only=all|placeholders|routes] [--require]\n");
    exit(2);
}

if (!db_table_exists('static_pages')) {
    fwrite(STDERR, "Trust-page audit: static_pages table is unavailable.\n");
    exit(2);
}

if (!db_column_exists('static_pages', 'is_published')) {
    fwrite(STDERR, "Trust-page audit: static_pages.is_published is unavailable.\n");
    exit(2);
}

$failures = 0;
$placeholderPages = 0;
$placeholderFailures = 0;
$routePassed = 0;
$routeFailures = 0;
$routeSkipped = 0;

if ($only === 'all' || $only === 'placeholders') {
    $publishedPages = fetch_all(
        'SELECT * FROM static_pages WHERE is_published = 1 ORDER BY id ASC'
    );
    $placeholderPages = count($publishedPages);

    foreach ($publishedPages as $page) {
        $issues = published_static_page_placeholder_issues($page);
        $slug = trim((string) ($page['slug'] ?? ''));
        $id = (int) ($page['id'] ?? 0);

        if ($issues) {
            $placeholderFailures++;
            $failures++;
            $labels = array_map(
                static fn(array $issue): string => $issue['field'] . ': ' . $issue['reason'],
                $issues
            );
            echo '[FAIL] static_pages#' . $id . ' ' . $slug . ': ' . implode('; ', $labels) . PHP_EOL;
            continue;
        }

        echo '[PASS] static_pages#' . $id . ' ' . $slug . PHP_EOL;
    }

    echo sprintf(
        "Published-page placeholder summary: %d audited; %d failed.\n",
        $placeholderPages,
        $placeholderFailures
    );
}

if ($only === 'all' || $only === 'routes') {
    $render = static function (string $slug, string $requestPath): string {
        $previousGet = $_GET;
        $previousRequest = $_REQUEST;
        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        $previousMethod = $_SERVER['REQUEST_METHOD'] ?? null;
        $startedTransaction = false;
        $bufferLevel = ob_get_level();
        $html = '';

        try {
            $_GET = ['slug' => $slug];
            $_REQUEST = $_GET;
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = $requestPath;

            if (!db()->inTransaction()) {
                db()->beginTransaction();
                $startedTransaction = true;
            }

            // Keep route auditing read-only; the normal header records a page
            // view on public requests.
            $recordPageView = false;
            ob_start();
            require __DIR__ . '/../static_page.php';
            $html = (string) ob_get_clean();
        } catch (Throwable) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $html = '<!-- trust route render exception -->';
        } finally {
            if ($startedTransaction && db()->inTransaction()) {
                db()->rollBack();
            }

            $_GET = $previousGet;
            $_REQUEST = $previousRequest;
            if ($previousUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousUri;
            }
            if ($previousMethod === null) {
                unset($_SERVER['REQUEST_METHOD']);
            } else {
                $_SERVER['REQUEST_METHOD'] = $previousMethod;
            }
        }

        return $html;
    };

    $extractMeta = static function (string $html, string $name): string {
        if (!preg_match_all('~<meta\b[^>]*>~is', $html, $matches)) {
            return '';
        }

        foreach ($matches[0] as $tag) {
            if (!preg_match('/\bname\s*=\s*(["\'])(.*?)\1/i', $tag, $nameMatch)
                || strcasecmp(trim($nameMatch[2]), $name) !== 0
                || !preg_match('/\bcontent\s*=\s*(["\'])(.*?)\1/i', $tag, $contentMatch)) {
                continue;
            }

            return trim(html_entity_decode($contentMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    };

    $extractCanonical = static function (string $html): string {
        if (!preg_match_all('~<link\b[^>]*>~is', $html, $matches)) {
            return '';
        }

        foreach ($matches[0] as $tag) {
            if (!preg_match('/\brel\s*=\s*(["\'])(.*?)\1/i', $tag, $relMatch)) {
                continue;
            }

            $rels = preg_split('/\s+/', strtolower(trim($relMatch[2])), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            if (!in_array('canonical', $rels, true)
                || !preg_match('/\bhref\s*=\s*(["\'])(.*?)\1/i', $tag, $hrefMatch)) {
                continue;
            }

            return trim(html_entity_decode($hrefMatch[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    };

    $extractSchemaTypes = static function (string $html): array {
        $types = [];
        $webPageUrls = [];
        $invalid = 0;

        if (!preg_match_all('~<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $matches)) {
            return ['types' => [], 'web_page_urls' => [], 'invalid' => 0];
        }

        foreach ($matches[1] as $json) {
            $decoded = json_decode(trim($json), true);
            if (!is_array($decoded)) {
                $invalid++;
                continue;
            }

            $nodes = isset($decoded['@graph']) && is_array($decoded['@graph'])
                ? $decoded['@graph']
                : [$decoded];
            foreach ($nodes as $node) {
                if (!is_array($node)) {
                    continue;
                }

                $nodeTypes = $node['@type'] ?? [];
                $nodeTypes = is_array($nodeTypes) ? $nodeTypes : [$nodeTypes];
                foreach ($nodeTypes as $type) {
                    $type = trim((string) $type);
                    if ($type !== '') {
                        $types[] = $type;
                    }
                    if ($type === 'WebPage' && isset($node['url'])) {
                        $webPageUrls[] = trim((string) $node['url']);
                    }
                }
            }
        }

        return [
            'types' => array_values(array_unique($types)),
            'web_page_urls' => array_values(array_unique($webPageUrls)),
            'invalid' => $invalid,
        ];
    };

    foreach (published_static_page_trust_routes() as $slug => $requestPath) {
        $page = find_published_static_page_by_slug($slug);
        if (!$page) {
            if ($requireTrustRoutes) {
                $routeFailures++;
                $failures++;
                echo '[FAIL] ' . $requestPath . ': missing published CMS record' . PHP_EOL;
            } else {
                $routeSkipped++;
                echo '[SKIP] ' . $requestPath . ': no published CMS record' . PHP_EOL;
            }
            continue;
        }

        $html = $render($slug, $requestPath);
        $issues = [];
        $expectedCanonical = base_url(ltrim($requestPath, '/'));
        $title = '';
        if (preg_match('~<title\b[^>]*>(.*?)</title>~is', $html, $titleMatch)) {
            $title = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        if ($title === '') {
            $issues[] = 'missing title';
        }
        if ($extractMeta($html, 'description') === '') {
            $issues[] = 'missing meta description';
        }
        $robots = strtolower($extractMeta($html, 'robots'));
        if ($robots === '' || str_contains($robots, 'noindex')) {
            $issues[] = 'robots directive is not indexable';
        }
        if ($extractCanonical($html) !== $expectedCanonical) {
            $issues[] = 'canonical does not match public trust route';
        }
        if (preg_match_all('~<h1\b[^>]*>~i', $html) !== 1) {
            $issues[] = 'expected exactly one H1';
        }
        if (!preg_match('~(?:href|content)=["\'][^"\']*(?:/contact|wa\.me)[^"\']*["\']~i', $html)) {
            $issues[] = 'missing contact or WhatsApp CTA';
        }
        if (published_static_page_has_unsafe_root_link($html, APP_URL)) {
            $issues[] = 'root-relative content link is not deployment-subdirectory safe';
        }

        foreach (published_static_page_placeholder_issues($page) as $issue) {
            $issues[] = $issue['field'] . ': ' . $issue['reason'];
        }
        $issues = array_values(array_unique($issues));

        $schema = $extractSchemaTypes($html);
        if ($schema['invalid'] > 0) {
            $issues[] = 'invalid JSON-LD block';
        }
        foreach (['WebSite', 'TravelAgency', 'BreadcrumbList', 'WebPage'] as $requiredType) {
            if (!in_array($requiredType, $schema['types'], true)) {
                $issues[] = 'missing ' . $requiredType . ' JSON-LD';
            }
        }
        if (!in_array($expectedCanonical, $schema['web_page_urls'], true)) {
            $issues[] = 'WebPage JSON-LD URL does not match public trust route';
        }

        $faqItems = clean_public_faq_items(decode_faq_json_items((string) ($page['faq_json'] ?? '')));
        $hasFaqSchema = in_array('FAQPage', $schema['types'], true);
        if ($faqItems && !$hasFaqSchema) {
            $issues[] = 'published FAQ content is missing FAQPage JSON-LD';
        }
        if (!$faqItems && $hasFaqSchema) {
            $issues[] = 'FAQPage JSON-LD has no visible FAQ content';
        }

        if ($issues) {
            $routeFailures++;
            $failures++;
            echo '[FAIL] ' . $requestPath . ': ' . implode('; ', $issues) . PHP_EOL;
        } else {
            $routePassed++;
            echo '[PASS] ' . $requestPath . PHP_EOL;
        }
    }

    echo sprintf(
        "Trust-route summary: %d passed; %d failed; %d skipped.\n",
        $routePassed,
        $routeFailures,
        $routeSkipped
    );
}

exit($failures > 0 ? 1 : 0);
