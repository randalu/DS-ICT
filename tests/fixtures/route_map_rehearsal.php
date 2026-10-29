<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = dirname(__DIR__, 2);
$out = $root . '/output/route-map-20261025';
ini_set('session.save_path', $root . '/output/session');
require_once $root . '/includes/route_map_store.php';
if (is_file($out . '/fixture.json')) { throw new RuntimeException('Fixture already exists; reuse it rather than creating another schema.'); }
$name = 'ict_route_maps_20261025_' . bin2hex(random_bytes(3));
$normal = DB_NAME;
$protected = [];
foreach (['tours','itineraries','itinerary_days','day_tours','destinations','site_settings'] as $table) {
    $protected[$table] = route_map_hash(db()->query('SELECT * FROM `' . $table . '` ORDER BY 1')->fetchAll());
}
$normalMapTables = db()->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('map_locations','package_route_maps') ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
db()->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach ($tables as $table) {
    if (!preg_match('/\A[a-zA-Z0-9_]+\z/', $table)) { throw new RuntimeException('Unexpected table name.'); }
    db()->exec('CREATE TABLE `' . $name . '`.`' . $table . '` LIKE `' . $normal . '`.`' . $table . '`');
}
db()->exec('USE `' . $name . '`');
db()->exec(file_get_contents($root . '/database/migrations/2026-10-24-tour-program.sql'));
for ($i = 0; $i < 2; $i++) { db()->exec(file_get_contents($root . '/database/migrations/2026-10-25-route-maps.sql')); }
function route_map_fixture_insert(string $table, array $values): void
{
    $columns = db()->query('SHOW COLUMNS FROM `' . $table . '`')->fetchAll(PDO::FETCH_COLUMN);
    $values = array_intersect_key($values, array_flip($columns));
    $keys = array_keys($values);
    execute_query('INSERT INTO `' . $table . '` (`' . implode('`,`', $keys) . '`) VALUES (:' . implode(',:', $keys) . ')', $values);
}
route_map_fixture_insert('admins',['id'=>901,'name'=>'Synthetic map reviewer','email'=>'map@example.test','username'=>'map-review','password_hash'=>'!synthetic-no-login','is_active'=>1]);
route_map_fixture_insert('itinerary_categories',['id'=>901,'name'=>'Synthetic route category','slug'=>'map-category','is_published'=>1]);
foreach ([901=>['Sigiriya',1],902=>['Colombo',0]] as $id=>[$title,$published]) {
    route_map_fixture_insert('destinations',['id'=>$id,'name'=>$title,'slug'=>'map-destination-'.$id,'is_published'=>$published]);
}
$photo = 'assets/images/home-hero/home-5.jpg';
$common = ['id'=>901,'name'=>'Synthetic map journey','slug'=>'map-itinerary','days'=>3,'category_id'=>901,'intro'=>'A synthetic private journey through culture and hill country for map validation.','excerpt'=>'Synthetic content only.','hero_image'=>$photo,'price_from'=>1250,'price_currency'=>'USD','is_published'=>1];
route_map_fixture_insert('itineraries',$common);
route_map_fixture_insert('itinerary_days',['id'=>901,'itinerary_id'=>901,'day_number'=>1,'day_end'=>1,'title'=>'Cultural region','summary'=>'Synthetic day one.','overnight_location'=>'Sigiriya']);
route_map_fixture_insert('itinerary_days',['id'=>902,'itinerary_id'=>901,'day_number'=>2,'day_end'=>3,'title'=>'Hill country','summary'=>'Synthetic grouped days.','overnight_location'=>'Kandy']);
$program = ['route'=>'Negombo → Sigiriya → Kandy','days'=>[['day_start'=>1,'day_end'=>1,'title'=>'Cultural region','location'=>'Sigiriya'],['day_start'=>2,'day_end'=>3,'title'=>'Hill country','location'=>'Kandy']],'hotels'=>[],'sections'=>[]];
route_map_fixture_insert('tours',array_replace($common,['name'=>'Synthetic map tour','slug'=>'map-tour','program_json'=>json_encode($program)]));
route_map_fixture_insert('day_tours',array_replace($common,['name'=>'Synthetic map day tour','slug'=>'map-day-tour','itinerary'=>'Colombo to Galle and return','duration_hours'=>8,'meeting_point'=>'Colombo']));
foreach (['privacy_service_ga4_enabled','privacy_service_gtm_enabled','privacy_service_trustpilot_enabled','privacy_service_tripadvisor_enabled','privacy_service_gtranslate_enabled','privacy_service_tawk_enabled'] as $key) {
    route_map_fixture_insert('site_settings',['setting_key'=>$key,'setting_value'=>'0']);
}
$suggestions = json_decode(file_get_contents($root . '/assets/maps/locations.json'),true)['places'];
$locationIds = [];
foreach ($suggestions as $place) {
    $locationIds[$place['name']] = route_map_location_save(0,$place + ['coordinate_source'=>$place['source'],'revision'=>'missing','is_verified'=>'1','is_active'=>'1']);
}
$afterProtected = [];
foreach (array_keys($protected) as $table) { $afterProtected[$table] = route_map_hash(db()->query('SELECT * FROM `' . $normal . '`.`' . $table . '` ORDER BY 1')->fetchAll()); }
if ($protected !== $afterProtected) { throw new RuntimeException('Normal data changed unexpectedly.'); }
file_put_contents($out . '/fixture.json',json_encode(['database'=>$name,'normal_database'=>$normal,'normal_hashes'=>$protected,'normal_map_tables'=>$normalMapTables,'locations'=>$locationIds,'itinerary'=>901,'tour'=>901,'day_tour'=>901],JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo "Disposable map schema created. Migration applied twice; normal package/settings hashes preserved.\n";
