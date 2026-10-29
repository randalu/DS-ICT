<?php
declare(strict_types=1);

namespace NewSite;

// Entirely synthetic editorial/design data. No CMS, pricing, reviews or legal claims.
$home = require __DIR__ . '/catalog.php';
$records = [];
$label = 'Design sample - not a published offer';
$make = static function (string $family, string $slug, string $title, string $summary, string $image, string $alt, string $path, array $extra = []) use (&$records, $label): array {
    $record = ['id' => $extra['id'] ?? ('demo-' . $family . '-' . $slug), 'slug' => $slug, 'title' => $title,
        'summary' => $summary, 'image' => $image, 'image_alt' => $alt, 'url' => $path,
        'label' => $label, 'demo' => true, 'family' => $family] + $extra;
    $records[$family][$slug] = $record;
    return $record;
};
$copy = static function (string $family, array $card, array $extra = []) use ($make): array {
    $slug = basename($card['url']);
    return $make($family, $slug, $card['title'], $card['summary'], $card['image'], $card['image_alt'], $card['url'],
        ['id' => $card['id'] ?? ('demo-' . $family . '-' . $slug)] + $extra);
};
$galle = $home['destinations'][0];
$ella = $home['destinations'][1];
$kandy = $home['destinations'][2];
$mirissa = $home['destinations'][3];
$yala = ['title' => 'Yala', 'summary' => 'A wildlife landscape shown as a destination design sample. No sightings, park access or availability are promised.',
    'image' => 'assets/images/yala-national-park-leopard-safari-large.w800.webp', 'image_alt' => 'Wildlife photograph from Yala National Park, Sri Lanka', 'url' => '/destinations/yala'];
