<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/tour_program.php';
require_once __DIR__.'/../includes/image_manager.php';
require_once __DIR__.'/../includes/retired_package_pages.php';
require_once __DIR__.'/../includes/package_fields.php';

final class TourProgramTest extends TestCase
{
    private int $tour;private int $asset;
    protected function setUp():void
    {
        if(!db_column_exists('tours','program_json')){$this->markTestSkipped('Tour programme schema required.');}
        db()->beginTransaction();
        execute_query("INSERT INTO tours(name,slug,intro) VALUES ('Programme fixture',:s,'Preserved overview')",['s'=>'programme-'.bin2hex(random_bytes(8))]);$this->tour=(int)db()->lastInsertId();
        execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES ('assets/images/home-hero/home-5.jpg','Programme photo','image/jpeg',100,'Rock fortress and fields')");$this->asset=(int)db()->lastInsertId();
    }
    protected function tearDown():void{if(db()->inTransaction()){db()->rollBack();}}
    private function program():array{return ['route'=>'Airport → Sigiriya → coast','days'=>[['day_start'=>1,'day_end'=>1,'title'=>'Explore the cultural region','location'=>'Sigiriya','summary'=>'A flexible private sightseeing day.','highlights'=>['A heritage visit','Time to rest'],'overnight'=>'Sigiriya','travel_time'=>'Confirm with the route','image_asset_id'=>0,'image_alt'=>'']],'hotels'=>[['name'=>'Proposed lodge','location'=>'Sigiriya','category'=>'Boutique','description'=>'Confirm the property in the quote.','nights'=>2,'room_type'=>'To be confirmed','board_basis'=>'To be confirmed','status'=>'proposed','image_asset_id'=>0,'image_alt'=>'']],'sections'=>[['title'=>'Transfer details','body'=>'<p>Allow time for comfort stops.</p>']]];}
    private function save(array $program):array{$before=image_manager_record('tour',$this->tour);tour_program_save($this->tour,$program,tour_coverage_revision($before['program_json']??null));return image_manager_record('tour',$this->tour);}
    private function photoInput(string $slot='program_day'):array{$r=image_manager_record('tour',$this->tour);return ['type'=>'tour','id'=>$this->tour,'slot'=>$slot,'child_id'=>1,'source'=>'library','asset_id'=>$this->asset,'caption'=>'Rock fortress and fields','revision'=>image_manager_slot('tour',$r,$slot,1)['revision']];}
    public function testProgrammeSaveOwnsRouteDaysHotelsAndPreservesOtherFields():void
    {
        $before=image_manager_record('tour',$this->tour);$after=$this->save($this->program());$decoded=tour_program_decode($after['program_json']);
        $this->assertSame('Sigiriya',$decoded['days'][0]['location']);$this->assertSame(2,$decoded['hotels'][0]['nights']);$this->assertCount(2,$decoded['days'][0]['highlights']);
        unset($before['program_json'],$before['updated_at'],$after['program_json'],$after['updated_at']);$this->assertSame($before,$after);
        $inventory=package_field_inventory('tour',$this->tour);$this->assertCount(1,$inventory['children']['programme_hotels']);
    }
    public function testProgrammeTextSaveRejectsConcurrentImageChanges():void
    {
        $saved=$this->save($this->program());$revision=tour_coverage_revision($saved['program_json']);image_manager_save($this->photoInput());
        $this->expectException(DomainException::class);tour_program_save($this->tour,$this->program(),$revision);
    }
    public function testDayAndHotelImagesAndAltSavesPreserveAllProgrammeWording():void
    {
        $this->save($this->program());
        foreach(['program_day'=>'days','program_hotel'=>'hotels'] as $slot=>$group){$before=tour_program_decode(image_manager_record('tour',$this->tour)['program_json']);$input=$this->photoInput($slot);image_manager_save($input);$after=tour_program_decode(image_manager_record('tour',$this->tour)['program_json']);
            $this->assertSame($this->asset,$after[$group][0]['image_asset_id']);$this->assertSame('Rock fortress and fields',$after[$group][0]['image_alt']);
            unset($before[$group][0]['image_asset_id'],$before[$group][0]['image_alt'],$after[$group][0]['image_asset_id'],$after[$group][0]['image_alt']);$this->assertSame($before,$after);
            try{image_manager_save($input);$this->fail('Stale photograph accepted');}catch(DomainException){$this->addToAssertionCount(1);}
        }
        $this->assertSame(2,tour_program_media_reference_count($this->asset));
        $images=package_sitemap_images(image_manager_record('tour',$this->tour),'tour');
        $this->assertCount(1,$images);$this->assertStringEndsWith('home-5.jpg',$images[0]);
    }
    public function testReorderingOrRemovingProgrammeRowsInvalidatesImageSlot():void
    {
        $this->save($this->program());$input=$this->photoInput();$program=$this->program();$program['days'][0]['title']='A changed day';$this->save($program);
        $this->expectException(DomainException::class);image_manager_save($input);
    }
    public function testWrongTypeAndMissingProgrammeRowsCannotBeChanged():void
    {
        $this->save($this->program());$r=image_manager_record('tour',$this->tour);
        foreach([['tour','program_day',2],['tour','program_hotel',0],['itinerary','program_day',1]] as [$type,$slot,$id]){try{image_manager_slot($type,$r,$slot,$id);$this->fail('Foreign/missing row accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}}
    }
    public function testProgrammeValidationRejectsAmbiguousRangesAndUnsafeImageClaims():void
    {
        $cases=[];$p=$this->program();$p['days'][]=$p['days'][0];$cases[]=$p;
        $p=$this->program();$p['days'][0]['day_end']=0;$cases[]=$p;
        $p=$this->program();$p['days'][0]['image_asset_id']=$this->asset;$cases[]=$p;
        $p=$this->program();$p['hotels'][0]['status']='guaranteed';$cases[]=$p;
        $p=$this->program();$p['days'][0]['title']='<script>bad()</script>';$cases[]=$p;
        foreach($cases as $case){try{tour_program_normalize($case);$this->fail('Invalid programme accepted');}catch(InvalidArgumentException){$this->addToAssertionCount(1);}}
    }
    public function testRetiredReferenceRoutesAndSavedLinksCannotBeReintroduced():void
    {
        foreach(['/5-day-itinerary','/99-day-itinerary/','/n_day_itinerary.php?days=9'] as $path){$this->assertTrue(package_reference_path_retired($path));}
        $this->assertFalse(package_reference_path_retired('/itineraries/a-private-route'));
        $html=render_public_content('<p><a href="/9-day-itinerary">Plan</a></p>');$this->assertStringNotContainsString('/9-day-itinerary',$html);$this->assertStringContainsString(base_url('tours'),$html);
        $this->assertNull(find_published_static_page_by_slug('9-day-itinerary'));
        if(!function_exists('seo_normalize_canonical')){ob_start();require_once __DIR__.'/../includes/seo.php';ob_end_clean();}
        $this->assertSame(base_url('tours/example'),seo_normalize_canonical(base_url('9-day-itinerary'),base_url('tours/example')));
    }
    public function testDatabaseGuardsRejectRetiredPageAndConnectionCreation():void
    {
        $queries=["INSERT INTO n_day_itinerary_pages(days,title) VALUES(59,'Retired')","INSERT INTO itinerary_package_links(informational_path,package_slug,package_path) VALUES('/59-day-itinerary','retired','/tours/retired')","INSERT INTO static_pages(title,slug) VALUES('Retired','59-day-itinerary')"];
        foreach($queries as $query){try{execute_query($query);$this->fail('Retired content recreated');}catch(PDOException){$this->addToAssertionCount(1);}}
    }
    public function testReservedCmsSlugIsRejectedEvenWithoutStrictSqlMode():void
    {
        $mode=(string)fetch_one('SELECT @@SESSION.sql_mode AS mode')['mode'];
        try{
            db()->exec("SET SESSION sql_mode=''");
            try{execute_query("INSERT INTO static_pages(title,slug) VALUES('Retired','59-day-itinerary')");$this->fail('Reserved slug created in permissive SQL mode');}
            catch(PDOException $e){$this->assertSame('45000',$e->getCode());}
        }finally{db()->exec('SET SESSION sql_mode='.db()->quote($mode));}
    }
}
