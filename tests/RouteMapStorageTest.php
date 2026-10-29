<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/route_map_store.php';

final class RouteMapStorageTest extends TestCase
{
    private array $locations;
    protected function setUp(): void
    {
        if (!preg_match('/\Aict_route_maps_20261025_[a-f0-9]{6}\z/', DB_NAME)) { $this->markTestSkipped('Run storage tests only against the disposable Route Map rehearsal schema.'); }
        $this->assertTrue(route_map_available());
        db()->beginTransaction();
        $this->locations = route_map_locations();
    }
    protected function tearDown(): void { if (db()->inTransaction()) { db()->rollBack(); } }
    private function input(string $type): array
    {
        $state = route_map_state($type, route_map_record($type, 901));
        $ids = array_keys($this->locations);
        $days = [0=>[$ids[0]]];
        foreach ($state['schedule']['days'] as $day) { $days[$day['day']] = [$ids[$day['day'] % count($ids)]]; }
        return ['revision'=>$state['revision'],'source_revision'=>$state['schedule']['revision'],'library_revision'=>route_map_library_revision($state['locations']),'status'=>'ready','map_days'=>$days];
    }

    public function testAllThreeCollectionsSaveIndependentlyWithoutTouchingPackageFields(): void
    {
        $before = [];
        foreach (array_keys(route_map_types()) as $type) { $before[$type] = route_map_record($type,901); }
        foreach (array_keys(route_map_types()) as $type) {
            route_map_save($type,901,$this->input($type));
            $state = route_map_state($type, route_map_record($type,901));
            $this->assertTrue($state['ready']);
            $this->assertSame($before[$type],route_map_record($type,901));
        }
        $this->assertSame(3,(int) fetch_one('SELECT COUNT(*) AS n FROM package_route_maps')['n']);
    }

    public function testConcurrentMapSaveRejectsAStaleRevisionWithoutOverwriting(): void
    {
        $input = $this->input('itinerary'); route_map_save('itinerary',901,$input);
        $saved = fetch_one('SELECT * FROM package_route_maps WHERE package_type=\'itinerary\' AND package_id=901');
        try { route_map_save('itinerary',901,$input); $this->fail('Stale map accepted'); } catch (DomainException) { $this->addToAssertionCount(1); }
        $this->assertSame($saved, fetch_one('SELECT * FROM package_route_maps WHERE package_type=\'itinerary\' AND package_id=901'));
    }

    public function testProgrammeChangeRequiresExplicitMapReviewAndRetainsStoredRoute(): void
    {
        route_map_save('itinerary',901,$this->input('itinerary'));
        $before = fetch_one('SELECT route_json FROM package_route_maps WHERE package_type=\'itinerary\' AND package_id=901');
        execute_query("UPDATE itinerary_days SET title='Changed plan' WHERE id=901");
        $state = route_map_state('itinerary',route_map_record('itinerary',901));
        $this->assertTrue($state['review']); $this->assertFalse($state['ready']);
        $this->assertSame($before,fetch_one('SELECT route_json FROM package_route_maps WHERE package_type=\'itinerary\' AND package_id=901'));
        $input = $this->input('itinerary');
        try { route_map_save('itinerary',901,$input); $this->fail('Unreviewed route accepted'); } catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        route_map_save('itinerary',901,$input+['confirm_review'=>'1']);
        $this->assertTrue(route_map_state('itinerary',route_map_record('itinerary',901))['ready']);
    }

    public function testReferencedCoordinateChangeInvalidatesPublicRouteAndStaleEditor(): void
    {
        route_map_save('tour',901,$this->input('tour'));
        $old = $this->input('tour'); $first = reset($this->locations);
        $values = array_map(static fn($v)=>(string) $v,$first); $values['latitude']='6.935490';
        route_map_location_save((int) $first['id'],$values);
        $state = route_map_state('tour',route_map_record('tour',901));
        $this->assertTrue($state['review']); $this->assertFalse($state['ready']);
        $this->expectException(DomainException::class); route_map_save('tour',901,$old);
    }

    public function testRouteWordingRequiresReviewButPhotographChangesDoNot(): void
    {
        route_map_save('tour',901,$this->input('tour')+['confirm_review'=>'1']);
        $record=route_map_record('tour',901);
        $program=json_decode($record['program_json'],true);
        $program['days'][0]['image_asset_id']=123;
        execute_query('UPDATE tours SET program_json=:json WHERE id=901',['json'=>json_encode($program)]);
        $this->assertTrue(route_map_state('tour',['id'=>901])['ready']);
        $program['route']='A changed route plan';
        execute_query('UPDATE tours SET program_json=:json WHERE id=901',['json'=>json_encode($program)]);
        $this->assertTrue(route_map_state('tour',['id'=>901])['review']);
        $this->assertFalse(route_map_state('tour',['id'=>901])['ready']);
    }

    public function testLocationCreatesLinksAndRejectsStaleOrDuplicateDestinationWrites(): void
    {
        $input = ['name'=>'Linked Sigiriya','latitude'=>'7.949460','longitude'=>'80.750370','coordinate_source'=>'Synthetic verified point','destination_id'=>'901','revision'=>'missing','is_verified'=>'1','is_active'=>'1'];
        $id = route_map_location_save(0,$input);
        $this->assertSame(901,(int) route_map_locations()[$id]['destination_id']);
        try { route_map_location_save(0,$input); $this->fail('Duplicate destination accepted'); } catch (DomainException) { $this->addToAssertionCount(1); }
        $this->expectException(DomainException::class); route_map_location_save($id,$input);
    }

    public function testDesignSaveChangesOnlyItsOwnSettingAndRejectsStaleWrites(): void
    {
        $before = fetch_all('SELECT * FROM site_settings WHERE setting_key<>:key ORDER BY setting_key',['key'=>ROUTE_MAP_DESIGN_KEY]);
        $design = route_map_design_defaults()+['revision'=>route_map_design_state()['revision']];
        route_map_design_save($design);
        $this->assertTrue(route_map_design_state()['saved']);
        $this->assertSame($before,fetch_all('SELECT * FROM site_settings WHERE setting_key<>:key ORDER BY setting_key',['key'=>ROUTE_MAP_DESIGN_KEY]));
        $this->expectException(DomainException::class); route_map_design_save($design);
    }

    public function testInvalidRouteSaveIsAtomicAndPreviewReadDoesNotWrite(): void
    {
        $before = fetch_all('SELECT * FROM package_route_maps ORDER BY id');
        $input = $this->input('day_tour'); $input['map_days'][1] = [99999999];
        try { route_map_save('day_tour',901,$input); $this->fail('Unknown location accepted'); } catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        route_map_state('day_tour',route_map_record('day_tour',901));
        route_map_design_state();
        $this->assertSame($before,fetch_all('SELECT * FROM package_route_maps ORDER BY id'));
    }
}
