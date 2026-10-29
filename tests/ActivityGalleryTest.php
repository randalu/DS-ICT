<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/activity_pages.php';
require_once __DIR__.'/../includes/activity_gallery_view.php';
require_once __DIR__.'/../includes/image_manager.php';

final class ActivityGalleryTest extends TestCase
{
    private int $page; private int $otherPage; private array $entries=[]; private int $foreignEntry; private array $assets=[];
    protected function setUp():void
    {
        if(!activity_gallery_available()){$this->markTestSkipped('Activity entry gallery migration required.');}
        db()->beginTransaction();$suffix=bin2hex(random_bytes(8));
        foreach(['page','otherPage'] as $property){
            execute_query("INSERT INTO activity_pages(name,slug,excerpt,meta_title,meta_description,research_sources_json,research_verified_at,hero_image,cover_image,cover_alt,og_image) VALUES('Gallery fixture',:slug,'Preserve overview','Gallery title','Gallery description','[\"https://example.test/source\"]','2026-10-25','assets/images/home-hero/home-1.jpg','assets/images/home-hero/home-1.jpg','A test landscape','assets/images/home-hero/home-1.jpg')",['slug'=>$property.'-'.$suffix]);
            $this->$property=(int)db()->lastInsertId();
        }
        for($i=0;$i<3;$i++){
            execute_query("INSERT INTO activity_examples(activity_page_id,title,destinations_json,source_urls_json,last_verified_at,sort_order) VALUES(:page,:title,'[\"Kandy\"]','[\"https://example.test/source\"]','2026-10-25',:sort)",['page'=>$this->page,'title'=>'Entry '.$i,'sort'=>$i]);$this->entries[]=(int)db()->lastInsertId();
        }
        execute_query("INSERT INTO activity_examples(activity_page_id,title,destinations_json) VALUES(:page,'Foreign entry','[\"Galle\"]')",['page'=>$this->otherPage]);$this->foreignEntry=(int)db()->lastInsertId();
        for($i=1;$i<=6;$i++){
            execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES(:path,'Gallery fixture','image/jpeg',100,'A landscape photograph')",['path'=>'assets/images/home-hero/home-'.$i.'.jpg']);$this->assets[]=(int)db()->lastInsertId();
        }
    }
    protected function tearDown():void{if(db()->inTransaction()){db()->rollBack();}}
    private function input(string $action='add',?int $entry=null):array
    {
        $entry??=$this->entries[0];return ['type'=>'activity','id'=>$this->page,'example_id'=>$entry,'action'=>'activity_gallery_'.$action,'revision'=>activity_gallery_revision($entry),'source'=>'library','asset_id'=>$this->assets[0],'alt_text'=>'A landscape at the activity destination'];
    }
    private function add(int $asset=0,?int $entry=null):array
    {
        $input=$this->input('add',$entry);$input['asset_id']=$this->assets[$asset];activity_gallery_save($input);return activity_gallery_images((int)$input['example_id']);
    }
    private function reject(callable $call,string $class=InvalidArgumentException::class):void
    {
        try{$call();$this->fail('Invalid change was accepted.');}catch(Throwable $error){$this->assertInstanceOf($class,$error);}
    }
    private function payload():array
    {
        $page=fetch_one('SELECT * FROM activity_pages WHERE id=:id',['id'=>$this->page]);
        return ['id'=>$this->page,'revision'=>activity_content_revision($this->page),'name'=>$page['name'],'slug'=>$page['slug'],'excerpt'=>$page['excerpt'],'meta_title'=>$page['meta_title'],'meta_description'=>$page['meta_description'],'research_sources'=>'https://example.test/source','research_verified_at'=>'2026-10-25','examples'=>array_map(static fn($e)=>['id'=>(int)$e['id'],'title'=>$e['title'],'destinations'=>'Kandy','sources'=>'https://example.test/source','last_verified_at'=>'2026-10-25'],activity_examples($this->page))];
    }
    public function testMaximumAndDuplicateAssignmentsAreEnforced():void
    {
        $this->add();$this->reject(fn()=>activity_gallery_save($this->input()));
        for($i=1;$i<5;$i++){$this->add($i);}
        $sixth=$this->input();$sixth['asset_id']=$this->assets[5];$this->reject(fn()=>activity_gallery_save($sixth));
        $this->assertCount(5,activity_gallery_images($this->entries[0]));
    }
    public function testReplacementAltOrderAndRemovalPreserveContent():void
    {
        $before=fetch_one('SELECT name,excerpt,is_published FROM activity_pages WHERE id=:id',['id'=>$this->page]);
        $this->add();$photos=$this->add(1);$ids=array_map(static fn($p)=>(int)$p['id'],$photos);
        $replace=$this->input('replace');$replace['photo_id']=$ids[0];$replace['asset_id']=$this->assets[2];activity_gallery_save($replace);
        $alt=$this->input('alt');unset($alt['asset_id'],$alt['source']);$alt['photo_id']=$ids[0];$alt['alt_text']='A different descriptive photograph';activity_gallery_save($alt);
        $order=$this->input('order');unset($order['asset_id'],$order['source']);$order['order']=json_encode(array_reverse($ids));activity_gallery_save($order);
        $this->assertSame(array_reverse($ids),array_map(static fn($p)=>(int)$p['id'],activity_gallery_images($this->entries[0])));
        $remove=$this->input('remove');unset($remove['asset_id'],$remove['source']);$remove['photo_id']=$ids[1];activity_gallery_save($remove);
        $remaining=activity_gallery_images($this->entries[0]);$this->assertCount(1,$remaining);$this->assertSame('A different descriptive photograph',$remaining[0]['alt_text']);
        $this->assertSame($before,fetch_one('SELECT name,excerpt,is_published FROM activity_pages WHERE id=:id',['id'=>$this->page]));
        $this->assertSame(1,activity_gallery_reference_count($this->assets[2]));
        $this->assertNotNull(fetch_one('SELECT id FROM media_assets WHERE id=:id',['id'=>$this->assets[1]]));
    }
    public function testStaleConcurrentAddCannotExceedFive():void
    {
        for($i=0;$i<4;$i++){$this->add($i);}
        $one=$this->input();$two=$one;$one['asset_id']=$this->assets[4];$two['asset_id']=$this->assets[5];activity_gallery_save($one);
        $this->reject(fn()=>activity_gallery_save($two),DomainException::class);$this->assertCount(5,activity_gallery_images($this->entries[0]));
    }
    public function testForeignEntriesPhotosAndInvalidOrdersAreRejected():void
    {
        $foreign=$this->input('add',$this->foreignEntry);$this->reject(fn()=>activity_gallery_save($foreign));
        $photos=$this->add();$this->add(1,$this->entries[1]);
        $bad=$this->input('alt',$this->entries[1]);unset($bad['asset_id'],$bad['source']);$bad['photo_id']=(int)$photos[0]['id'];$this->reject(fn()=>activity_gallery_save($bad));
        $order=$this->input('order');unset($order['asset_id'],$order['source']);$order['order']='[1,1]';$this->reject(fn()=>activity_gallery_save($order));
    }
    public function testInvalidAltMissingMediaAndUnsupportedFilesLeaveGalleryUnchanged():void
    {
        foreach(['','<b>Photo</b>',str_repeat('x',256)] as $alt){$bad=$this->input();$bad['alt_text']=$alt;$this->reject(fn()=>activity_gallery_save($bad));}
        $bad=$this->input();$bad['asset_id']=PHP_INT_MAX;$this->reject(fn()=>activity_gallery_save($bad));
        execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES('assets/missing-activity-image.jpg','Missing','image/jpeg',1)");$bad['asset_id']=(int)db()->lastInsertId();$this->reject(fn()=>activity_gallery_save($bad));
        $bad=$this->input();$bad['source']='upload';$bad['asset_id']=0;$this->reject(fn()=>activity_gallery_save($bad,['tmp_name'=>__FILE__,'error'=>UPLOAD_ERR_OK,'name'=>'fake.jpg','size'=>100]));
        $this->assertSame([],activity_gallery_images($this->entries[0]));
    }
    public function testTextEditsAndReorderingPreserveEntryIdsAndGalleryAssociations():void
    {
        $photos=$this->add();$payload=$this->payload();$payload['examples']=array_reverse($payload['examples']);$payload['examples'][2]['title']='A revised entry';
        activity_admin_save($payload);
        $this->assertSame(array_reverse($this->entries),array_map(static fn($e)=>(int)$e['id'],activity_examples($this->page)));
        $this->assertSame($photos,activity_gallery_images($this->entries[0]));
        $stale=$this->payload();$this->add(1);$this->reject(fn()=>activity_admin_save($stale),DomainException::class);
    }
    public function testRemovingEntryWithImagesRequiresConfirmationAndRetainsAsset():void
    {
        $this->add();$payload=$this->payload();array_shift($payload['examples']);
        $this->reject(fn()=>activity_admin_save($payload));
        $payload['confirmed_removals']=json_encode([$this->entries[0]]);activity_admin_save($payload);
        $this->assertSame([],activity_gallery_images($this->entries[0]));$this->assertCount(2,activity_examples($this->page));
        $this->assertNotNull(fetch_one('SELECT id FROM media_assets WHERE id=:id',['id'=>$this->assets[0]]));
    }
    public function testPublishedSaveRequiresImagesForEveryEntryAndFinalImageCannotBeRemoved():void
    {
        $payload=$this->payload();$payload['is_published']='1';$this->reject(fn()=>activity_admin_save($payload));
        foreach($this->entries as $index=>$entry){$this->add($index,$entry);}
        $payload=$this->payload();$payload['is_published']='1';activity_admin_save($payload);
        $photo=activity_gallery_images($this->entries[0])[0];$remove=$this->input('remove');unset($remove['asset_id'],$remove['source']);$remove['photo_id']=(int)$photo['id'];$this->reject(fn()=>activity_gallery_save($remove));
        $this->assertCount(1,activity_gallery_images($this->entries[0]));
    }
    public function testMediaAssetDeletionIsRestrictedByForeignKey():void
    {
        $this->add();$this->assertSame(1,activity_gallery_reference_count($this->assets[0]));
        $this->reject(fn()=>execute_query('DELETE FROM media_assets WHERE id=:id',['id'=>$this->assets[0]]),PDOException::class);
    }
    public function testOneTwoAndFiveImageMarkupHasOriginalLinksAndResponsiveSources():void
    {
        foreach([1,2,5] as $count){
            while(count(activity_gallery_images($this->entries[0]))<$count){$this->add(count(activity_gallery_images($this->entries[0])));}
            ob_start();activity_gallery_public(activity_gallery_images($this->entries[0]),'An entry');$html=ob_get_clean();
            $dom=new DOMDocument();@$dom->loadHTML($html);$xpath=new DOMXPath($dom);
            $this->assertSame($count,$xpath->query('//*[@data-activity-slide]')->length);
            $this->assertSame($count===1?0:$count,$xpath->query('//*[@data-activity-thumb]')->length);
            $this->assertSame($count-1,$xpath->query('//*[@data-activity-slide and @hidden]')->length);
            $this->assertStringContainsString('data-lightbox-ignore',$html);$this->assertStringContainsString('width="900"',$html);
        }
    }
    public function testForeignAndDuplicatedContentIdsCannotMoveEntryGalleries():void
    {
        $this->add();$foreign=$this->payload();$foreign['examples'][0]['id']=$this->foreignEntry;$this->reject(fn()=>activity_admin_save($foreign));
        $duplicate=$this->payload();$duplicate['examples'][1]['id']=$this->entries[0];$this->reject(fn()=>activity_admin_save($duplicate));
        $this->assertSame($this->entries,array_map(static fn($e)=>(int)$e['id'],activity_examples($this->page)));
        $this->assertCount(1,activity_gallery_images($this->entries[0]));
    }
    public function testExchangingTitlesKeepsImagesWithTheirOriginalEntryIds():void
    {
        $first=$this->add();$second=$this->add(1,$this->entries[1]);$payload=$this->payload();
        [$payload['examples'][0]['title'],$payload['examples'][1]['title']]=[$payload['examples'][1]['title'],$payload['examples'][0]['title']];
        activity_admin_save($payload);$this->assertSame($first,activity_gallery_images($this->entries[0]));$this->assertSame($second,activity_gallery_images($this->entries[1]));
    }
    public function testMalformedIdentifiersAndDuplicateFileAliasesAreRejected():void
    {
        $photos=$this->add();
        foreach(['id'=>'1x','example_id'=>-1,'photo_id'=>(int)$photos[0]['id'],'asset_id'=>-1] as $field=>$value){$bad=$this->input();$bad[$field]=$value;$this->reject(fn()=>activity_gallery_save($bad));}
        execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size) VALUES('assets/images/home-hero/home-1.jpg','Alias','image/jpeg',100)");
        $alias=$this->input();$alias['asset_id']=(int)db()->lastInsertId();$this->reject(fn()=>activity_gallery_save($alias));
        $this->assertCount(1,activity_gallery_images($this->entries[0]));
    }
}