foreach ([$galle, $ella, $kandy, $mirissa, $yala] as $index => $card) {
    $copy('destination', $card, [
        'region' => ['south', 'hills', 'central', 'south', 'south'][$index],
        'body' => [
            ['heading' => 'A place to pause', 'text' => 'Sample educational layout content: use this space to describe the character of ' . $card['title'] . ' with checked editorial sources. The photograph provides visual context, not a promise of a particular experience.'],
            ['heading' => 'Make space in the plan', 'text' => 'Before adding a stop, discuss interests, mobility needs and the time available with a travel planner. Travel times, access arrangements and current conditions must be verified separately. This preview does not provide an operating programme.'],
        ],
        'facts' => [['label' => 'Content status', 'value' => 'Illustrative destination profile'], ['label' => 'Availability', 'value' => 'Not checked in preview']],
        'gallery' => [['image' => $card['image'], 'image_alt' => $card['image_alt']]],
    ]);
}
foreach ($home['journeys'] as $index => $card) {
    $duration = [7, 10, 14][$index];
    $days = [];
    for ($day = 1; $day <= $duration; $day++) {
        $stop = $card['route'][min(count($card['route']) - 1, (int) floor(($day - 1) * count($card['route']) / $duration))];
        $days[] = ['title' => 'Illustrative day ' . $day, 'summary' => 'Sample layout slot only. Activities, accommodation, driving time and availability have not been arranged or verified.', 'location' => $stop['title']];
    }
    $copy('itinerary', $card, [
        'duration' => $duration, 'duration_days' => $duration, 'duration_label' => $duration . ' days - illustrative sample only',
        'theme' => ['adventure', 'wildlife', 'family'][$index], 'category' => ['cultural', 'wildlife', 'cultural'][$index],
        'traveller' => ['couples', 'friends', 'family'][$index], 'start' => ['kandy', 'galle', 'kandy'][$index],
        'route' => $card['route'], 'route_label' => $card['route_label'], 'days' => $days,
        'body' => [['heading' => 'A starting point, not a package', 'text' => 'This is an illustrative composition for testing the journey page. The sample duration is a design device, not a recommended timetable or an actual product. A real proposal would need confirmed logistics, inclusions, exclusions and written terms.']],
        'facts' => [['label' => 'Duration', 'value' => $duration . ' days - illustrative sample only'], ['label' => 'Booking status', 'value' => 'Not bookable in preview']],
        'inclusions' => ['No services are included; this is a design sample.'],
        'exclusions' => ['No accommodation, transport, entry tickets or activity reservations are supplied.'],
        'faq' => [['question' => 'Can I book this sample?', 'answer' => 'No. It is synthetic layout content, not a published travel offer.']],
        'gallery' => [['image' => $card['image'], 'image_alt' => $card['image_alt']]],
    ]);
}
foreach ([['demo-cultural', 'Cultural perspectives', $kandy, 'cultural'], ['demo-hills-coast', 'Hills and coast private tour', $ella, 'adventure']] as [$slug, $title, $card, $theme]) {
    $make('tour', $slug, $title, 'A synthetic private-tour layout. This is not an available tour or a quotation.', $card['image'], $card['image_alt'], '/tours/' . $slug, [
        'theme' => $theme, 'category' => $theme,
        'body' => [['heading' => 'A private-tour design sample', 'text' => 'This sample separates the tour page from the itinerary collection. A genuine tour page would explain the operating programme and verified inclusions. No actual services, duration or price are offered here.']],
        'facts' => [['label' => 'Offer status', 'value' => 'Synthetic and not bookable']],
        'inclusions' => ['No services included in this preview.'], 'exclusions' => ['All travel arrangements require a separate verified proposal.'],
    ]);
}
foreach ([['galle', $galle, 'south', 'easy'], ['kandy', $kandy, 'central', 'moderate'], ['ella', $ella, 'hills', 'moderate']] as [$slug, $card, $region, $difficulty]) {
    $make('day-tour', 'demo-' . $slug, $card['title'] . ' day sample', 'An illustrative day-tour layout, not an operating excursion. Difficulty is a sample filter tag only.', $card['image'], $card['image_alt'], '/day-tours/demo-' . $slug, [
        'region' => $region, 'difficulty' => $difficulty, 'price_band' => 'unquoted',
        'body' => [['heading' => 'Explore the day-tour layout', 'text' => 'A published excursion would need checked pickup arrangements, accessibility details, operating hours and written inclusions. These sample tags exist only to demonstrate filtering; they are not an assessment of an actual activity.']],
        'facts' => [['label' => 'Difficulty tag', 'value' => ucfirst($difficulty) . ' - illustrative only'], ['label' => 'Price', 'value' => 'Not quoted']],
        'route' => [['title' => $card['title'], 'url' => $card['url']]],
        'inclusions' => ['No excursion or transport is provided.'], 'exclusions' => ['No tickets or reservations are included.'],
    ]);
}
foreach ($home['experiences'] as $index => $card) {
    $copy('experience', $card, ['mood' => ['slow', 'active', 'nature'][$index],
        'body' => [['heading' => 'An idea to discuss', 'text' => 'Sample educational layout content: an experience page can help a visitor explain what interests them. It should distinguish inspiration from an actual activity with verified access, safety information and availability. This page offers no activity.']],
        'facts' => [['label' => 'Experience status', 'value' => 'Illustrative only']],
        'gallery' => [['image' => $card['image'], 'image_alt' => $card['image_alt']]],
    ]);
}
$articles = $home['articles'];
$articles[] = ['title' => 'Leaving room in a journey', 'summary' => 'A sample educational article about questions to ask while planning, not an operational travel guide.',
    'image' => $kandy['image'], 'image_alt' => $kandy['image_alt'], 'url' => '/blog/demo-planning-space'];
