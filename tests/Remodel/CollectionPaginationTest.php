<?php
declare(strict_types=1);

/**
 * Unit tests for the listing pagination rules.
 *
 * These pin the canonical contract that the legacy listings established and
 * BlogPaginationCanonicalTest enforces for /blog, so the six new indexes cannot
 * drift from it.
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

require_once APP_ROOT . '/app/Autoload.php';

use ICT\Support\Pagination;
use PHPUnit\Framework\TestCase;

final class CollectionPaginationTest extends TestCase
{
    public function testFirstPageAndOutOfRangeCanonicaliseToBase(): void
    {
        $base = 'https://example.test/destinations';

        self::assertSame($base, Pagination::canonical($base, 0, 4));
        self::assertSame($base, Pagination::canonical($base, 1, 4));
        self::assertSame($base, Pagination::canonical($base, 5, 4));
        self::assertSame($base, Pagination::canonical($base, 999, 4));
        self::assertSame($base, Pagination::canonical($base, 2, 0));
    }

    public function testInnerPagesAreSelfCanonical(): void
    {
        $base = 'https://example.test/destinations';

        self::assertSame($base . '?page=2', Pagination::canonical($base, 2, 4));
        self::assertSame($base . '?page=4', Pagination::canonical($base, 4, 4));
    }

    public function testTrailingSlashNeverLeaksIntoCanonical(): void
    {
        self::assertSame(
            'https://example.test/destinations?page=2',
            Pagination::canonical('https://example.test/destinations/', 2, 3)
        );
    }

    public function testWindowMathsAndClamping(): void
    {
        $window = Pagination::window('https://example.test/tours', 2, 25, 12);

        self::assertSame(2, $window['page']);
        self::assertSame(3, $window['pages']);
        self::assertSame(25, $window['total']);
        self::assertTrue($window['has_prev']);
        self::assertTrue($window['has_next']);
        self::assertSame('https://example.test/tours', $window['prev_url']);
        self::assertSame('https://example.test/tours?page=3', $window['next_url']);

        $clamped = Pagination::window('https://example.test/tours', 99, 25, 12);
        self::assertSame(3, $clamped['page'], 'page is clamped to the last page');
        self::assertFalse($clamped['has_next']);
    }

    public function testWindowWithNoResultsStillReportsOnePage(): void
    {
        $window = Pagination::window('https://example.test/activities', 1, 0, 12);

        self::assertSame(1, $window['pages']);
        self::assertFalse($window['has_prev']);
        self::assertFalse($window['has_next']);
    }

    public function testPageUrlKeepsActiveFilters(): void
    {
        self::assertSame(
            'https://example.test/destinations?region=South+Coast&page=2',
            Pagination::pageUrl('https://example.test/destinations', 2, ['region' => 'South Coast'])
        );

        self::assertSame(
            'https://example.test/destinations?region=South+Coast',
            Pagination::pageUrl('https://example.test/destinations', 1, ['region' => 'South Coast', 'page' => 3]),
            'page 1 drops the page key but keeps filters'
        );

        self::assertSame(
            'https://example.test/destinations',
            Pagination::pageUrl('https://example.test/destinations', 1, [])
        );
    }

    public function testCurrentPageParsesAndSanitisesQueryInput(): void
    {
        self::assertSame(1, Pagination::currentPage([]));
        self::assertSame(1, Pagination::currentPage(['page' => '0']));
        self::assertSame(1, Pagination::currentPage(['page' => '-4']));
        self::assertSame(1, Pagination::currentPage(['page' => 'abc']));
        self::assertSame(1, Pagination::currentPage(['page' => ['2']]));
        self::assertSame(3, Pagination::currentPage(['page' => '3']));
        self::assertSame(1000, Pagination::currentPage(['page' => '99999']), 'page is capped');
    }
}
