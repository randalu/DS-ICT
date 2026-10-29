<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

final class InventoryTriageTest extends TestCase
{
    public function testThemeCategoryAliasCanonicalUsesExistingThemeRoute(): void
    {
        $themeRoutes = [
            'honeymoon' => 'honeymoon-tours',
            'family' => 'family-tours',
            'adventure' => 'adventure-tours',
            'wellness' => 'wellness-tours',
            'luxury' => 'luxury-tours',
            'wildlife' => 'wildlife-tours',
        ];

        foreach ($themeRoutes as $slug => $themeRoute) {
            $category = [
                'slug' => $slug,
                'theme_slug' => $themeRoute,
                'is_theme' => 1,
                'itinerary_count' => 1,
            ];

            $this->assertSame(base_url($themeRoute), itinerary_category_public_canonical_url($category));
            $this->assertFalse(itinerary_category_sitemap_indexable($category));
        }
    }

    public function testRegularAndEmptyCategoriesPreserveRoutesButOnlyContentPagesEnterSitemap(): void
    {
        $classicCategory = [
            'slug' => 'classic-tours',
            'theme_slug' => null,
            'is_theme' => 0,
        ];

        $this->assertSame(
            base_url('tours/classic-tours'),
            itinerary_category_public_canonical_url($classicCategory)
        );
        $this->assertFalse(itinerary_category_sitemap_indexable($classicCategory + ['itinerary_count' => 0]));
        $this->assertTrue(itinerary_category_sitemap_indexable($classicCategory + ['itinerary_count' => 2]));
    }

    public function testExperienceTemplateEmitsItsExistingSchemaGraph(): void
    {
        $source = file_get_contents(__DIR__ . '/../experience.php');
        $this->assertIsString($source);
        $schemaPosition = strrpos($source, 'schema_output($schemaGraph);');
        $footerPosition = strrpos($source, "require __DIR__ . '/includes/footer.php';");

        $this->assertNotFalse($schemaPosition);
        $this->assertNotFalse($footerPosition);
        $this->assertLessThan($footerPosition, $schemaPosition);
    }

    public function testActualCategoryQueryRendersCanonicalThemeCardsEvenWithoutItineraries(): void
    {
        $category = fetch_one("SELECT id FROM itinerary_categories WHERE slug = 'honeymoon' LIMIT 1");
        $this->assertNotNull($category, 'The local honeymoon theme is required for this integration test.');
        db()->beginTransaction();
        try {
            execute_query("UPDATE itinerary_categories SET is_published = 1, is_theme = 1, theme_slug = 'honeymoon-tours' WHERE id = :id", ['id' => $category['id']]);
            execute_query('UPDATE itineraries SET is_published = 0 WHERE category_id = :id', ['id' => $category['id']]);
            $rows = fetch_published_itinerary_categories_with_counts();
            $themes = array_values(array_filter($rows, static fn(array $row): bool => $row['slug'] === 'honeymoon'));
            $this->assertCount(1, $themes);
            $this->assertSame('honeymoon-tours', $themes[0]['theme_slug']);
            $this->assertSame(1, (int) $themes[0]['is_theme']);
            $this->assertSame(0, (int) $themes[0]['itinerary_count']);
            $this->assertSame(base_url('honeymoon-tours'), itinerary_category_public_canonical_url($themes[0]));
            $this->assertTrue(db()->inTransaction(), 'Query integration checks must preserve fixture rollback.');
        } finally {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
        }
        // Rendering invokes runtime DDL guards, so do it only after fixture rollback.
        ob_start();
        try {
            require __DIR__ . '/../tours.php';
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        $this->assertMatchesRegularExpression(
            '~class="related-link-card[^\"]*" href="' . preg_quote(e(base_url('honeymoon-tours')), '~') . '"~',
            $html
        );
    }
}