foreach ($articles as $index => $card) {
    $copy('blog', $card, ['category' => ['coast', 'hills', 'planning'][$index],
        'sections' => [
            ['id' => 'sample-editorial', 'heading' => 'A sample editorial canvas', 'text' => 'This is sample educational layout content, not a published CMS guide. The page demonstrates a readable article, contents links and related journeys without inventing a named author, publication date or expert endorsement.'],
            ['id' => 'planning-questions', 'heading' => ['Leave room beside the coast', 'Think about changes of scenery', 'Start with the questions'][$index], 'text' => 'When discussing an itinerary, ask how much time you want to spend moving between places and what a relaxed day means to you. Consider personal interests, rest and accessibility before adding more stops. Current conditions and practical arrangements should be checked with a qualified local provider.'],
            ['id' => 'verify-details', 'heading' => 'Verify before travelling', 'text' => 'A genuine guide should link to current, authoritative information for entry requirements, access, opening hours and safety. This preview deliberately omits visa advice, weather promises, exact drive times and costs because none have been verified for publication.'],
        ],
    ]);
}
foreach (route_manifest(['NEW_SITE_DEMO_PARTNERS' => '1']) as $path => $entry) {
    if ($entry['family'] !== 'guide' || !isset($entry['slug'])) {
        continue;
    }
    $slug = $entry['slug'];
    $group = $entry['guide_group'] ?? 'theme';
    $photo = $group === 'month' ? $mirissa : ($group === 'country' ? $ella : $kandy);
    $focus = match ($group) {
        'month' => 'This month page is an educational layout sample, not a weather forecast or seasonal recommendation. A published guide would need current, region-specific evidence and clear limits on what can be predicted.',
        'country' => 'This departure-country page is a sample layout, not immigration, flight or visa advice. Entry rules depend on nationality and circumstances; a genuine guide must refer visitors to current official sources.',
        default => 'This theme page is a sample editorial layout, not a specialist service promise. A genuine proposal must verify the practical requirements, accessibility and suitability of each suggested activity.',
    };
    $make('guide', $slug, $entry['title'], 'Sample educational guide layout. No published destination advice or actual product is implied.', $photo['image'], $photo['image_alt'], $path, [
        'guide_group' => $group, 'theme' => $entry['theme'] ?? '',
        'context_links' => [['title' => 'Explore sample journeys', 'url' => '/itineraries'], ['title' => 'Explore destinations', 'url' => '/destinations']],
        'sections' => [['id' => 'sample-scope', 'heading' => 'What this sample demonstrates', 'text' => $focus],
            ['id' => 'plan-with-care', 'heading' => 'Questions for a real planning conversation', 'text' => 'Discuss the time available, places of interest and practical needs before choosing a route. This preview uses illustrative records only; it cannot confirm transport, accommodation, access, entry requirements or booking terms.']],
    ]);
}
foreach (route_manifest() as $path => $entry) {
    if ($entry['family'] !== 'static' || !isset($entry['slug'])) {
        continue;
    }
    $make('static', $entry['slug'], $entry['title'], 'Read-only service-page design sample. No price, availability or service contract is confirmed.', $galle['image'], $galle['image_alt'], $path, [
        'body' => [['heading' => 'A service page, without a booking promise', 'text' => 'This synthetic page demonstrates how a service description could be presented. An actual request would need a confirmed route, passenger and luggage requirements, vehicle suitability, inclusions and a written quote. No message is sent from this preview.'],
            ['heading' => 'Costs need a verified quotation', 'text' => 'No real prices or deposit terms are shown. This preview does not establish a quote, a booking, cancellation rights or payment obligations. Published service and policy content must come from verified approved sources.']],
        'facts' => [['label' => 'Service status', 'value' => 'Design sample only']],
    ]);
}
$make('partner', 'demo-coastal-stay', 'Synthetic coastal stay', 'A fictional directory entry for layout testing only. Not a real business, endorsement or bookable property.', $mirissa['image'], $mirissa['image_alt'], '/business-partners/partner/demo-coastal-stay', [
    'category' => 'stays', 'body' => [['heading' => 'Synthetic partner profile', 'text' => 'This fictional entry demonstrates the directory design. No actual business identity, partner relationship, rating or contact details are represented. Applications and uploads are disabled.']],
]);
return ['records' => $records, 'categories' => ['cultural' => ['title' => 'Cultural journey samples', 'itinerary_slugs' => ['demo-hills-coast', 'demo-island-contrasts']]]];
