<?php
declare(strict_types=1);

/**
 * Unit tests for the v2 foundation layer (app/).
 *
 * These are deliberately dependency-free: the app classes only need the small
 * set of global helpers they call at runtime, so the test bootstraps stubs
 * instead of opening a database connection or starting a session.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Island Culture Travels');
}

if (!defined('DEFAULT_OG_IMAGE')) {
    define('DEFAULT_OG_IMAGE', 'https://example.test/assets/images/og-default.jpg');
}

if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        return 'https://example.test/' . ltrim($path, '/');
    }
}

if (!function_exists('stored_media_url')) {
    function stored_media_url(string $path): string
    {
        return 'https://example.test/assets/uploads/' . ltrim($path, '/');
    }
}

require_once APP_ROOT . '/app/Autoload.php';

use ICT\Support\ContentNormalizer;
use ICT\Support\MediaResolver;
use ICT\Support\PriceFormatter;
use ICT\View\Page;
use PHPUnit\Framework\TestCase;

final class RemodelFoundationTest extends TestCase
{
    public function testPageExposesMetaWithFallbacks(): void
    {
        $page = new Page(
            template: 'pages/home',
            meta: ['title' => 'A title', 'description' => ''],
            data: ['key' => 'value'],
            bodyClass: 'ic-v2 ic-page-home',
        );

        self::assertSame('A title', $page->meta('title'));
        self::assertSame('fallback', $page->meta('description', 'fallback'));
        self::assertSame('fallback', $page->meta('missing', 'fallback'));
        self::assertSame('pages/home', $page->template);
        self::assertTrue($page->recordPageView);
    }

    public function testPageWithHelpersReturnCopies(): void
    {
        $page = new Page(template: 'pages/home', meta: ['title' => 'One']);

        $withMeta = $page->withMeta(['description' => 'Two']);
        $withData = $page->withData(['cards' => [1, 2]]);
        $withSchema = $page->withSchema([['@type' => 'WebSite']]);

        self::assertSame('One', $page->meta('title'), 'original must be untouched');
        self::assertSame('', $page->meta('description'));
        self::assertSame([], $page->data);

        self::assertSame('One', $withMeta->meta('title'));
        self::assertSame('Two', $withMeta->meta('description'));
        self::assertSame([1, 2], $withData->data['cards']);
        self::assertSame('WebSite', $withSchema->schema[0]['@type']);
    }

    public function testPriceFormatterHandlesMissingAndPresentPrices(): void
    {
        self::assertSame('', PriceFormatter::from(null));
        self::assertSame('', PriceFormatter::from(''));
        self::assertSame('', PriceFormatter::from(0));
        self::assertSame('', PriceFormatter::from(-10));

        self::assertSame('$700', PriceFormatter::from(700, 'USD'));
        self::assertSame('$1,250', PriceFormatter::from('1250', 'USD'));
        self::assertSame('€980.50', PriceFormatter::from(980.5, 'EUR'));

        self::assertSame('$70', PriceFormatter::forRow(['price_from' => 70, 'price_currency' => 'USD']));
        self::assertSame('', PriceFormatter::forRow([]));
    }

    public function testPriceFormatterDurationLabels(): void
    {
        self::assertSame('6 hours', PriceFormatter::duration(6));
        self::assertSame('1 hour', PriceFormatter::duration(1));
        self::assertSame('1 day', PriceFormatter::duration(24));
        self::assertSame('3 days', PriceFormatter::duration(72));
        self::assertSame('', PriceFormatter::duration(null));
        self::assertSame('', PriceFormatter::duration(0));
    }

    public function testMediaResolverDetectsVideoAndBuildsUrls(): void
    {
        self::assertTrue(MediaResolver::isVideo('destinations/hero.mp4'));
        self::assertTrue(MediaResolver::isVideo('clip.WEBM'));
        self::assertFalse(MediaResolver::isVideo('photo.jpg'));

        self::assertStringEndsWith('/assets/images/a.jpg', MediaResolver::url('assets/images/a.jpg'));
        self::assertSame('https://cdn.test/a.jpg', MediaResolver::url('https://cdn.test/a.jpg'));
        self::assertSame(DEFAULT_OG_IMAGE, MediaResolver::url(''));
    }

    public function testMediaResolverFallsBackForAltTextWithoutLeakingEmpty(): void
    {
        self::assertSame('Row alt', MediaResolver::alt(['hero_alt' => 'Row alt'], 'Fallback'));
        self::assertSame('Fallback', MediaResolver::alt(['hero_alt' => '   '], 'Fallback'));
        self::assertSame(APP_NAME, MediaResolver::alt([], ''));
    }

    public function testMediaResolverUsesPosterForVideoHeroes(): void
    {
        $media = MediaResolver::forRow([
            'hero_image' => 'destinations/hero.mp4',
            'cover_image' => 'destinations/cover.jpg',
            'hero_alt' => 'Sigiriya at sunrise',
        ], 'Sigiriya');

        self::assertStringEndsWith('destinations/cover.jpg', $media['url']);
        self::assertTrue($media['is_video']);
        self::assertSame('Sigiriya at sunrise', $media['alt']);
    }

    public function testDestinationCardShapeIsStable(): void
    {
        $card = ContentNormalizer::destination([
            'name' => 'Sigiriya',
            'slug' => 'sigiriya',
            'excerpt' => 'An ancient rock fortress.',
            'region_label' => 'Cultural Triangle',
            'stay_duration' => '1-2 days',
            'destination_type' => 'UNESCO Heritage',
            'hero_image' => 'assets/images/galle-lighthouse.jpg',
        ]);

        self::assertSame('Sigiriya', $card['title']);
        self::assertStringEndsWith('/destinations/sigiriya', $card['url']);
        self::assertSame('Cultural Triangle', $card['kicker']);
        self::assertContains('1-2 days', $card['meta']);
        self::assertContains('UNESCO Heritage', $card['meta']);
        self::assertSame('An ancient rock fortress.', $card['excerpt']);

        foreach (['title', 'url', 'image', 'alt', 'excerpt', 'meta', 'price', 'kicker'] as $key) {
            self::assertArrayHasKey($key, $card);
        }
    }

    public function testJourneyCardCarriesDurationAndPrice(): void
    {
        $card = ContentNormalizer::journey([
            'name' => 'Classic Sri Lanka',
            'slug' => 'classic-sri-lanka',
            'days' => 7,
            'price_from' => 700,
            'price_currency' => 'USD',
            'hero_image' => 'assets/images/home-hero/home-2.jpg',
        ]);

        self::assertStringEndsWith('/itineraries/classic-sri-lanka', $card['url']);
        self::assertSame(['7 days'], $card['meta']);
        self::assertSame('$700', $card['price']);
    }

    public function testArticleAndDayTourCardsDegradeGracefully(): void
    {
        $article = ContentNormalizer::article([]);
        self::assertSame('Journal entry', $article['title']);
        self::assertStringEndsWith('/blog', $article['url']);

        $tour = ContentNormalizer::dayTour(['duration_hours' => 6, 'max_group_size' => 4]);
        self::assertContains('6 hours', $tour['meta']);
        self::assertContains('Up to 4', $tour['meta']);
    }
}
