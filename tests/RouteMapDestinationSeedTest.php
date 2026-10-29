<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__.'/../includes/route_map_destination_seed.php';

final class RouteMapDestinationSeedTest extends TestCase
{
    private function catalog(): array { return route_map_seed_catalog(__DIR__.'/../assets/maps/destination-locations.json'); }
    private function destination(int $id, string $name, string $slug): array { return compact('id','name','slug'); }

    public function testAllSourcedPlacesAreUsableAndUnmatchedPlacesAreReported(): void
    {
        $catalog = $this->catalog(); $destinations=[];
        foreach ($catalog['data']['places'] as $i=>$place) { $destinations[]=$this->destination($i+1,$place['names'][0],$place['slug']); }
        $destinations[]=$this->destination(901,'Ussangoda','ussangoda');
        $destinations[]=$this->destination(902,'Minneriya / Kaudulla','minneriya-kaudulla');
        $plan=route_map_seed_plan($destinations,[],$catalog);
        $this->assertCount(41,$plan['create']); $this->assertCount(2,$plan['skipped']);
        foreach($plan['create'] as $entry) { $this->assertLessThanOrEqual(500,mb_strlen($entry['coordinate_source'])); $this->assertMatchesRegularExpression('/\A\d+\.\d{6}\z/',$entry['latitude']); }
    }

    public function testNameAliasesAreExactAndConflictingSlugsAreRejected(): void
    {
        $plan=route_map_seed_plan([$this->destination(1,"Adam’s Peak",'adams-peak'),$this->destination(2,'Kandy','colombo'),$this->destination(3,'Near Colombo','near-colombo')],[],$this->catalog());
        $this->assertSame([1],array_column($plan['create'],'destination_id')); $this->assertCount(2,$plan['skipped']);
    }

    public function testExistingVerifiedAndInactiveLinkedLocationsRemainUntouched(): void
    {
        $rows=[['id'=>81,'name'=>'Owner custom name','destination_id'=>1,'is_verified'=>0,'is_active'=>0,'revision'=>9]];
        $plan=route_map_seed_plan([$this->destination(1,'Colombo','colombo')],$rows,$this->catalog());
        $this->assertSame([],$plan['create']); $this->assertSame(81,$plan['preserve'][0]['location_id']);
        $this->assertSame(9,$rows[0]['revision']);
    }

    public function testUnlinkedAndDuplicatePlaceNamesRequireManualReview(): void
    {
        $destinations=[$this->destination(1,'Colombo','colombo'),$this->destination(2,'Negombo','negombo'),$this->destination(3,'Negombo','negombo-copy')];
        $plan=route_map_seed_plan($destinations,[['id'=>81,'name'=>'COLOMBO','destination_id'=>null]],$this->catalog());
        $this->assertSame([],$plan['create']); $this->assertCount(3,$plan['skipped']);
    }

    public function testPlanChangesWhenExistingLocationOrDestinationChanges(): void
    {
        $destinations=[$this->destination(1,'Colombo','colombo')]; $catalog=$this->catalog();
        $a=route_map_seed_plan($destinations,[],$catalog);
        $this->assertSame($a,route_map_seed_plan($destinations,[],$catalog));
        $destinations[0]['name']='Renamed place';
        $this->assertNotSame($a['plan_sha256'],route_map_seed_plan($destinations,[],$catalog)['plan_sha256']);
        $this->assertNotSame($a['plan_sha256'],route_map_seed_plan([$this->destination(1,'Colombo','colombo')],[['id'=>99,'name'=>'New custom place','destination_id'=>null]],$catalog)['plan_sha256']);
    }

    public function testCatalogRejectsInvalidCoordinatesAndAmbiguousAliases(): void
    {
        $catalog=$this->catalog()['data']; $file=tempnam(sys_get_temp_dir(),'ict-map-catalog-');
        try {
            $catalog['places'][0]['latitude']=0;file_put_contents($file,json_encode($catalog,JSON_THROW_ON_ERROR));
            try { route_map_seed_catalog($file);$this->fail('Invalid coordinates accepted.'); } catch(RuntimeException $error) { $this->assertStringContainsString('Invalid',$error->getMessage()); }
            $catalog=$this->catalog()['data'];$catalog['places'][1]['names'][]=$catalog['places'][0]['names'][0];file_put_contents($file,json_encode($catalog,JSON_THROW_ON_ERROR));
            $this->expectException(RuntimeException::class); route_map_seed_catalog($file);
        } finally { unlink($file); }
    }
}
