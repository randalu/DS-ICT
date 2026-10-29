<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/route_map_store.php';
require_once __DIR__ . '/../includes/route_map_view.php';

final class RouteMapTest extends TestCase
{
    private function places(): array
    {
        return [1 => ['id'=>1,'name'=>'Colombo','latitude'=>6.93548,'longitude'=>79.84868,'is_verified'=>1,'is_active'=>1,'revision'=>1],
            2 => ['id'=>2,'name'=>'Sigiriya','latitude'=>7.94946,'longitude'=>80.75037,'is_verified'=>1,'is_active'=>1,'revision'=>1]];
    }

    public function testGroupedDaysExpandWithoutChangingTheWrittenProgramme(): void
    {
        $rows = [['start'=>1,'end'=>1,'title'=>'Arrival'],['start'=>2,'end'=>4,'title'=>'Cultural region']];
        $days = route_map_expand_days($rows, 5);
        $this->assertSame([1,2,3,4,5], array_column($days, 'day'));
        $this->assertSame('Cultural region', $days[2]['title']);
        $this->assertFalse($days[4]['written']);
        $this->assertCount(2, $rows);
    }

    public function testOverlappingSourceDaysAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        route_map_expand_days([['start'=>1,'end'=>3],['start'=>3,'end'=>4]]);
    }

    public function testDayZeroMultipleStopsRepeatedStaysAndOrderAreRetained(): void
    {
        $schedule = ['days'=>route_map_expand_days([['start'=>1,'end'=>2]])];
        $days = route_map_normalize_days([2=>[2,1],0=>[1],1=>[1,2,2]], $schedule, $this->places(), true);
        $this->assertSame([0,1,2], array_column($days, 'day'));
        $this->assertSame([1,2,2], $days[1]['stops']);
        $stops = route_map_stops($days, $this->places());
        $this->assertSame([1,1,2,2,2,1], array_column($stops, 'id'));
        $svg = route_map_svg($stops);
        $this->assertSame(2, substr_count($svg, 'class="route-map-line"'));
        $this->assertStringContainsString('route-map-return', $svg);
        $this->assertSame(2, substr_count($svg, 'class="route-map-marker'));
    }

    public function testDraftCanRetainAnUnmappedDayButReadyRequiresEveryWrittenDay(): void
    {
        $schedule = ['days'=>route_map_expand_days([['start'=>1,'end'=>1]], 2)];
        $this->assertSame([], route_map_normalize_days([1=>[1],2=>['']], $schedule, $this->places(), false)[1]['stops']);
        $this->expectException(InvalidArgumentException::class);
        route_map_normalize_days([1=>[1],2=>[2]], $schedule, $this->places(), true);
    }

    public function testForgedDaysReferencesTypesAndIncompleteDocumentsAreRejected(): void
    {
        $schedule = ['days'=>[['day'=>1,'written'=>true]]];
        foreach ([[1=>[999]],[1=>[true]],[1=>[1],2=>[2]],[],[1=>array_fill(0,9,1)],[1=>['01']],[1=>[[]]]] as $input) {
            try { route_map_normalize_days($input, $schedule, $this->places(), false); $this->fail('Invalid map accepted'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function testUnverifiedAndInactiveLocationsCannotBeDisplayed(): void
    {
        $places = $this->places(); $places[1]['is_verified'] = 0;
        $this->assertCount(1, route_map_normalize_days([1=>[1]], ['days'=>[['day'=>1,'written'=>true]]], $places, false));
        $this->expectException(InvalidArgumentException::class);
        route_map_normalize_days([1=>[1]], ['days'=>[['day'=>1,'written'=>true]]], $places, true);
    }

    public function testProjectionMatchesTheStoredD3Template(): void
    {
        [$x,$y] = route_map_project(7.2083,79.8358);
        $this->assertEqualsWithDelta(154.47132067486746,$x,0.000001);
        $this->assertEqualsWithDelta(471.4320448171063,$y,0.000001);
        $this->assertSame('Public domain', route_map_geometry()['license']);
    }

    public function testNearbyStopsShareAMarkerWhileKeepingEveryStopInTheDayList(): void
    {
        $places = $this->places();
        $places[3] = ['id'=>3,'name'=>'Dambulla','latitude'=>7.86,'longitude'=>80.651667,'is_verified'=>1,'is_active'=>1,'revision'=>1];
        $html = route_map_card(['ready'=>true,'document'=>['days'=>[['day'=>1,'stops'=>[3,2]]]],'locations'=>$places],route_map_design_defaults());
        $this->assertSame(1,substr_count($html,'class="route-map-marker'));
        $this->assertStringContainsString('>1+</text>',$html);
        $this->assertStringContainsString('1. Dambulla → 2. Sigiriya',$html);
    }

    public function testDefaultOverviewNeverInventsPackageStops(): void
    {
        $html = route_map_card(['ready'=>false,'document'=>['days'=>[['day'=>1,'stops'=>[1,2]]]],'locations'=>$this->places()], route_map_design_defaults());
        $this->assertStringContainsString('Sri Lanka overview', $html);
        $this->assertStringNotContainsString('route-map-marker', $html);
        $this->assertStringNotContainsString('route-map-line', $html);
        $this->assertStringContainsString('srilankaprivatetravels.com', $html);
    }

    public function testLabelsAndDesignTextAreEscapedAndFullNamesRemainAccessible(): void
    {
        $places = $this->places(); $places[1]['name'] = '<script>alert("x")</script> & a long destination label';
        $html = route_map_card(['ready'=>true,'document'=>['days'=>[['day'=>1,'stops'=>[1,2]]]],'locations'=>$places], route_map_design_defaults());
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('View stops by day', $html);
    }

    public function testLocationValidationCoversCoordinatesReferencesAndPlainText(): void
    {
        $valid = ['name'=>'Airport','latitude'=>'7.180756','longitude'=>'79.884117','coordinate_source'=>'GeoNames airport reference point','destination_id'=>'','is_verified'=>'1','is_active'=>'1'];
        $this->assertNull(route_map_location_normalize($valid)['destination_id']);
        foreach ([['latitude'=>'NaN'],['longitude'=>'0'],['name'=>'<b>Bad</b>'],['is_verified'=>'yes'],['destination_id'=>'0'],['coordinate_source'=>'']] as $bad) {
            try { route_map_location_normalize(array_replace($valid,$bad)); $this->fail('Invalid place accepted'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function testDesignRequiresReadableColoursAndBoundedPlainText(): void
    {
        $this->assertSame(route_map_design_defaults(), route_map_design_normalize(route_map_design_defaults()));
        foreach (['#ffffff','url(bad)','#abc'] as $color) {
            try { route_map_design_normalize(array_replace(route_map_design_defaults(),['route_color'=>$color])); $this->fail('Unreadable design accepted'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
    }

    public function testCorruptDocumentsAndChangedLocationVersionsAreDetected(): void
    {
        $days = [['day'=>0,'stops'=>[1]],['day'=>1,'stops'=>[2]]];
        $doc = ['version'=>1,'status'=>'ready','source_revision'=>str_repeat('a',64),'locations_revision'=>route_map_locations_revision($days,$this->places()),'days'=>$days];
        $this->assertSame($days, route_map_decode(json_encode($doc))['days']);
        $places = $this->places(); $places[1]['revision'] = 2;
        $this->assertNotSame($doc['locations_revision'],route_map_locations_revision($days,$places));
        $doc['days'][1]['day'] = 0;
        $this->expectException(InvalidArgumentException::class);
        route_map_decode(json_encode($doc));
    }
}
