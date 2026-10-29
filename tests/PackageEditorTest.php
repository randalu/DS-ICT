<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/seo_manager.php';
require_once __DIR__ . '/../includes/image_manager.php';
require_once __DIR__ . '/../includes/site_audit_portal.php';
require_once __DIR__ . '/../includes/package_gallery.php';
require_once __DIR__ . '/../includes/tour_guides.php';

final class PackageEditorTest extends TestCase
{
    private array $ids = [];
    private int $asset;
    protected function setUp(): void
    {
        if (!db_column_exists('tours', 'coverage_json') || !db_column_exists('day_tours', 'cover_image')) { $this->markTestSkipped('Package migrations required.'); }
        db()->beginTransaction(); $suffix = bin2hex(random_bytes(8));
        foreach (package_tables() as $type => $table) {
            execute_query("INSERT INTO $table(name,slug,intro,is_published,hero_image,og_image) VALUES(:name,:slug,'Original wording',0,'assets/images/home-hero/home-5.jpg','assets/images/home-hero/home-3.jpg')", ['name' => 'Editor fixture', 'slug' => 'package-test-' . $suffix]);
            $this->ids[$type] = (int) db()->lastInsertId();
        }
        execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES('assets/images/home-hero/home-1.jpg','Fixture cover','image/jpeg',10)");
        $this->asset = (int) db()->lastInsertId();
    }
    protected function tearDown(): void { if (db()->inTransaction()) { db()->rollBack(); } }
    private function imageInput(string $type, string $slot, int $child = 0): array
    {
        $row = image_manager_record($type, $this->ids[$type]);
        return ['type' => $type, 'id' => $row['id'], 'slot' => $slot, 'child_id' => $child, 'revision' => image_manager_slot($type, $row, $slot, $child)['revision'], 'source' => 'library', 'asset_id' => $this->asset, 'caption' => 'Tea country landscape'];
    }
    private function coverage(): array
    {
        $cards = [['title' => 'Original experience', 'location' => 'Exact place', 'description' => 'Original description', 'status' => 'optional', 'destination_id' => 0, 'experience_id' => 0, 'image_asset_id' => 0, 'image_alt' => '']];
        execute_query('UPDATE tours SET coverage_json=:json WHERE id=:id', ['id' => $this->ids['tour'], 'json' => json_encode($cards)]); return $cards;
    }
    public function testSeparateCoversPreserveHeroesSocialImagesAndOtherCollections(): void
    {
        foreach ($this->ids as $type => $id) {
            $before = image_manager_record($type, $id); $input = $this->imageInput($type, 'cover_image');
            image_manager_save($input); $after = image_manager_record($type, $id);
            $this->assertSame('assets/images/home-hero/home-1.jpg', $after['cover_image']);
            $this->assertSame($this->asset, (int) $after['cover_media_asset_id']);
            $this->assertSame('Tea country landscape', package_card_alt($after));
            $this->assertStringEndsWith('home-1.jpg', package_card_image($after));
            foreach (['cover_image','cover_alt','cover_media_asset_id','updated_at'] as $key) { unset($before[$key], $after[$key]); }
            $this->assertSame($before, $after);
            try { image_manager_save($input); $this->fail('Stale cover accepted'); } catch (DomainException) { $this->addToAssertionCount(1); }
        }
    }
    public function testFullEditorCoverOmissionAndClearAreExplicit(): void
    {
        $created = null; $row = image_manager_record('tour', $this->ids['tour']);
        $this->assertSame([], package_cover_updates('tour', $row, [], [], $created));
        $updates = package_cover_updates('tour', $row, ['cover_present' => '1', 'cover_alt' => 'Cover description', 'cover_image_media_asset_id' => (string) $this->asset], [], $created);
        $this->assertSame(['cover_alt','cover_image','cover_media_asset_id'], array_keys($updates));
        $this->assertSame(['cover_image' => null, 'cover_media_asset_id' => null, 'cover_alt' => null], package_cover_updates('tour', $row, ['cover_present' => '1', 'cover_clear' => '1'], [], $created));
        $this->assertNull($created);
    }
    public function testCoverageImageAndAltOnlySavesPreserveAllExperienceData(): void
    {
        $before = $this->coverage(); $input = $this->imageInput('tour', 'coverage', 1); image_manager_save($input);
        $after = tour_coverage_decode(image_manager_record('tour', $this->ids['tour'])['coverage_json']);
        $this->assertSame($this->asset, $after[0]['image_asset_id']); $this->assertSame('Tea country landscape', $after[0]['image_alt']);
        foreach (['image_asset_id','image_alt'] as $key) { unset($before[0][$key], $after[0][$key]); } $this->assertSame($before, $after);
        $input = $this->imageInput('tour', 'coverage', 1); $input['source'] = 'caption'; $input['asset_id'] = 0; $input['caption'] = 'New image description';
        image_manager_save($input); $this->assertSame('New image description', image_manager_present('tour', image_manager_record('tour', $this->ids['tour']), 'coverage', 1)['caption']);
    }
    public function testSeoCoverageHistoryAndStructuralRestoreGuard(): void
    {
        $cards = $this->coverage(); $id = $this->ids['tour']; $present = seo_present('tour', $id);
        $this->assertArrayHasKey('coverage_0_description', $present['fields']);
        $saved = seo_save('tour', $id, $present['revision'], ['coverage_0_description' => 'Edited experience wording'], null, 1);
        $after = tour_coverage_decode(image_manager_record('tour', $id)['coverage_json']); $cards[0]['description'] = 'Edited experience wording'; $this->assertSame($cards, $after);
        $revisionId = $saved['revisions'][0]['id'];
        $restored = seo_restore('tour', $id, $saved['revision'], (int) $revisionId, 1); $this->assertSame('Original description', $restored['fields']['coverage_0_description']['value']);
        $cards[0]['location'] = 'A different exact place'; execute_query('UPDATE tours SET coverage_json=:json WHERE id=:id', ['id' => $id, 'json' => json_encode($cards)]);
        $this->expectException(DomainException::class); seo_restore('tour', $id, seo_present('tour', $id)['revision'], (int) $revisionId, 1);
    }
    public function testSeoDayWordingPreservesFactsAndDetectsConcurrentImageChanges(): void
    {
        $id = $this->ids['itinerary']; execute_query("INSERT INTO itinerary_days(itinerary_id,day_number,title,summary,overnight_location,travel_time) VALUES(:id,1,'Original day','Original summary','Kandy','3 hours')", ['id' => $id]); $day = (int) db()->lastInsertId();
        $present = seo_present('itinerary', $id); $saved = seo_save('itinerary', $id, $present['revision'], ['day_' . $day . '_title' => 'New day title', 'day_' . $day . '_highlights_json' => '["Garden visit"]'], null, 1);
        $row = fetch_one('SELECT * FROM itinerary_days WHERE id=:id', ['id' => $day]); $this->assertSame('Kandy', $row['overnight_location']); $this->assertSame('3 hours', $row['travel_time']); $this->assertSame('New day title', $row['title']);
        image_manager_save($this->imageInput('itinerary', 'day', $day));
        $this->expectException(DomainException::class); seo_save('itinerary', $id, $saved['revision'], ['day_' . $day . '_summary' => 'Stale text'], null, 1);
    }
    public function testDayTourSeoAndAuditExposeCompleteFieldsAndEditors(): void
    {
        $id = $this->ids['day_tour']; $present = seo_present('day_tour', $id);
        $saved = seo_save('day_tour', $id, $present['revision'], ['itinerary' => '<p>Detailed day experiences</p>', 'includes' => 'Confirmed services'], null, 1);
        $this->assertSame('Confirmed services', $saved['fields']['includes']['value']);
        $inventory = package_field_inventory('day_tour', $id); $this->assertSame(array_keys(image_manager_record('day_tour', $id)), array_keys($inventory['record']));
        $this->assertArrayHasKey('day_tour_gallery', $inventory['children']);
        $links = audit_editor_links(['type' => 'day_tour', 'id' => $id]); $this->assertArrayHasKey('images', $links); $this->assertArrayHasKey('seo', $links); $this->assertArrayHasKey('fields', $links);
        $this->expectException(InvalidArgumentException::class); seo_save('day_tour', $id, $saved['revision'], ['price_from' => '1'], null, 1);
    }
    public function testSeasonEditingPreservesExtraDataAndAcceptsExplicitStructuredReplacement(): void
    {
        $original = '{"months":["january"],"ratings":{"january":"best"},"note":"Original"}';
        $this->assertSame($original, package_season_json($original, [], 'best_time_json', 'best_time_months', 'best_time_note'));
        $updated = json_decode(package_season_json($original, ['best_time_months' => ['february'], 'best_time_note' => 'Updated'], 'best_time_json', 'best_time_months', 'best_time_note'), true);
        $this->assertSame(['january' => 'best'], $updated['ratings']); $this->assertSame(['february'], $updated['months']);
        $rich = '{"months":[{"month":"january","rating":"best"}]}';
        $this->assertSame($rich, package_season_json($rich, ['best_time_note' => 'Unrelated'], 'best_time_json', 'best_time_months', 'best_time_note'));
        $this->assertSame($rich, package_season_json($original, ['best_time_json_override' => '1', 'best_time_json_raw' => $rich], 'best_time_json', 'best_time_months', 'best_time_note'));
    }
    public function testGallerySeoIncludesCaptionsAndProtectsOtherPhotoFields(): void
    {
        $id = $this->ids['day_tour'];
        execute_query("INSERT INTO day_tour_gallery(day_tour_id,image_path,alt_text,sort_order) VALUES(:id,'assets/images/home-hero/home-5.jpg','Original photo',7)", ['id' => $id]); $photo = (int) db()->lastInsertId();
        $present = seo_present('day_tour', $id); $field = 'gallery_' . $photo . '_alt_text';
        $this->assertFalse($present['fields'][$field]['ai']);
        seo_save('day_tour', $id, $present['revision'], [$field => 'Coastal scenery'], null, 1);
        $row = fetch_one('SELECT * FROM day_tour_gallery WHERE id=:id', ['id' => $photo]);
        $this->assertSame('Coastal scenery', $row['alt_text']); $this->assertSame(7, (int) $row['sort_order']); $this->assertSame('assets/images/home-hero/home-5.jpg', $row['image_path']);
        $this->expectException(InvalidArgumentException::class); seo_save('tour', $this->ids['tour'], seo_present('tour', $this->ids['tour'])['revision'], [$field => 'Wrong parent'], null, 1);
    }
    public function testAuditInventoriesEveryPackageFieldAndCoverageImage(): void
    {
        $this->coverage(); $id = $this->ids['tour'];
        execute_query('UPDATE tours SET is_published=1,cover_image=:path,cover_alt=:alt,cover_media_asset_id=:asset WHERE id=:id', ['id' => $id, 'path' => 'assets/images/home-hero/home-1.jpg', 'alt' => 'Distinct cover', 'asset' => $this->asset]);
        image_manager_save($this->imageInput('tour', 'coverage', 1));
        $inventory = audit_inventory(); $item = null;
        foreach ($inventory['items'] as $candidate) { if ($candidate['type'] === 'tour' && $candidate['id'] === $id) { $item = $candidate; break; } }
        $this->assertNotNull($item); $this->assertArrayHasKey('coverage_json', $item['field_inventory']); $this->assertArrayHasKey('cover_media_asset_id', $item['field_inventory']);
        $this->assertStringEndsWith('home-1.jpg', $item['media']['cover_image']); $this->assertStringEndsWith('home-1.jpg', $item['child_media']['coverage:1']);
        $this->assertSame(array_keys(image_manager_record('tour', $id)), array_keys($item['field_inventory']));
    }
    public function testTravellerChoicesUseNativeSubmittedValuesAndOmissionPreservesOlderForms(): void
    {
        $this->assertNull(package_traveller_types(['traveller_types' => 'family']));
        $this->assertSame('', package_traveller_types(['traveller_types_present' => '1']));
        $this->assertSame('family,couple', package_traveller_types(['traveller_types_present' => '1', 'traveller_types' => 'stale-hidden-value', 'traveller_types_raw' => ['family','couple','family']]));
        $this->expectException(InvalidArgumentException::class);
        package_traveller_types(['traveller_types_present' => '1', 'traveller_types_raw' => ['<b>family</b>']]);
    }
    public function testImageSitemapKeepsCoverHeroSocialAndCoveragePhotography(): void
    {
        $this->coverage(); $id = $this->ids['tour']; image_manager_save($this->imageInput('tour', 'cover_image')); image_manager_save($this->imageInput('tour', 'coverage', 1));
        $row = image_manager_record('tour', $id); $images = package_sitemap_images($row, 'tour');
        foreach (['home-1.jpg','home-3.jpg','home-5.jpg'] as $name) { $this->assertNotEmpty(array_filter($images, static fn($url) => str_ends_with($url, $name))); }
        $this->assertCount(3, $images);
        $row['hero_image'] = 'example.mp4'; $row['hero_media_type'] = 'video'; $row['hero_video_poster'] = 'assets/images/home-hero/home-2.jpg';
        $images = package_sitemap_images($row, 'tour'); $this->assertCount(3, $images);
        $this->assertEmpty(array_filter($images, static fn($url) => str_ends_with($url, '.mp4')));
    }
    public function testRetiredGuideControlsAreAbsentFromInventoryAndSeo(): void
    {
        $tour=image_manager_record('tour',$this->ids['tour']);
        $this->assertFalse(tour_guides_available()); $this->assertSame([],tour_guide_rows($tour));
        $this->assertArrayNotHasKey('Planning guides',package_editor_links('tour',$tour['id']));
        $this->assertArrayHasKey('Route, days and stays',package_editor_links('tour',$tour['id']));
        $this->assertNull(package_seo_definition('guide_1_informational_label'));
        $this->assertArrayNotHasKey('itinerary_package_links',package_field_inventory('tour',$tour['id'])['relationships']);
    }
    public function testRetiredGuideApiRejectsStaleCreationRequests(): void
    {
        $this->expectException(DomainException::class);
        tour_guide_save($this->ids['tour'],['informational_path'=>'/10-day-itinerary','package_path'=>'/tours/example']);
    }
    public function testGalleryAddEditStaleRejectionAndRemovalPreserveTheMediaAsset(): void
    {
        $id = $this->ids['day_tour'];
        $input = ['revision' => package_gallery_revision($id), 'gallery_image_media_asset_id' => (string) $this->asset, 'alt_text' => 'Tea country photograph', 'sort_order' => '4'];
        package_gallery_save($id, $input, []); $row = image_manager_children('day_tour', $id)[0];
        $this->assertSame('Tea country photograph', $row['alt_text']); $this->assertSame(4, (int) $row['sort_order']);
        $edit = ['revision' => package_gallery_revision($id), 'photo_id' => (string) $row['id'], 'alt_text' => 'Scenic tea country', 'sort_order' => '2'];
        package_gallery_save($id, $edit, []); $after = image_manager_children('day_tour', $id)[0];
        $this->assertSame($row['image_path'], $after['image_path']); $this->assertSame(2, (int) $after['sort_order']);
        try { package_gallery_save($id, $edit, []); $this->fail('Stale gallery accepted'); } catch (DomainException) { $this->addToAssertionCount(1); }
        $this->assertSame($after, image_manager_children('day_tour', $id)[0]);
        package_gallery_save($id, ['revision' => package_gallery_revision($id), 'photo_id' => (string) $row['id'], 'action' => 'remove'], []);
        $this->assertSame([], image_manager_children('day_tour', $id));
        $this->assertNotNull(fetch_one('SELECT id FROM media_assets WHERE id=:id', ['id' => $this->asset]));
    }
    public function testExistingLibrarySelectionIsNeverMarkedAsANewFileForCleanup(): void
    {
        $oldPost = $_POST; $oldFiles = $_FILES;
        try {
            $_POST = ['hero_image_media_asset_id' => (string) $this->asset]; $_FILES = []; $created = [];
            $media = admin_resolve_media_field('hero_image', 'itineraries', [], true, $created);
            $this->assertSame([], $created); $this->assertSame($this->asset, $media['asset_id']); $this->assertSame('assets/images/home-hero/home-1.jpg', $media['path']);
        } finally { $_POST = $oldPost; $_FILES = $oldFiles; }
    }
}
