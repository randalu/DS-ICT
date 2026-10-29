<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

final class BlogPaginationCanonicalTest extends TestCase
{
    public static function paginationQueries(): array
    {
        return [
            'default first page' => [[], 'blog'],
            'explicit first page' => [['page' => '1'], 'blog'],
            'second page' => [['page' => '2'], 'blog?page=2'],
            'third page' => [['page' => '3'], 'blog?page=3'],
            'tracking parameter is excluded' => [['page' => '2', 'utm_source' => 'canonical-test'], 'blog?page=2'],
            'normalized first page' => [['page' => '0'], 'blog'],
            'empty out-of-range page keeps its existing canonical' => [['page' => '999999'], 'blog'],
        ];
    }

    #[DataProvider('paginationQueries')]
    public function testRenderedBlogMetadataUsesTheRequestedPage(array $query, string $expectedPath): void
    {
        if ($expectedPath !== 'blog' && count_published_blog_posts() <= ((int) $query['page'] - 1) * 9) {
            $this->markTestSkipped('This rendered integration case requires enough published blog posts for its page.');
        }
        $get = $_GET;
        $request = $_REQUEST;
        $cookies = $_COOKIE;
        $server = $_SERVER;
        $bufferLevel = ob_get_level();

        try {
            $_GET = $query;
            $_REQUEST = $query;
            $_COOKIE = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = '/blog' . ($query ? '?' . http_build_query($query) : '');
            $recordPageView = false;
            ob_start();
            require __DIR__ . '/../blog.php';
            $html = (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $_GET = $get;
            $_REQUEST = $request;
            $_COOKIE = $cookies;
            $_SERVER = $server;
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        $xpath = new DOMXPath($document);
        $canonical = $xpath->query('//link[@rel="canonical"]');
        $this->assertCount(1, $canonical);
        $this->assertSame(base_url($expectedPath), $canonical->item(0)->getAttribute('href'));
        $this->assertSame(
            base_url($expectedPath),
            $xpath->evaluate('string(//meta[@property="og:url"]/@content)')
        );
        $this->assertStringNotContainsString(
            'noindex',
            $xpath->evaluate('string(//meta[@name="robots"]/@content)')
        );
        $this->assertCount(1, $xpath->query('//h1'));
    }
}
