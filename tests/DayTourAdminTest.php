<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/day_tour_admin.php';
final class DayTourAdminTest extends TestCase
{
    private int $id; private int $itinerary; private string $suffix;
    protected function setUp():void
    {
        db()->beginTransaction();$this->suffix=bin2hex(random_bytes(6));
        $this->id=1+max((int)db()->query('SELECT COALESCE(MAX(id),0) FROM tours')->fetchColumn(),(int)db()->query('SELECT COALESCE(MAX(id),0) FROM day_tours')->fetchColumn());
        foreach(['tours','day_tours'] as $table){execute_query("INSERT INTO {$table}(id,name,slug,intro,is_published,hero_image,og_image) VALUES(:id,:name,:slug,'Preserved introduction',0,'old-hero.jpg','old-og.jpg')",['id'=>$this->id,'name'=>$table.' fixture','slug'=>'day-editor-'.$this->suffix]);}
        execute_query("INSERT INTO itineraries(name,slug,intro,is_published) VALUES('Related itinerary',:slug,'Preserved',0)",['slug'=>'day-editor-'.$this->suffix]);$this->itinerary=(int)db()->lastInsertId();
        execute_query('INSERT INTO day_tour_itinerary(day_tour_id,itinerary_id,day_number,sort_order) VALUES(:id,:related,4,6)',['id'=>$this->id,'related'=>$this->itinerary]);
        execute_query("INSERT INTO day_tour_gallery(day_tour_id,image_path,alt_text,sort_order) VALUES(:id,'old-gallery.jpg','Old gallery description',7)",['id'=>$this->id]);
    }
    protected function tearDown():void{if(db()->inTransaction())db()->rollBack();}
    private function input(array $extra=[]):array{return $extra+['id'=>$this->id,'revision'=>day_tour_admin_revision(day_tour_admin_record($this->id))];}
    public function testFullEditorSavesDayTourWithoutChangingPackagesGalleryOrRelationshipMetadata():void
    {
        $package=fetch_one('SELECT * FROM tours WHERE id=:id',['id'=>$this->id]);$before=day_tour_admin_record($this->id);$gallery=image_manager_children('day_tour',$this->id);$links=fetch_all('SELECT * FROM day_tour_itinerary WHERE day_tour_id=:id',['id'=>$this->id]);
        day_tour_admin_save($this->input(['name'=>'Updated day tour','intro'=>'Detailed new introduction','price_from'=>'20','price_to'=>'40','best_time_json'=>'{"months":["jan"],"note":"Morning"}','relations_present'=>['itineraries'=>'1'],'itineraries'=>[(string)$this->itinerary]]));
        $after=day_tour_admin_record($this->id);$this->assertSame('Updated day tour',$after['name']);$this->assertSame('Detailed new introduction',$after['intro']);$this->assertSame('20.00',$after['price_from']);$this->assertSame($before['hero_image'],$after['hero_image']);$this->assertSame($before['og_image'],$after['og_image']);$this->assertSame($before['is_published'],$after['is_published']);$this->assertSame($package,fetch_one('SELECT * FROM tours WHERE id=:id',['id'=>$this->id]));$this->assertSame($gallery,image_manager_children('day_tour',$this->id));$this->assertSame($links,fetch_all('SELECT * FROM day_tour_itinerary WHERE day_tour_id=:id',['id'=>$this->id]));
    }
    public function testInvalidDataAndForgedRelationshipsLeaveDayTourUnchanged():void
    {
        $before=day_tour_admin_record($this->id);
        foreach([['name'=>''],['faq_json'=>'broken'],['faq_json'=>'"text"'],['best_time_json'=>'{'],['price_from'=>'-1'],['price_from'=>'100','price_to'=>'20'],['difficulty'=>'invalid'],['start_time'=>'25:00'],['max_group_size'=>'1.5'],['canonical_url'=>'javascript:alert(1)'],['relations_present'=>['itineraries'=>'1'],'itineraries'=>['999999999']]] as $invalid){
            try{day_tour_admin_save($this->input($invalid));$this->fail('Invalid payload accepted');}catch(InvalidArgumentException){$this->assertSame($before,day_tour_admin_record($this->id));$this->assertTrue(db()->inTransaction());}
        }
    }
    public function testStaleEditorCannotOverwriteAnImageManagerUpdate():void
    {
        $input=$this->input(['intro'=>'Stale edit']);execute_query("UPDATE day_tours SET hero_image='new-image.jpg' WHERE id=:id",['id'=>$this->id]);
        try{day_tour_admin_save($input);$this->fail('Stale editor accepted');}catch(DomainException){$this->assertSame('new-image.jpg',day_tour_admin_record($this->id)['hero_image']);$this->assertSame('Preserved introduction',day_tour_admin_record($this->id)['intro']);}
    }
    public function testOmittedRelationshipsStayAndExplicitEmptySelectionRemovesOnlyThoseLinks():void
    {
        day_tour_admin_save($this->input(['name'=>'No link change']));$this->assertCount(1,fetch_all('SELECT * FROM day_tour_itinerary WHERE day_tour_id=:id',['id'=>$this->id]));
        day_tour_admin_save($this->input(['relations_present'=>['itineraries'=>'1'],'itineraries'=>[]]));$this->assertSame([],fetch_all('SELECT * FROM day_tour_itinerary WHERE day_tour_id=:id',['id'=>$this->id]));$this->assertCount(1,image_manager_children('day_tour',$this->id));
    }
    public function testNewDayTourIsDraftAndCannotOverwriteSameIdInPackages():void
    {
        $id=day_tour_admin_save(['revision'=>day_tour_admin_revision([]),'name'=>'New day tour','slug'=>'new-day-'.$this->suffix,'intro'=>'New introduction']);$row=day_tour_admin_record($id);$this->assertSame(0,(int)$row['is_published']);$this->assertSame('New day tour',$row['name']);$this->assertSame('tours fixture',fetch_one('SELECT name FROM tours WHERE id=:id',['id'=>$this->id])['name']);
    }
    public function testDayTourImageManagerGalleryOwnershipAndUnrelatedFields():void
    {
        $path='day-image-'.$this->suffix.'.png';file_put_contents(UPLOAD_BASE_PATH.'/'.$path,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jWZkAAAAASUVORK5CYII='));
        try{
            execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES(:path,'Day fixture','image/png',68)",['path'=>$path]);$asset=(int)db()->lastInsertId();$parent=day_tour_admin_record($this->id);$photo=image_manager_children('day_tour',$this->id)[0];
            image_manager_save(['type'=>'day_tour','id'=>$this->id,'slot'=>'day_tour_gallery','child_id'=>$photo['id'],'caption'=>'New gallery description','source'=>'library','asset_id'=>$asset,'revision'=>image_manager_slot('day_tour',$parent,'day_tour_gallery',(int)$photo['id'])['revision']]);
            $after=image_manager_children('day_tour',$this->id)[0];$this->assertSame($path,$after['image_path']);$this->assertSame('New gallery description',$after['alt_text']);$this->assertSame(7,(int)$after['sort_order']);$this->assertSame($parent,day_tour_admin_record($this->id));$this->assertSame('tours',image_manager_type('tour')['table']);$this->assertSame('Tour Packages',image_manager_type('tour')['label']);$this->assertSame('day_tour_form.php',image_manager_type('day_tour')['form']);
            $this->expectException(InvalidArgumentException::class);image_manager_slot('day_tour',['id'=>0],'day_tour_gallery',(int)$photo['id']);
        }finally{if(is_file(UPLOAD_BASE_PATH.'/'.$path))unlink(UPLOAD_BASE_PATH.'/'.$path);}
    }
}
