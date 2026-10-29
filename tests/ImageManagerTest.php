<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_manager.php';

final class ImageManagerTest extends TestCase
{
    private array $ids = [];
    private int $asset;
    private string $path;

    protected function setUp(): void
    {
        db()->beginTransaction();
        $suffix = bin2hex(random_bytes(8));
        foreach (image_manager_types() as $type => $definition) {
            if(!in_array($type,['destination','itinerary','tour','day_tour','activity'],true))continue; // Other media types are covered by PageMediaTest.
            $intro=$type==='activity'?'excerpt':'intro';
            execute_query('INSERT INTO ' . $definition['table'] . ' (name,slug,'.$intro.',is_published) VALUES (:name,:slug,:intro,0)',
                ['name' => 'Image manager fixture', 'slug' => 'im-test-' . $suffix, 'intro' => 'Preserve detailed content']);
            $this->ids[$type] = (int) db()->lastInsertId();
        }
        $this->path = 'image-manager-test-' . $suffix . '.png';
        file_put_contents(UPLOAD_BASE_PATH . '/' . $this->path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jWZkAAAAASUVORK5CYII='));
        execute_query('INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES(:path,:name,\'image/png\',68)', ['path' => $this->path, 'name' => 'Fixture.png']);
        $this->asset = (int) db()->lastInsertId();
    }
    protected function tearDown(): void
    {
        if (db()->inTransaction()) { db()->rollBack(); }
        if (isset($this->path) && is_file(UPLOAD_BASE_PATH . '/' . $this->path)) { unlink(UPLOAD_BASE_PATH . '/' . $this->path); }
    }
    private function input(string $type, string $slot, int $child = 0): array
    {
        $id = $this->ids[$type];
        return ['type' => $type, 'id' => $id, 'slot' => $slot, 'child_id' => $child,
            'revision' => image_manager_slot($type, image_manager_record($type, $id), $slot, $child)['revision'],
            'source' => 'library', 'asset_id' => $this->asset];
    }
    public function testHeroPlacementAltPreservesReusableDefaultAndHeroMetadata(): void
    {
        if(!db_table_exists('media_placement_alts'))$this->markTestSkipped('Placement alt migration required.');
        $input=$this->input('destination','hero_image');$input['caption']='A river flowing through forest';
        image_manager_save($input);$before=image_manager_record('destination',$this->ids['destination']);
        $input=$this->input('destination','hero_image');$input['source']='caption';$input['asset_id']=0;$input['caption']='A forest river beside the walking route';
        $saved=image_manager_save($input);$after=image_manager_record('destination',$this->ids['destination']);
        $this->assertSame('A forest river beside the walking route',$saved['caption']);
        $this->assertSame('A river flowing through forest',fetch_one('SELECT alt_text FROM media_assets WHERE id=:id',['id'=>$this->asset])['alt_text']);
        foreach(['hero_image','hero_media_type','hero_media_asset_id','hero_video_poster','is_published','intro']as$field){$this->assertSame($before[$field]??null,$after[$field]??null);}
        $this->expectException(DomainException::class);image_manager_save($input);
    }
    public function testImageOnlyUpdatesAcrossAllContentTypesAndDuplicateRejection(): void
    {
        foreach ($this->ids as $type => $id) {
            $before = image_manager_record($type, $id);
            $input = $this->input($type, 'og_image');
            $saved = image_manager_save($input);
            $after = image_manager_record($type, $id);
            $this->assertSame($this->path, $after['og_image']);
            unset($before['og_image'], $after['og_image'], $before['updated_at'], $after['updated_at']);
            $this->assertSame($before, $after);
            $this->assertNotSame($input['revision'], $saved['revision']);
            try { image_manager_save($input); $this->fail('Duplicate save accepted'); }
            catch (DomainException) { $this->addToAssertionCount(1); }
        }
    }
    public function testDayOwnershipAndGroupedEntriesPreserveAllDayDetails(): void
    {
        execute_query('INSERT INTO itinerary_days(itinerary_id,day_number,day_end,title,summary) VALUES(:id,3,4,\'Grouped day\',\'Preserve summary\')', ['id' => $this->ids['itinerary']]);
        $child = (int) db()->lastInsertId();
        $before = fetch_one('SELECT * FROM itinerary_days WHERE id=:id', ['id' => $child]);
        image_manager_save($this->input('itinerary', 'day', $child));
        $after = fetch_one('SELECT * FROM itinerary_days WHERE id=:id', ['id' => $child]);
        $this->assertSame($this->path, $after['image_path']);
        unset($before['image_path'], $after['image_path'], $before['updated_at'], $after['updated_at']);
        $this->assertSame($before, $after);
        $this->expectException(InvalidArgumentException::class);
        image_manager_slot('itinerary', ['id' => 0], 'day', $child);
    }
    public function testGalleryAddReplaceAppendAndReplayProtection(): void
    {
        $input = $this->input('destination', 'gallery_add') + ['caption' => 'A Sri Lankan landscape'];
        $added = image_manager_save($input);
        $this->assertGreaterThan(0, $added['child_id']);
        try { image_manager_save($input); $this->fail('Duplicate gallery addition accepted'); }
        catch (DomainException) { $this->addToAssertionCount(1); }
        $replace = $this->input('destination', 'gallery', $added['child_id']) + ['caption' => 'Updated photo description'];
        image_manager_save($replace);
        $photo = fetch_one('SELECT * FROM destination_photos WHERE id=:id', ['id' => $added['child_id']]);
        $this->assertSame(0, (int) $photo['sort_order']);
        $this->assertSame('Updated photo description', $photo['caption']);
        image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Second photograph']);
        $children = image_manager_children('destination', $this->ids['destination']);
        $this->assertSame([0, 1], array_map(static fn(array $row): int => (int) $row['sort_order'], $children));
    }
    public function testVideoToImageClearsVideoMetadataAndPoster(): void
    {
        execute_query('UPDATE destinations SET hero_image=\'old.mp4\',hero_media_type=\'video\',hero_video_poster=\'old.jpg\' WHERE id=:id', ['id' => $this->ids['destination']]);
        $saved = image_manager_save($this->input('destination', 'hero_image'));
        $parent = image_manager_record('destination', $this->ids['destination']);
        $this->assertSame('image', $parent['hero_media_type']);
        $this->assertNull($parent['hero_video_poster']);
        $this->assertSame($this->asset, (int) $parent['hero_media_asset_id']);
        $this->assertNull($saved['poster']);
    }
    public function testLegacyDestinationCaptionsPreserveTheShorterLibraryDefault(): void
    {
        $long = str_repeat('A', 300);
        $added = image_manager_save($this->input('destination', 'gallery_add') + ['caption' => $long]);
        $photo = fetch_one('SELECT caption FROM destination_photos WHERE id=:id', ['id' => $added['child_id']]);
        $this->assertSame($long, $photo['caption']);
        $this->assertSame('', (string) fetch_one('SELECT alt_text FROM media_assets WHERE id=:id', ['id' => $this->asset])['alt_text']);
        image_manager_gallery_manage($this->galleryAction('gallery_caption', ['child_id' => $added['child_id'], 'caption' => str_repeat('B', 299)]));
        $this->assertSame(str_repeat('B', 299), image_manager_children('destination', $this->ids['destination'])[0]['caption']);
        $input = $this->input('destination', 'gallery', $added['child_id']) + ['caption' => $long, 'update_asset_alt' => '1'];
        try { image_manager_save($input); $this->fail('An oversized reusable default was accepted.'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        $this->assertSame(str_repeat('B', 299), image_manager_children('destination', $this->ids['destination'])[0]['caption']);
        foreach (['<b>Unsafe caption</b>', "Unsafe\ncaption", str_repeat('x', 301)] as $invalid) {
            try { image_manager_gallery_manage($this->galleryAction('gallery_caption', ['child_id' => $added['child_id'], 'caption' => $invalid])); $this->fail('Invalid destination caption was accepted.'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }
    private function galleryAction(string $action, array $extra = []): array
    {
        return $extra + ['action' => $action, 'id' => $this->ids['destination'],
            'revision' => image_manager_gallery_revision($this->ids['destination'])];
    }
    public function testGalleryAssignmentAndCrossDestinationRejection(): void
    {
        $before = image_manager_record('destination', $this->ids['destination']);
        image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Destination landscape']);
        $asset = fetch_one('SELECT * FROM media_assets WHERE id=:id', ['id' => $this->asset]);
        $this->assertSame($this->ids['destination'], (int) $asset['destination_id']);
        $this->assertSame($before, image_manager_record('destination', $this->ids['destination']));
        execute_query('UPDATE media_assets SET destination_id=999999 WHERE id=:id', ['id' => $this->asset]);
        try { image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Wrong destination']); $this->fail('Foreign asset accepted'); }
        catch (InvalidArgumentException) { $this->assertCount(1, image_manager_children('destination', $this->ids['destination'])); }
        // Other content image slots may still reuse that asset without changing its destination.
        image_manager_save($this->input('tour', 'og_image'));
        $this->assertSame(999999, (int) fetch_one('SELECT destination_id FROM media_assets WHERE id=:id', ['id' => $this->asset])['destination_id']);
    }
    public function testPublicGalleryRequiresExplicitLibrarySelection(): void
    {
        $input = $this->input('destination', 'gallery_add') + ['caption' => 'Unselected upload'];
        $input['source'] = 'upload'; $input['asset_id'] = 0;
        try { image_manager_save($input); $this->fail('Device upload could add a public gallery photo'); }
        catch (InvalidArgumentException $error) { $this->assertStringContainsString('Media Library first', $error->getMessage()); }
        $this->assertSame([], image_manager_children('destination', $this->ids['destination']));
        image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Explicitly selected library photo']);
        $this->assertCount(1, image_manager_children('destination', $this->ids['destination']));
    }
    public function testGalleryCaptionOrderRemovalAndStaleActions(): void
    {
        $one = image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'First landscape'])['child_id'];
        $two = image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Second landscape'])['child_id'];
        $stale = $this->galleryAction('gallery_remove', ['child_id' => $one]);
        image_manager_gallery_manage($this->galleryAction('gallery_caption', ['child_id' => $one, 'caption' => 'New landscape description']));
        $this->assertSame('New landscape description', image_manager_children('destination', $this->ids['destination'])[0]['caption']);
        try { image_manager_gallery_manage($stale); $this->fail('Stale removal accepted'); }
        catch (DomainException) { $this->addToAssertionCount(1); }
        image_manager_gallery_manage($this->galleryAction('gallery_order', ['order' => json_encode([$two, $one])]));
        $this->assertSame([$two, $one], array_map('intval', array_column(image_manager_children('destination', $this->ids['destination']), 'id')));
        $assetBefore = fetch_one('SELECT * FROM media_assets WHERE id=:id', ['id' => $this->asset]);
        image_manager_gallery_manage($this->galleryAction('gallery_remove', ['child_id' => $one]));
        $this->assertCount(1, image_manager_children('destination', $this->ids['destination']));
        $this->assertFileExists(UPLOAD_BASE_PATH . '/' . $this->path);
        $this->assertSame($assetBefore, fetch_one('SELECT * FROM media_assets WHERE id=:id', ['id' => $this->asset]));
        $this->assertSame($this->path, image_manager_children('destination', $this->ids['destination'])[0]['image_path']);
        $this->assertSame(0, (int) image_manager_record('destination', $this->ids['destination'])['is_published']);
    }
    public function testGalleryRejectsInvalidMembershipCaptionAndParent(): void
    {
        $one = image_manager_save($this->input('destination', 'gallery_add') + ['caption' => 'Landscape'])['child_id'];
        $before = image_manager_children('destination', $this->ids['destination']);
        foreach (['[]', '[' . $one . ',' . $one . ']', '[999999]', '["' . $one . '"]', '{"photo":1}', 'invalid'] as $order) {
            try { image_manager_gallery_manage($this->galleryAction('gallery_order', ['order' => $order])); $this->fail('Bad order accepted'); }
            catch (InvalidArgumentException) { $this->assertSame($before, image_manager_children('destination', $this->ids['destination'])); }
        }
        foreach (['', str_repeat('x', 301)] as $caption) {
            try { image_manager_gallery_manage($this->galleryAction('gallery_caption', ['child_id' => $one, 'caption' => $caption])); $this->fail('Bad caption accepted'); }
            catch (InvalidArgumentException) { $this->assertSame($before, image_manager_children('destination', $this->ids['destination'])); }
        }
        foreach (['gallery_remove', 'gallery_caption'] as $action) {
            try { image_manager_gallery_manage($this->galleryAction($action, ['child_id' => 999999, 'caption' => 'Valid description'])); $this->fail('Foreign photo accepted'); }
            catch (InvalidArgumentException) { $this->assertSame($before, image_manager_children('destination', $this->ids['destination'])); }
        }
        $this->expectException(InvalidArgumentException::class);
        image_manager_gallery_manage($this->galleryAction('gallery_remove', ['id' => 999999, 'child_id' => $one]));
    }
    public function testInvalidAssetsAndSourcesLeaveExistingContentUnchanged(): void
    {
        $input = $this->input('tour', 'hero_image');
        $before = image_manager_record('tour', $this->ids['tour']);
        foreach (['missing', 'ambiguous', 'caption', 'type', 'slot', 'mime'] as $case) {
            $bad = $input;
            if ($case === 'missing') { $bad['asset_id'] = 0; }
            if ($case === 'ambiguous') { $bad['source'] = 'upload'; }
            if ($case === 'caption') { $bad = $this->input('destination', 'gallery_add') + ['caption' => '']; }
            if ($case === 'type') { $bad['type'] = 'tours;DELETE'; }
            if ($case === 'slot') { $bad['slot'] = 'intro'; }
            if ($case === 'mime') { execute_query('UPDATE media_assets SET mime_type=\'video/mp4\' WHERE id=:id', ['id' => $this->asset]); }
            try { image_manager_save($bad); $this->fail('Invalid case accepted: ' . $case); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
            $this->assertSame($before, image_manager_record('tour', $this->ids['tour']));
            $this->assertTrue(db()->inTransaction());
        }
    }
    public function testMissingLocalAssetCannotReplaceImage(): void
    {
        unlink(UPLOAD_BASE_PATH . '/' . $this->path);
        $this->expectException(InvalidArgumentException::class);
        image_manager_save($this->input('tour', 'hero_image'));
    }
    public function testAllowlistedLoginReturnAndMimeLimits(): void
    {
        $this->assertSame('admin/image_manager.php', image_manager_return('image_manager'));
        foreach (['https://evil.test', '//evil.test', '../image_manager.php', ['image_manager']] as $value) {
            $this->assertSame('admin/dashboard.php', image_manager_return($value));
        }
        $this->assertSame(2 * 1048576, image_manager_ini_bytes('2M'));
        $this->assertLessThanOrEqual(20 * 1048576, image_manager_limit(false));
        $this->assertSame('video', image_manager_validate_mime('video/mp4', true)['media_type']);
        $this->expectException(InvalidArgumentException::class);
        image_manager_validate_mime('image/heic', false);
    }
    public function testStaleExternalEditAndCredentialBinding(): void
    {
        $input = $this->input('tour', 'hero_image');
        execute_query("UPDATE tours SET hero_image='external-editor.jpg' WHERE id=:id", ['id' => $this->ids['tour']]);
        try { image_manager_save($input); $this->fail('External edit overwritten'); }
        catch (DomainException) { $this->addToAssertionCount(1); }
        $this->assertSame('external-editor.jpg', image_manager_record('tour', $this->ids['tour'])['hero_image']);
        $session = [ADMIN_CREDENTIAL_STAMP_KEY => admin_credential_stamp(9, 'synthetic-hash')];
        $this->assertTrue(admin_session_credentials_match($session, 9, 'synthetic-hash'));
        $this->assertFalse(admin_session_credentials_match($session, 9, 'changed-synthetic-hash'));
        $this->assertFalse(admin_session_credentials_match($session, 10, 'synthetic-hash'));
        $this->assertFalse(admin_session_credentials_match([], 9, 'synthetic-hash'));
    }
}
