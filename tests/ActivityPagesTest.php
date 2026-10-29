<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/activity_seed_data.php';

final class ActivityPagesTest extends TestCase
{
    public function testSeedHasHubAndAllSixteenRequestedCategories(): void
    {
        $pages = activity_seed_data();
        $hubs = array_values(array_filter($pages, static fn(array $page): bool => !empty($page['is_hub'])));
        $categories = array_values(array_filter($pages, static fn(array $page): bool => empty($page['is_hub'])));
        $this->assertCount(1, $hubs);
        $this->assertSame('activities', $hubs[0]['slug']);
        $this->assertCount(16, $categories);
        $this->assertSame([
            'arts-and-crafts','camping-and-hiking','boat-safari','paragliding','gemstones','night-life',
            'jeep-safari','culture-and-history','sports-and-adventure','spa-and-ayurveda','food-and-drink',
            'bird-watching','architectural-tours','hot-air-ballooning','cycling-tours','city-tours',
        ], array_column($categories, 'slug'));
    }

    public function testExamplesHaveDestinationsAndResearchReferences(): void
    {
        foreach (activity_seed_data() as $page) {
            if (!empty($page['is_hub'])) { continue; }
            $minimum = $page['slug'] === 'hot-air-ballooning' ? 1 : 3;
            $this->assertGreaterThanOrEqual($minimum, count($page['examples']), $page['slug']);
            $this->assertLessThanOrEqual(5, count($page['examples']), $page['slug']);
            foreach ($page['examples'] as $example) {
                $this->assertNotSame([], $example['destinations'], $page['slug'] . ': destination list');
                $this->assertNotSame([], $example['sources'], $page['slug'] . ': source list');
                foreach ($example['sources'] as $source) {
                    $this->assertMatchesRegularExpression('~^https://~', $source);
                }
            }
        }
    }

    public function testActivityRoutesManagersAndSitemapsAreRegistered(): void
    {
        $root = dirname(__DIR__);
        $routes = file_get_contents($root . '/.htaccess');
        $header = file_get_contents($root . '/includes/header.php');
        $imageManager = file_get_contents($root . '/includes/image_manager.php');
        $seoManager = file_get_contents($root . '/includes/seo_manager.php');
        $audit = file_get_contents($root . '/includes/site_audit_portal.php');
        $sitemap = file_get_contents($root . '/sitemap_partial.php');
        $this->assertStringContainsString('^activities/?$', $routes);
        $this->assertStringContainsString('^activities/([a-z0-9-]+)/?$', $routes);
        $this->assertStringContainsString("base_url('activities')", $header);
        $this->assertStringContainsString("['activity'] = ['table' => 'activity_pages'", $imageManager);
        $this->assertStringContainsString("'activity' => ['table' => 'activity_pages'", $seoManager);
        $this->assertStringContainsString("'activity' => ['activity_pages','activities/'", $audit);
        $this->assertStringContainsString("if (\$type === 'activities' || \$withImages)", $sitemap);
    }
}
