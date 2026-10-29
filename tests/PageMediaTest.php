<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/page_gallery_view.php';
require_once __DIR__.'/../includes/seo_manager.php';
final class PageMediaTest extends TestCase {
    protected function setUp(): void {
        if(!preg_match('/^ict_page_media_test_[a-f0-9]{12}$/D',DB_NAME))$this->markTestSkipped('Run against the isolated page-media rehearsal schema.');
        db()->beginTransaction();
    }
    protected function tearDown(): void {if(db()->inTransaction())db()->rollBack();}
    private function input(string $type,string $action='add'): array {
        $id=$type==='blog_landing'?1:101;$asset=(int)fetch_one('SELECT id FROM media_assets ORDER BY id LIMIT 1')['id'];
        return ['type'=>$type,'id'=>$id,'action'=>$action,'source'=>'library','asset_id'=>$asset,'description'=>'A descriptive landscape photograph','revision'=>page_media_revision($type,page_media_record($type,$id))];
    }
    private function rejects(callable $work,string $class=InvalidArgumentException::class): void {try{$work();$this->fail('Invalid operation was accepted.');}catch(Throwable $e){$this->assertInstanceOf($class,$e);}}
    public function testEveryTypeSupportsIndependentGalleryAndRetainsParentContent(): void {
        foreach(page_media_types() as $type=>$def){$input=$this->input($type);$before=page_media_record($type,(int)$input['id']);$count=count(page_gallery_rows($type,(int)$input['id']));page_media_save($input);$this->assertCount($count+1,page_gallery_rows($type,(int)$input['id']));$after=page_media_record($type,(int)$input['id']);$this->assertSame($before,$after);}
    }
    public function testStaleRevisionRejectsSecondWriteForEveryType(): void {
        foreach(page_media_types() as $type=>$def){$input=$this->input($type);page_media_save($input);$this->rejects(fn()=>page_media_save($input),DomainException::class);}
    }
    public function testDescriptionReplaceOrderAndRemovalRetainSharedFile(): void {
        foreach(['destination','experience','day_tour','itinerary'] as $type){
            $input=$this->input($type,'description');$photos=page_gallery_rows($type,101);$input['child_id']=$photos[0]['id'];unset($input['source'],$input['asset_id']);page_media_save($input);
            $photos=page_gallery_rows($type,101);$this->assertSame('A descriptive landscape photograph',$photos[0]['alt_text']);
            $input=$this->input($type,'replace');$input['child_id']=$photos[0]['id'];$input['asset_id']=(int)fetch_one("SELECT id FROM media_assets WHERE file_path='assets/images/country-market/matara-beach.jpg' ORDER BY id LIMIT 1")['id'];page_media_save($input);
            $photos=page_gallery_rows($type,101);$this->assertSame('assets/images/country-market/matara-beach.jpg',$photos[0]['image_path']);
            $input=$this->input($type,'order');unset($input['source'],$input['asset_id']);$ids=array_map('intval',array_column($photos,'id'));$input['order']=json_encode(array_reverse($ids));page_media_save($input);
            $this->assertSame(array_reverse($ids),array_map('intval',array_column(page_gallery_rows($type,101),'id')));
            $input=$this->input($type,'remove');unset($input['source'],$input['asset_id']);$input['child_id']=$photos[0]['id'];page_media_save($input);$this->assertCount(4,page_gallery_rows($type,101));$this->assertFileExists(__DIR__.'/../assets/images/country-market/matara-beach.jpg');
        }
    }
    public function testInvalidOwnershipOrderDescriptionsSourcesAndIdentifiersAreRejected(): void {
        foreach(['child_id'=>'999999','id'=>'101x','asset_id'=>'-1'] as $key=>$value){$input=$this->input('itinerary','replace');$input['child_id']=page_gallery_rows('itinerary',101)[0]['id'];$input[$key]=$value;$this->rejects(fn()=>page_media_save($input));}
        foreach(['','<b>Photo</b>',str_repeat('x',256)] as $description){$input=$this->input('itinerary');$input['description']=$description;$this->rejects(fn()=>page_media_save($input));}
        $input=$this->input('itinerary','order');$input['order']='[1,1]';$this->rejects(fn()=>page_media_save($input),DomainException::class);
        $input=$this->input('itinerary');$input['asset_id']=PHP_INT_MAX;$this->rejects(fn()=>page_media_save($input));
        $input=$this->input('itinerary');$input['source']='upload';$input['asset_id']=0;$this->rejects(fn()=>page_media_save($input,['name'=>'fake.jpg','tmp_name'=>__FILE__,'error'=>UPLOAD_ERR_OK,'size'=>100]));
    }
    public function testMediaSlotsSaveDescriptionsAndRemovalWithoutPublishing(): void {
        foreach(['blog','page','experience','blog_landing'] as $type){foreach(['hero_image'=>'hero_alt','cover_image'=>'cover_alt','og_image'=>'og_alt'] as $slot=>$alt){$input=$this->input($type,'slot');$input['slot']=$slot;page_media_save($input);$record=page_media_record($type,(int)$input['id']);$this->assertSame($input['description'],$record[$alt]);$input=$this->input($type,'slot');$input['slot']=$slot;$input['source']='remove';unset($input['asset_id']);page_media_save($input);$this->assertNull(page_media_record($type,(int)$input['id'])[$slot]);}}
    }
    public function testAiTargetsBindToOwnedPagePhotosWithoutChangingContent(): void {
        require_once __DIR__.'/../includes/image_alt_context.php';
        foreach(page_media_types() as $type=>$definition){
            $id=$type==='blog_landing'?1:101;$record=page_media_record($type,$id);
            $photo=page_gallery_rows($type,$id)[0];
            $target=page_media_alt_target($type,$id,'gallery',(int)$photo['id']);
            $this->assertSame($photo['image_path'],$target['path']);
            $context=image_alt_context(['page_media'=>'1','type'=>$type,'id'=>$id,'slot'=>'gallery','child_id'=>$photo['id']]);
            $this->assertSame($record[$definition[1]],$context['references']['page']);
            $this->assertSame('Sri Lanka',$context['references']['country']);
            $this->assertSame($record,page_media_record($type,$id));
            $this->rejects(fn()=>page_media_alt_target($type,$id,'gallery',PHP_INT_MAX));
        }
    }
    public function testPageAltSaveKeepsLibraryDefaultUnlessExplicitAndRejectsStaleDefault(): void {
        require_once __DIR__.'/../includes/media_alt.php';
        $photo=page_gallery_rows('blog',101)[0];$asset=media_alt_asset($photo['image_path']);
        $input=$this->input('blog','description');unset($input['asset_id'],$input['source']);$input['child_id']=$photo['id'];
        $revision=media_alt_revision($asset);page_media_save($input);
        $this->assertSame($asset['alt_text'],fetch_one('SELECT alt_text FROM media_assets WHERE id=:id',['id'=>$asset['id']])['alt_text']);
        $input['revision']=page_media_revision('blog',page_media_record('blog',101));$input['update_asset_alt']='1';$input['asset_alt_revision']=$revision;
        page_media_save($input);
        $this->assertSame($input['description'],fetch_one('SELECT alt_text FROM media_assets WHERE id=:id',['id'=>$asset['id']])['alt_text']);
        $input['description']='A conflicting stale description';$input['revision']=page_media_revision('blog',page_media_record('blog',101));
        $this->rejects(fn()=>page_media_save($input),DomainException::class);
        $this->assertSame('A descriptive landscape photograph',page_gallery_rows('blog',101)[0]['alt_text']);
    }
    public function testNewSeoEditorLinksUseTheExistingBrowserContract(): void {
        foreach(['experience','page','blog_landing'] as $type){
            $page=seo_present($type,$type==='blog_landing'?1:101);
            $this->assertTrue(array_is_list($page['editor_links']));
            foreach($page['editor_links'] as $link){$this->assertNotEmpty($link['label']);$this->assertStringStartsWith(APP_URL,$link['url']);}
        }
    }
    public function testSharedGalleryAssetCannotBeDeleted(): void {$row=fetch_one('SELECT media_asset_id FROM page_gallery_images LIMIT 1');$this->rejects(fn()=>execute_query('DELETE FROM media_assets WHERE id=:id',['id'=>$row['media_asset_id']]),PDOException::class);}
    public function testEveryEditorAndGalleryKeepsVisibleAiControls(): void {
        require_once __DIR__.'/../includes/page_media_admin.php';
        foreach(page_media_types() as $type=>$definition){
            $id=$type==='blog_landing'?1:101;
            ob_start();page_media_manager($type,$id);$html=ob_get_clean();
            $dom=new DOMDocument();@$dom->loadHTML($html);$xp=new DOMXPath($dom);
            $forms=$xp->query('//form[@data-pm-form]');
            $this->assertGreaterThanOrEqual(9,$forms->length,$type);
            foreach($forms as $form){
                $this->assertSame(1,$xp->query('.//button[@data-alt-generate]',$form)->length,$type);
                $this->assertSame(1,$xp->query('.//button[@data-alt-apply]',$form)->length,$type);
                $this->assertSame(1,$xp->query('.//input[@name="alt_context_revision"]',$form)->length,$type);
                $this->assertSame(1,$xp->query('.//input[@name="page_media" and @value="1"]',$form)->length,$type);
            }
        }
    }
    public function testAiControlsNeverRenderTheServerCredential(): void {
        require_once __DIR__.'/../includes/page_media_admin.php';
        $before=getenv('OPENAI_ADMIN_API_KEY');$secret='synthetic-server-key-must-not-appear-in-html';
        try{
            putenv('OPENAI_ADMIN_API_KEY='.$secret);
            ob_start();page_media_manager('blog',101);$html=ob_get_clean();
            $this->assertStringNotContainsString($secret,$html);
            $this->assertStringContainsString('data-alt-generate',$html);
            $this->assertSame($secret,admin_openai_key());
            $this->assertStringNotContainsString('name="api_key"',$html);
        }finally{putenv($before===false?'OPENAI_ADMIN_API_KEY':'OPENAI_ADMIN_API_KEY='.$before);}
    }
    public function testGalleryMarkupHandlesZeroOneThroughFiveAndLargerCollections(): void {
        foreach([0,1,2,3,4,5,8] as $count){$rows=[];for($i=0;$i<$count;$i++)$rows[]=['image_path'=>'assets/images/home-hero/home-1.jpg','alt_text'=>'Photograph '.$i];ob_start();page_gallery_public($rows,'Fixture');$html=ob_get_clean();if(!$count){$this->assertSame('',$html);continue;}$dom=new DOMDocument();@$dom->loadHTML($html);$xp=new DOMXPath($dom);$this->assertSame($count,$xp->query('//*[@data-pg-photo]')->length);$this->assertStringContainsString('View all '.$count.' photos',$html);$this->assertStringContainsString('data-lightbox-ignore',$html);$this->assertStringContainsString('loading="eager"',$html);}
    }
    public function testSeoDescriptionSaveAndRestoreWorksForAllGalleryAdapters(): void {
        foreach(['destination','itinerary','tour','day_tour','experience','activity','blog','page','blog_landing'] as $type){$id=$type==='blog_landing'?1:101;$photo=page_gallery_rows($type,$id)[0];$field='page_gallery_'.$photo['id'].'_alt_text';$before=seo_present($type,$id);$this->assertArrayHasKey($field,$before['fields']);$after=seo_save($type,$id,$before['revision'],[$field=>'A revised factual photograph description'],null,1);$this->assertSame('A revised factual photograph description',page_gallery_rows($type,$id)[0]['alt_text']);seo_restore($type,$id,$after['revision'],(int)$after['revisions'][0]['id'],1);$this->assertSame($photo['alt_text'],page_gallery_rows($type,$id)[0]['alt_text']);}
    }
    public function testSeoRestoreRejectsChangedGalleryStructure(): void {$before=seo_present('blog',101);$photo=page_gallery_rows('blog',101)[0];$after=seo_save('blog',101,$before['revision'],['page_gallery_'.$photo['id'].'_alt_text'=>'A revised photograph description'],null,1);page_media_save($this->input('blog'));$current=seo_present('blog',101);$this->rejects(fn()=>seo_restore('blog',101,$current['revision'],(int)$after['revisions'][0]['id'],1),DomainException::class);}
    public function testPageLayoutDefaultsAndOverrides(): void {$this->assertSame('travel',page_layout(['slug'=>'private-driver-sri-lanka']));$this->assertSame('document',page_layout(['layout_style'=>'document']));$this->assertSame('travel',page_layout(['layout_style'=>'travel']));}
}
