<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$j = fn($a) => json_encode($a, JSON_UNESCAPED_UNICODE);

// region_slug mapping for destinations that lack it
$region_map = [
    'yala' => 'wildlife',
    'udawalawe' => 'wildlife',
    'ella' => 'hill',
    'colombo' => 'west',
    'sigiriya' => 'cultural',
    'mirissa' => 'south',
    'galle' => 'south',
    'sri-pada-adams-peak' => 'hill',
    'hiriketiya' => 'south',
    'kandy' => 'hill',
    'nuwara-eliya' => 'hill',
    'trincomalee' => 'east',
    'anuradhapura' => 'cultural',
    'polonnaruwa' => 'cultural',
    'arugam-bay' => 'east',
    'bentota' => 'south',
    'hikkaduwa' => 'south',
    'jaffna' => 'north',
    'negombo' => 'west',
    'dambulla' => 'cultural',
    'horton-plains' => 'hill',
    'knuckles-mountain-range' => 'hill',
    'unawatuna' => 'south',
    'weligama' => 'south',
    'tangalle' => 'south',
    'adams-peak' => 'hill',
    'kurunegala' => 'cultural',
    'mannar' => 'north',
    'passikudah' => 'east',
    'batticaloa' => 'east',
    'matara' => 'south',
    'ratnapura' => 'hill',
    'ampara' => 'east',
    'sinharaja' => 'wildlife',
    'haputale' => 'hill',
    'wilpattu' => 'wildlife',
    'kalpitiya' => 'south',
    'gal-oya' => 'east',
    'ritigala' => 'cultural',
    'ussangoda' => 'south',
    'yapahuwa' => 'cultural',
    'minneriya' => 'wildlife',
    'delft-island' => 'north',
    'bundala' => 'wildlife',
    'meemure' => 'hill',
];

// best_time_json for all 45 destinations (only 4 have it)
$best_time_map = [
    'yala' => $j(['January', 'February', 'March', 'April', 'May', 'June', 'July']),
    'udawalawe' => $j(['January', 'February', 'March', 'July', 'August', 'December']),
    'ella' => $j(['January', 'February', 'March', 'April', 'July', 'August']),
    'colombo' => $j(['January', 'February', 'March', 'July', 'August', 'December']),
    'sigiriya' => null, // already has it
    'mirissa' => $j(['December', 'January', 'February', 'March']),
    'galle' => null, // already has it
    'sri-pada-adams-peak' => null, // already has it
    'hiriketiya' => null, // already has it
    'kandy' => $j(['July', 'August', 'December', 'January']),
    'nuwara-eliya' => $j(['January', 'February', 'March', 'April']),
    'trincomalee' => $j(['May', 'June', 'July', 'August', 'September']),
    'anuradhapura' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
    'polonnaruwa' => $j(['January', 'February', 'March', 'April', 'December']),
    'arugam-bay' => $j(['June', 'July', 'August', 'September']),
    'bentota' => $j(['December', 'January', 'February', 'March']),
    'hikkaduwa' => $j(['December', 'January', 'February', 'March']),
    'jaffna' => $j(['March', 'April', 'July', 'August', 'September']),
    'negombo' => $j(['December', 'January', 'February', 'March']),
    'dambulla' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
    'horton-plains' => $j(['January', 'February', 'March', 'April']),
    'knuckles-mountain-range' => $j(['January', 'February', 'March', 'April']),
    'unawatuna' => $j(['December', 'January', 'February', 'March']),
    'weligama' => $j(['December', 'January', 'February', 'March']),
    'tangalle' => $j(['December', 'January', 'February', 'March']),
    'adams-peak' => $j(['January', 'February', 'March', 'April']),
    'kurunegala' => $j(['January', 'February', 'March', 'December']),
    'mannar' => $j(['March', 'April', 'July', 'August', 'September']),
    'passikudah' => $j(['June', 'July', 'August', 'September']),
    'batticaloa' => $j(['May', 'June', 'July', 'August']),
    'matara' => $j(['January', 'February', 'March', 'December']),
    'ratnapura' => $j(['January', 'February', 'March', 'August']),
    'ampara' => $j(['June', 'July', 'August', 'September']),
    'sinharaja' => $j(['January', 'February', 'March', 'August']),
    'haputale' => $j(['January', 'February', 'March', 'April']),
    'wilpattu' => $j(['May', 'June', 'July', 'August', 'September']),
    'kalpitiya' => $j(['June', 'July', 'August', 'January', 'February']),
    'gal-oya' => $j(['March', 'April', 'May', 'June', 'July']),
    'ritigala' => $j(['February', 'March', 'April', 'August', 'September']),
    'ussangoda' => $j(['January', 'February', 'March']),
    'yapahuwa' => $j(['January', 'February', 'March', 'April']),
    'minneriya' => $j(['July', 'August', 'September', 'October']),
    'delft-island' => $j(['January', 'February', 'March']),
    'bundala' => $j(['December', 'January', 'February', 'March']),
    'meemure' => $j(['January', 'February', 'March', 'April']),
];

$updated_region = 0;
$updated_best_time = 0;
$errors = 0;

foreach ($region_map as $slug => $region) {
    try {
        execute_query("UPDATE destinations SET region_slug = :region WHERE slug = :slug AND (region_slug IS NULL OR region_slug = '')", [
            'region' => $region,
            'slug' => $slug,
        ]);
        if (fetch_one("SELECT id FROM destinations WHERE slug = :slug AND region_slug = :region", ['slug' => $slug, 'region' => $region])) {
            echo "REG {$slug} -> {$region}\n";
            ++$updated_region;
        }
    } catch (\Throwable $e) {
        echo "FAIL {$slug} region: {$e->getMessage()}\n";
        ++$errors;
    }
}

foreach ($best_time_map as $slug => $bt) {
    if ($bt === null) continue; // skip those already populated
    try {
        execute_query("UPDATE destinations SET best_time_json = :bt WHERE slug = :slug", [
            'bt' => $bt,
            'slug' => $slug,
        ]);
        echo "BEST {$slug}\n";
        ++$updated_best_time;
    } catch (\Throwable $e) {
        echo "FAIL {$slug} best_time: {$e->getMessage()}\n";
        ++$errors;
    }
}

echo "\nDone. Region slugs set: {$updated_region}, Best time set: {$updated_best_time}, Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
