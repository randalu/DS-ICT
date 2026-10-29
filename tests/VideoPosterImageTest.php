<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/detail_redesign_helpers.php';
require_once __DIR__ . '/../includes/related_grid.php';

final class VideoPosterImageTest extends TestCase
{
    public function testStillImagesKeepTheCallersOrder(): void
    {
        $record=['hero_image'=>'hero.jpg','cover_image'=>'cover.jpg','og_image'=>'social.jpg'];
        $this->assertSame('hero.jpg',content_image_path($record));
        $this->assertSame('cover.jpg',content_image_path($record,['cover_image','hero_image']));
    }

    public function testDeclaredVideoUsesPosterEvenWithAnOpaqueVideoUrl(): void
    {
        $record=['hero_media_type'=>'video','hero_image'=>'https://media.example.test/watch?id=1','hero_video_poster'=>'posters/travel.jpg','og_image'=>'social.jpg'];
        $this->assertSame('posters/travel.jpg',content_image_path($record));
        $this->assertSame(stored_media_url('posters/travel.jpg'),detail_image_from_record($record));
        $this->assertSame('posters/travel.jpg',related_grid_image($record));
        $player=content_hero_media($record);
        $this->assertSame('video',$player['type']);
        $this->assertSame($record['hero_image'],$player['url']);
        $this->assertSame(stored_media_url('posters/travel.jpg'),$player['poster_url']);
    }

    public function testLegacyVideoPathsAreRejectedWithoutMediaColumns(): void
    {
        foreach(['hero.mp4','hero.WEBM?version=1','https://media.example.test/a.MOV#preview','video%2Emp4'] as $video) {
            $this->assertSame('social.jpg',content_image_path(['hero_image'=>$video,'og_image'=>'social.jpg']));
            $this->assertSame('',content_image_path(['hero_image'=>$video]));
        }
    }

    public function testInvalidPosterFallsThroughAndNeverBecomesAnImage(): void
    {
        $record=['hero_image'=>'hero.mp4','hero_video_poster'=>'bad.webm','og_image'=>'bad.mp4','cover_image'=>'cover.jpg'];
        $this->assertSame('cover.jpg',content_image_path($record));
        $this->assertSame('fallback.jpg',content_image_path($record,['hero_image','og_image'],'fallback.jpg'));
        $this->assertSame('',content_image_path($record,['hero_image'],'bad.mp4'));
        $this->assertSame(DEFAULT_OG_IMAGE,detail_image_from_record(['hero_image'=>'hero.mp4']));
        $this->assertSame('',related_grid_image(['hero_image'=>'hero.mp4']));
    }

    public function testEmptyRecordsKeepExistingFallbackBehavior(): void
    {
        $this->assertSame('',content_image_path([]));
        $this->assertSame('',related_grid_image([]));
        $this->assertSame(DEFAULT_OG_IMAGE,detail_image_from_record([]));
        $this->assertFalse(media_path_is_video('poster.jpg?name=hero.mp4'));
    }

    public function testMissingOptionalColumnsUseNullProjections(): void
    {
        $this->assertSame('NULL AS hero_media_type, NULL AS hero_video_poster',content_image_select_fragment('video_poster_absent_fixture','x'));
        $this->expectException(InvalidArgumentException::class);
        content_image_select_fragment('destinations; DROP TABLE x');
    }
}
