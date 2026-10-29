<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ThemeContentIntegrityTest extends TestCase
{
    #[DataProvider('themeItineraryProvider')]
    public function testThemeItinerariesAreLinkedInTheSeedData(string $slug, int $categoryId): void
    {
        $content = file_get_contents(__DIR__ . '/../sql-import.sql');

        $this->assertNotFalse($content, 'Unable to read sql-import.sql');

        $pattern = sprintf(
            "/%s',\\s*\\d+,\\s*%d,\\s*NULL,\\s*NULL,\\s*NULL,/",
            preg_quote($slug, '/'),
            $categoryId
        );

        $this->assertMatchesRegularExpression(
            $pattern,
            $content,
            sprintf('Expected %s to be linked to category %d in sql-import.sql', $slug, $categoryId)
        );
    }

    public static function themeItineraryProvider(): array
    {
        return [
            ['sri-lanka-honeymoon-itinerary-12-days', 6],
            ['sri-lanka-family-itinerary-10-days', 7],
            ['sri-lanka-luxury-itinerary-11-days', 10],
        ];
    }
}
