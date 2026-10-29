<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PublicContentLeakTest extends TestCase
{
    #[DataProvider('contentSourceProvider')]
    public function testPublicContentSourcesDoNotContainInternalLabels(string $relativePath): void
    {
        $path = __DIR__ . '/../' . $relativePath;
        $content = file_get_contents($path);

        $this->assertNotFalse($content, sprintf('Unable to read %s', $relativePath));

        $forbiddenPhrases = [
            'Route planning note:',
            'Planning depth:',
            'Operational planning note:',
            'Package operations:',
            'Source-market planning note:',
            'Recommended route angle:',
            'AI-search angle',
            'Best-fit use:',
        ];

        foreach ($forbiddenPhrases as $phrase) {
            $this->assertStringNotContainsString(
                $phrase,
                $content,
                sprintf('Found forbidden phrase "%s" in %s', $phrase, $relativePath)
            );
        }
    }

    public static function contentSourceProvider(): array
    {
        return [
            ['sql-import.sql'],
            ['database/migrations/2026-06-19-non-blog-content-depth.sql'],
            ['database/migrations/2026-06-19-ai-search-content-pack.sql'],
            ['database/migrations/2026-07-09-content-huma-itineraries.sql'],
            ['database/migrations/2026-07-09-content-huma-itinerary-days.sql'],
            ['database/migrations/2026-07-09-content-huma-tours.sql'],
        ];
    }
}
