<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/image_alt_context.php';

final class ImageAltContextTest extends TestCase
{
    private array $destinations=[];
    protected function setUp():void
    {
        db()->beginTransaction();$suffix=bin2hex(random_bytes(6));
        foreach(['Sigiriya','Kandy']as$index=>$name){execute_query('INSERT INTO destinations(name,slug,intro) VALUES(:name,:slug,:intro)',['name'=>$name,'slug'=>'alt-context-'.$suffix.'-'.$index,'intro'=>'PRIVATE COPY MUST NOT BE SENT']);$this->destinations[]=(int)db()->lastInsertId();}
    }
    protected function tearDown():void{if(db()->inTransaction())db()->rollBack();}
    public function testDestinationNamesComeFromSavedRecordsAndPromptUsesReferenceData():void
    {
        $snapshot=image_alt_context(['type'=>'destination','id'=>$this->destinations[0],'slot'=>'hero_image','destination'=>'Invented place']);
        $this->assertSame('Sri Lanka',$snapshot['references']['country']);$this->assertSame(['Sigiriya'],$snapshot['references']['destinations']);
        $payload=image_alt_payload('synthetic-jpeg',$snapshot['references']);$text=$payload['input'][0]['content'][0]['text'];
        $this->assertStringContainsString('Sigiriya',$text);$this->assertStringNotContainsString('Invented place',$text);$this->assertStringNotContainsString('PRIVATE COPY',$text);
        $this->assertStringContainsString('not a mandatory suffix',$payload['instructions']);$this->assertStringContainsString('untrusted descriptive DATA',$payload['instructions']);
        $this->assertSame('data:image/jpeg;base64,'.base64_encode('synthetic-jpeg'),$payload['input'][0]['content'][1]['image_url']);
    }
    public function testActivityEntryReferencesStayWithinTheirCategoryAndDetectEdits():void
    {
        if(!db_table_exists('activity_pages'))$this->markTestSkipped('Activities migration required.');
        $suffix=bin2hex(random_bytes(6));$pages=[];
        foreach(['Crafts','Other activity']as$index=>$title){execute_query("INSERT INTO activity_pages(name,slug,excerpt) VALUES(:name,:slug,'Overview')",['name'=>$title,'slug'=>'alt-activity-'.$suffix.'-'.$index]);$pages[]=(int)db()->lastInsertId();}
        execute_query("INSERT INTO activity_examples(activity_page_id,title,destinations_json) VALUES(:id,'Mask carving','[\"Ambalangoda\"]')",['id'=>$pages[0]]);$entry=(int)db()->lastInsertId();
        $input=['type'=>'activity','id'=>$pages[0],'slot'=>'activity_entry','example_id'=>$entry,'photo_id'=>0];
        $before=image_alt_context($input);$this->assertSame('Mask carving',$before['references']['entry']);$this->assertSame(['Ambalangoda'],$before['references']['destinations']);
        execute_query("UPDATE activity_examples SET destinations_json='[\"Kandy\"]' WHERE id=:id",['id'=>$entry]);$this->assertNotSame($before['revision'],image_alt_context($input)['revision']);
        $input['id']=$pages[1];$this->expectException(InvalidArgumentException::class);image_alt_context($input);
    }
    public function testLibraryAssetDestinationAndCountryOnlyFallbackAreAvailable():void
    {
        $this->assertSame([] ,image_alt_context([])['references']['destinations']);
        if(!db_column_exists('media_assets','destination_id'))$this->markTestSkipped('Destination library association unavailable.');
        execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,destination_id) VALUES(:path,'Reference fixture','image/jpeg',1,:destination)",['path'=>'context-'.bin2hex(random_bytes(6)).'.jpg','destination'=>$this->destinations[1]]);$asset=(int)db()->lastInsertId();
        $snapshot=image_alt_context(['type'=>'destination','id'=>$this->destinations[0],'slot'=>'cover_image'],$asset);
        $this->assertSame(['Sigiriya'],$snapshot['references']['destinations']);$this->assertSame('Kandy',$snapshot['references']['asset_destination']);
        $this->assertStringContainsString('If references conflict',image_alt_payload('jpeg',$snapshot['references'])['instructions']);
        $this->assertSame('Kandy',image_alt_context([],$asset)['references']['asset_destination']);
    }
    public function testTourDayHotelAndCoverageLocationsHaveTheirOwnContext():void
    {
        if(!db_column_exists('tours','program_json')||!db_column_exists('tours','coverage_json'))$this->markTestSkipped('Tour media schema required.');
        $program=['route'=>'Sigiriya to Kandy','days'=>[['title'=>'Explore the fortress','location'=>'Sigiriya','image_asset_id'=>0]],'hotels'=>[['name'=>'Example lodge','location'=>'Kandy','image_asset_id'=>0]],'sections'=>[]];
        $coverage=[['title'=>'Rock fortress visit','location'=>'Sigiriya','description'=>'A heritage visit','destination_id'=>$this->destinations[0],'image_asset_id'=>0]];
        execute_query("INSERT INTO tours(name,slug,intro,program_json,coverage_json) VALUES('Cultural journey',:slug,'Private copy',:program,:coverage)",['slug'=>'alt-tour-'.bin2hex(random_bytes(6)),'program'=>json_encode($program),'coverage'=>json_encode($coverage)]);$id=(int)db()->lastInsertId();
        $input=['type'=>'tour','id'=>$id,'child_id'=>1];
        $day=image_alt_context($input+['slot'=>'program_day'])['references'];$this->assertSame(['Sigiriya'],$day['locations']);
        $hotel=image_alt_context($input+['slot'=>'program_hotel'])['references'];$this->assertSame('Example lodge',$hotel['entry']);$this->assertSame(['Kandy'],$hotel['locations']);
        $card=image_alt_context($input+['slot'=>'coverage'])['references'];$this->assertSame(['Sigiriya'],$card['destinations']);
        $this->expectException(InvalidArgumentException::class);image_alt_context(['type'=>'tour','id'=>$id,'slot'=>'program_day','child_id'=>999]);
    }
    public function testItineraryAndDayTourLinkedDestinationsAreReadWithoutGuessing():void
    {
        foreach(['itinerary'=>['itineraries','itinerary_destination','itinerary_id'],'day_tour'=>['day_tours','day_tour_destination','day_tour_id']]as$type=>[$table,$pivot,$column]){
            if(!db_table_exists($pivot))continue;
            execute_query('INSERT INTO '.$table."(name,slug,intro) VALUES('Private journey',:slug,'Private copy')",['slug'=>'alt-'.$type.'-'.bin2hex(random_bytes(6))]);$id=(int)db()->lastInsertId();
            execute_query('INSERT INTO '.$pivot.'('.$column.',destination_id) VALUES(:id,:destination)',['id'=>$id,'destination'=>$this->destinations[1]]);
            $this->assertSame(['Kandy'],image_alt_context(['type'=>$type,'id'=>$id,'slot'=>'hero_image'])['references']['destinations']);
        }
        $this->assertSame('A title with tags',image_alt_context_text(" A <b>title</b>\nwith tags "));
    }
}
