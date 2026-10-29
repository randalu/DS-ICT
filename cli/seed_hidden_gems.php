<?php
declare(strict_types=1);

/**
 * Insert hidden gem destinations directly via PHP (avoids SQL escaping issues).
 * Run: php cli\seed_hidden_gems.php
 */

require_once __DIR__ . '/../includes/functions.php';

$inserted = 0;
$skipped = 0;
$errors = 0;

$destinations = [];

// Data keyed by slug
$data = [];

// 1. Sinharaja Forest Reserve
$data['sinharaja-forest-reserve'] = [
    'name' => 'Sinharaja Forest Reserve',
    'excerpt' => 'A UNESCO World Heritage rainforest where endemic birds flash through the canopy and leeches have evolved to fall from trees.',
    'intro' => "Sinharaja is Sri Lanka's last remaining tract of primary tropical rainforest. The canopy towers 45 metres high. The air is thick with humidity, bird calls, and the buzz of insects you can't see. This is the best place in the country to see endemic wildlife — 95 per cent of Sri Lanka's endemic bird species live here. The Sri Lanka Blue Magpie, the Red-faced Malkoha, the Green-billed Coucal. The trails are muddy, the leeches are persistent, and the experience is unforgettable. You need a guide to enter. That's not a restriction, it's a service.",
    'why_visit' => "The birding is world-class. But Sinharaja is more than a checklist. It's the feeling of walking through a forest that has never been logged, where the trees are older than the country. The waterfalls that appear around blind corners. The silence when the rain stops and the canopy drips. It's not an easy day trip. It's worth the effort.",
    'things_to_do' => "Hire a guide at the entrance (mandatory). Walk the 4km loop trail to the main waterfall. Birdwatch at dawn from the research station clearing. Visit the tea plantation edge for mixed-species flocks. Swim in the natural pools below the waterfall. Drive the winding road through the outer buffer zone — the forest edge is beautiful in its own right.",
    'best_time' => "The driest months are January to March and August to September. It can rain any day of the year — that's what makes it a rainforest. The bird activity peaks in the dry season when birds concentrate around fruiting trees.",
    'how_to_get_there' => "From Colombo, take the Southern Expressway to Galle then head inland via A17 to Deniyaya — about 3.5 hours. The last 20km to the entrance are winding and slow. Public transport runs to Deniyaya, then a local bus or tuk-tuk to the village.",
    'suggested_itinerary' => "Two nights minimum. Arrive afternoon on day one, settle in. Day two: full-day guided trek with an early start. Day three: morning birding, then onward to the south coast or Udawalawe. Don't try to day-trip this from the coast — it's too rushed.",
    'where_to_stay' => "Stay in the eco-lodges near the Kudawa entrance. Blue Magpie Lodge is the best option — basic but comfortable, great food. Forest Rest House is government-run and cheap. There are no luxury options inside the reserve. That's the point.",
    'travel_tips' => "Leeches are a fact of life. Wear long gaiters, tuck your trousers into your socks, and carry a salt shaker. Don't wear bright colours — birds spot you before you spot them. Your guide will point to things you would never see alone. Trust them. Bring a waterproof bag.",
    'nearby_travel_times' => "Deniyaya: 30 min. Galle: 1.5 hrs. Udawalawe: 1.5 hrs. Colombo: 3.5 hrs.",
    'works_well_with' => "Udawalawe, Galle, Haputale",
    'cta_text' => "Let us arrange a guide who knows where the Blue Magpie nests. Permits, pick-up, and a packed lunch sorted.",
    'faq_json' => '[{"q":"Do I need a guide for Sinharaja?","a":"Yes. Guides are mandatory and available at the entrance. It costs about 3000 LKR for a half-day."},{"q":"Are the leeches really that bad?","a":"They\'re active in wet weather. Wear gaiters and bring salt. It\'s not as bad as people warn."},{"q":"Can I visit Sinharaja on a day trip from Galle?","a":"Technically yes but you\'ll spend more time driving than hiking. Stay overnight."}]',
    'destination_type' => 'Nature',
    'region_slug' => 'wildlife',
    'region_label' => 'Rainforest',
    'emoji' => '🌿',
    'recommended_stay' => '2 nights',
    'meta_title' => 'Sinharaja Rainforest Guide | Sri Lanka Nature Tours | Island Culture Travels',
    'meta_description' => 'Explore Sinharaja Forest Reserve, Sri Lanka\'s UNESCO rainforest. Private guided birding tours with endemic species, waterfall treks, and expert naturalist guides. Book your Sinharaja adventure.',
];

// 2. Haputale
$data['haputale'] = [
    'name' => 'Haputale',
    'excerpt' => 'A misty hill-country town that thinks it\'s Scotland. Crisp air, rolling tea estates, and Lipton\'s Seat.',
    'intro' => "Haputale sits on a ridge at 1,431 metres. The views drop away on both sides — on a clear day you can see both the southern coast and the eastern lowlands. It's quieter than Nuwara Eliya, less touristy than Ella, and cheaper than both. The main attraction is Lipton's Seat, a viewpoint that Sir Thomas Lipton used to survey his tea empire. The road up is a winding ribbon through tea plantations. The view at the top stretches all the way to the sea. Dambatenne Tea Factory, where Lipton processed his tea, still operates and welcomes visitors.",
    'why_visit' => "Lipton's Seat is the obvious draw, but Haputale rewards you for walking. The path from the town to the viewpoint is seven kilometres through plantations where pickers still work by hand. The train journey from Haputale station — part of the scenic main line — passes through tunnels cut into cliffs. Adisham Bungalow, a Tudor-style country house turned monastery, feels like a corner of England abandoned in the tropics. The Thangamale Sanctuary is a quiet forest trail with birds and views.",
    'things_to_do' => "Hike to Lipton's Seat (start early, 7km up, 7km back or drive one way). Tour Dambatenne Tea Factory. Visit Adisham Bungalow. Walk the Thangamale Sanctuary trail. Ride the train one stop in either direction for the scenery. Watch the sunset from the Haputale Gap viewpoint.",
    'best_time' => "January to April is the dry season and the best time for clear views. May to July is misty and wet. August and September offer a second clear window. The rest of the year you're gambling on cloud cover.",
    'how_to_get_there' => "From Colombo, take the A4 highway through Ratnapura and Balangoda — about 4 hours. The train from Colombo to Haputale takes 6 hours and is one of the most scenic routes in the country. Buses run from Colombo, Kandy, and Ella.",
    'suggested_itinerary' => "Two nights is right. Day one: arrive by train, afternoon walk to the Gap viewpoint. Day two: early morning hike to Lipton's Seat, tea factory, Adisham. Day three: train onward to Ella or Nuwara Eliya. A private driver makes the Lipton's Seat trip much more flexible.",
    'where_to_stay' => "Haputale has limited accommodation but good options. The Cozy Cottage has stunning valley views from its hillside location. Adisham Bungalow offers basic monastic stay (book ahead). For mid-range, try Haputale Breeze. No luxury options — this is genuine small-town hill country.",
    'travel_tips' => "The Lipton's Seat road is steep and winding — a tuk-tuk can do it but a car is more comfortable. Pack a jacket; it gets cold when the mist rolls in. The tea factory tour is free and includes a tasting. Buy your tea there, not in town.",
    'nearby_travel_times' => "Ella: 45 min. Nuwara Eliya: 1.5 hrs. Bandarawela: 15 min. Colombo: 4 hrs.",
    'works_well_with' => "Ella, Nuwara Eliya, Bandarawela",
    'cta_text' => "We can arrange a private driver to meet your train and take you up to Lipton's Seat before the tour buses arrive.",
    'faq_json' => '[{"q":"Is Haputale better than Ella?","a":"Different vibe. Ella is livelier with more food options. Haputale is quieter, cheaper, and has better views from fewer crowds."},{"q":"Can I walk to Lipton\'s Seat?","a":"Yes, 7km each way. Takes 2 hours up, 1.5 down. Or drive one way and walk the other."},{"q":"Is the weather cold?","a":"Yes — 10-20°C year-round. Bring a jacket even in summer."}]',
    'destination_type' => 'Hill Country',
    'region_slug' => 'hill',
    'region_label' => 'Hill Country',
    'emoji' => '🍃',
    'recommended_stay' => '2 nights',
    'meta_title' => 'Haputale Travel Guide | Lipton\'s Seat & Tea Country | Island Culture Travels',
    'meta_description' => 'Discover Haputale in Sri Lanka\'s hill country — Lipton\'s Seat, tea factory tours, and scenic train rides. Private chauffeur-guided tours from Colombo, Ella, and Nuwara Eliya.',
];

// 3. Wilpattu National Park
$data['wilpattu-national-park'] = [
    'name' => 'Wilpattu National Park',
    'excerpt' => 'Sri Lanka\'s largest national park. Raw, uncrowded, and home to leopards who haven\'t learned to ignore jeeps.',
    'intro' => "Wilpattu covers 1,317 square kilometres of dry-zone forest, scrub, and natural sand-rimmed lakes called willus. It's bigger than Yala, quieter than Yala, and in some ways wilder. The leopards here are less habituated to vehicles, which means sightings feel more like real encounters and not zoo visits. The park was closed for years during the civil war. The wildlife rebounded. The sloth bear population is one of the healthiest in the country. The willus — freshwater lakes that dot the landscape — attract birds and animals in the dry season. You can have entire sections of the park to yourself.",
    'why_visit' => "The solitude. In Yala you queue with twenty jeeps for a leopard sighting. In Wilpattu you might be the only vehicle on a trail. The landscape is different — more open, more lake-edged, less dense. The bear sightings are more reliable than anywhere else. The birding is excellent. And the sense of discovery — of being in a place that hasn't been fully commodified for tourism — is rare and valuable.",
    'things_to_do' => "Full-day jeep safari starting at dawn. Birdwatch at the willus — painted storks, eagles, hornbills. Look for sloth bears in the early morning. Visit the ancient Modagam Viharaya ruins inside the park. Picnic at a secluded lakeside. Evening game drive for leopard activity near water sources.",
    'best_time' => "The park is open February to October. Peak leopard sightings are May and June. November to January brings rain and some tracks close. The dry season (May-September) concentrates animals around the willus for the best viewing.",
    'how_to_get_there' => "From Colombo, take the A12 highway toward Puttalam, then north to the park entrance — about 4 hours. The nearest town is Anuradhapura (1 hour from the park). Buses run from Colombo to Anuradhapura, then a local service to the park gate.",
    'suggested_itinerary' => "Base yourself in a lodge near the park entrance for two nights. Day one: afternoon safari. Day two: full-day safari with a picnic. Day three: morning safari, then drive to Anuradhapura or onward to Jaffna. The park is large — don't try to cover it in one game drive.",
    'where_to_stay' => "Accommodation is clustered near the park entrance. Wilpattu Safari Camp is a tented camp with proper beds and good food. Big Game Camp is mid-range and reliable. For luxury, Mahoora Tented Safari Camp offers glamping. Most lodges organise safaris.",
    'travel_tips' => "Wilpattu is dusty and the roads are rough — bring a mask and secure your camera. Start safaris at 5 AM for the best wildlife activity. Book your jeep and driver in advance; not all drivers know the park well. Carry cash — no ATMs near the park.",
    'nearby_travel_times' => "Anuradhapura: 1 hr. Puttalam: 1.5 hrs. Colombo: 4 hrs. Jaffna: 3 hrs.",
    'works_well_with' => "Anuradhapura, Jaffna, Kalpitiya",
    'cta_text' => "Skip the Yala crowds. We'll arrange a private Wilpattu safari with a driver who knows where the bears feed.",
    'faq_json' => '[{"q":"Is Wilpattu better than Yala for leopards?","a":"It depends. Yala has a higher density. Wilpattu offers a more private experience with fewer jeeps."},{"q":"What is a willu?","a":"A natural sand-rimmed lake unique to this region. Animals gather here in the dry season."},{"q":"Can I visit Wilpattu year-round?","a":"No. The park closes November to January for the monsoon. Open February to October."}]',
    'destination_type' => 'Wildlife',
    'region_slug' => 'wildlife',
    'region_label' => 'Wildlife',
    'emoji' => '🐆',
    'recommended_stay' => '2 nights',
    'meta_title' => 'Wilpattu National Park Safari | Private Wildlife Tours | Island Culture Travels',
    'meta_description' => 'Explore Wilpattu National Park — Sri Lanka\'s largest wilderness. Private leopard and sloth bear safaris with expert drivers, uncrowded trails, and luxury tented camps.',
];

// 4. Kalpitiya
$data['kalpitiya'] = [
    'name' => 'Kalpitiya',
    'excerpt' => 'A narrow peninsula where kitesurfers chase wind and spinner dolphins race the boats.',
    'intro' => "Kalpitiya is a 30-kilometre strip of land on Sri Lanka's north-west coast. One side is the Indian Ocean, the other is the Puttalam Lagoon. The beach on the ocean side is wild, empty, and windswept. The lagoon side is flat, shallow, and perfect for kitesurfing. The Bar Reef — a protected marine area — lies just offshore, one of the most biodiverse reef systems in the country. Kitesurfers discovered Kalpitiya years ago and haven't stopped coming. The windiest months draw an international crowd. But the real magic is the dolphins — pods of hundreds of spinner dolphins that patrol the reef edge.",
    'why_visit' => "The kitesurfing is world-class — steady trade winds and flat lagoon water from May to September. The dolphin watching is even better. Boats can get you to pods of 500-1,000 spinner dolphins that leap and spin alongside the hull. The Bar Reef snorkelling is good when conditions are calm. And the emptiness of the west-coast beaches — miles of sand with nobody on them — is increasingly rare in Sri Lanka.",
    'things_to_do' => "Kitesurf or take lessons on the lagoon (May-September). Dolphin-watching boat trip (best October-February). Snorkel the Bar Reef. Visit the Dutch Reformed Church in the old fort. Explore the fishing villages along the peninsula. Sandboard on the coastal dunes. Watch the sunset from the lagoon side — it's stunning.",
    'best_time' => "Two seasons: May to September is wind season — perfect for kitesurfing, calm lagoon, strong winds. October to February is dolphin season — calm seas, reliable sightings. March-April and October are transitional and quieter. Choose your season based on your activity.",
    'how_to_get_there' => "From Colombo, take the A12 highway toward Puttalam — about 3 hours. The last stretch to the peninsula is a narrow road. Buses run from Colombo to Puttalam, then a local bus to Kalpitiya town. A private car is strongly recommended.",
    'suggested_itinerary' => "Three to four days if you're kitesurfing. Two days for dolphin watching. Day one: arrive, afternoon dolphin cruise. Day two: full day kitesurfing or reef snorkelling. Day three: morning kitesurf, afternoon exploration of the peninsula. Head back to Colombo or onward to Wilpattu.",
    'where_to_stay' => "Accommodation is mostly basic guesthouses and kitesurf camps. Kite Camp Sri Lanka is popular with surfers — simple rooms, good vibe. Kalpitiya Beach Resort is mid-range. For something unique, stay in one of the Dutch-period villas in the old town. Luxury options are limited.",
    'travel_tips' => "The wind peaks in June and July — ideal for experienced kitesurfers. Beginners should come in May or September when conditions are gentler. Dolphin trips are best booked through your accommodation. The Bar Reef is weather-dependent — if the sea is rough, the snorkelling is poor.",
    'nearby_travel_times' => "Puttalam: 30 min. Wilpattu: 1.5 hrs. Colombo: 3 hrs. Negombo: 2 hrs.",
    'works_well_with' => "Wilpattu, Negombo, Anuradhapura",
    'cta_text' => "We'll arrange a private driver to get you to Kalpitiya and book your dolphin cruise with a trusted operator.",
    'faq_json' => '[{"q":"Is Kalpitiya only for kitesurfers?","a":"No. The dolphin watching and reef snorkelling are excellent even if you don\'t kite."},{"q":"When is the best time for dolphins?","a":"October to February. Pods of 500+ spinner dolphins are common."},{"q":"Is the water safe for swimming?","a":"The lagoon side is very safe — shallow and flat. The ocean side has strong currents in wind season."}]',
    'destination_type' => 'Beach',
    'region_slug' => '',
    'region_label' => '',
    'emoji' => '🪁',
    'recommended_stay' => '2-3 nights',
    'meta_title' => 'Kalpitiya Travel Guide | Kitesurfing & Dolphin Watching | Island Culture Travels',
    'meta_description' => 'Plan your Kalpitiya trip — world-class kitesurfing, spinner dolphin encounters, and Bar Reef snorkelling. Private chauffeur-guided tours from Colombo to Sri Lanka\'s north-west coast.',
];

// 5. Gal Oya National Park
$data['gal-oya-national-park'] = [
    'name' => 'Gal Oya National Park',
    'excerpt' => 'The only park in Sri Lanka where you can do a walking safari. With indigenous Vedda trackers.',
    'intro' => "Gal Oya is built around the Senanayake Samudra, Sri Lanka's largest reservoir. The park covers 2,500 hectares of forest, grassland, and water. What makes it special is the walking safari — the only park in Sri Lanka where you can walk with a guide to see wildlife. You're accompanied by trackers from the indigenous Vedda community, who have lived in these forests for thousands of years. The boat safari on the reservoir is equally unique — you'll see elephants swimming between islands, crocodiles basking on rocks, and hundreds of birds along the shoreline.",
    'why_visit' => "The walking safari is genuinely unique. In Yala or Wilpattu you're confined to a jeep. Here, you walk the same paths the Vedda have walked for generations. You learn about edible plants, animal tracks, and traditional hunting methods. The boat safari reveals a different side of the park — elephants wading chest-deep, their heads just visible above the water. The birding is exceptional: 150+ species including endemics like the Sri Lanka grey hornbill.",
    'things_to_do' => "Guided walking safari with Vedda trackers. Boat safari on Senanayake Samudra (early morning or late afternoon). Birding along the reservoir edge. Visit a Vedda village (book through your lodge). Sunset cruise on the reservoir. Look for elephants swimming between islands — a rare sight.",
    'best_time' => "March to July is the dry season — animals concentrate near the water and walking conditions are best. August to February is greener, the walking is still good but animals are more dispersed. December and January can see rain.",
    'how_to_get_there' => "From Colombo, take the A4 highway through Rathnapura and Badulla — about 7 hours. From the south coast (Arugam Bay), it's about 3 hours north. The park entrance is near the town of Siyambalanduwa. A private driver is essential — public transport is limited.",
    'suggested_itinerary' => "Two nights at minimum. Day one: arrive, afternoon boat safari. Day two: early morning walking safari, afternoon Vedda village visit. Day three: morning bird walk, then onward to the east coast. The park is remote — budget your time accordingly.",
    'where_to_stay' => "Gal Oya Lodge is the only premium option — nine eco-lodges with plunge pools, excellent food, and guided activities included. It's expensive but exceptional. Budget options are limited to basic guesthouses in Siyambalanduwa. Book Gal Oya Lodge well in advance.",
    'travel_tips' => "The walking safari is moderate difficulty — you cover 4-6km on uneven ground. Wear sturdy shoes. Bring insect repellent and plenty of water. The Vedda trackers speak limited English; your lodge guide will translate. The boat safari is essential — don't skip it.",
    'nearby_travel_times' => "Siyambalanduwa: 15 min. Arugam Bay: 3 hrs. Badulla: 2 hrs. Colombo: 7 hrs.",
    'works_well_with' => "Arugam Bay, Badulla, Yala",
    'cta_text' => "We'll arrange your Gal Oya stay including the walking safari, boat cruise, and Vedda guide — everything organised in advance.",
    'faq_json' => '[{"q":"Is the walking safari safe?","a":"Yes. You\'re accompanied by armed trackers and a guide who know the animal behaviour and terrain."},{"q":"Will I see elephants?","a":"Very likely — especially on the boat safari. Elephants swim between islands regularly."},{"q":"Do I need to be fit for the walk?","a":"Moderate fitness is enough. The terrain is uneven but the pace is slow and trackers adapt to your ability."}]',
    'destination_type' => 'Wildlife',
    'region_slug' => 'wildlife',
    'region_label' => 'Wildlife',
    'emoji' => '🛶',
    'recommended_stay' => '2 nights',
    'meta_title' => 'Gal Oya National Park | Walking Safaris with Vedda Trackers | Island Culture Travels',
    'meta_description' => 'Experience Gal Oya National Park — Sri Lanka\'s only walking safari with indigenous Vedda guides, boat safaris on the reservoir, and elephant sightings. Private tours available.',
];

// 6. Ritigala
$data['ritigala'] = [
    'name' => 'Ritigala',
    'excerpt' => 'An ancient monastery hidden in a strict nature reserve where the forest has reclaimed everything.',
    'intro' => "Ritigala is not a typical Sri Lanka tourist attraction. It's a strict nature reserve wrapped around the ruins of a 1,500-year-old Buddhist monastery. The site is overgrown. Massive stone bridges and meditation platforms lie half-buried in the forest. The paths are uneven and sometimes unclear. You must be accompanied by a guide from the forest department. There are no souvenir stalls, no cafes, no crowds. The rule here is simple: do not take anything, do not leave anything, do not disturb anything. Picking a leaf is forbidden. The silence is profound.",
    'why_visit' => "The atmosphere is unlike anywhere else in Sri Lanka. The combination of ancient ruins and protected forest creates a place that feels genuinely sacred. The stone bridges — some still intact after 1,500 years — are engineering marvels. The double-platform meditation areas show a level of sophistication you don't expect in a forest monastery. And the bird and butterfly life in the reserve is excellent. It's a place for people who value atmosphere over Instagram moments.",
    'things_to_do' => "Guided hike through the ruins (mandatory — book at the entrance). Walk the ancient stone pathways and bridges. Visit the meditation platforms. Birdwatch — the reserve has 120+ species. Spot butterflies — Ritigala is known for rare species. Enjoy the silence. There's nothing to buy or eat — that's the point.",
    'best_time' => "February to September is the dry season. November to December is wet — the paths get slippery and the leeches come out. January is a shoulder month with reasonable conditions. Go early in the morning for the best bird activity.",
    'how_to_get_there' => "From Colombo, take the A1 through Dambulla, then the A9 toward Anuradhapura — about 4 hours. The turn-off is at Maradankadawala. A private vehicle is essential — public transport does not reach the entrance. From Sigiriya, it's about 1 hour.",
    'suggested_itinerary' => "This is a half-day excursion from Habarana or Sigiriya. Go early (7 AM). The hike takes 2-3 hours. Return to your base for lunch. Don't plan to stay overnight at Ritigala — there are no facilities. Combine with a visit to Sigiriya or Dambulla the same day if you have energy.",
    'where_to_stay' => "Stay in Habarana or Sigiriya — both are 30-60 minutes from Ritigala. Jetwing Lake in Habarana is a good mid-range option. For budget, guesthouses in Maradankadawala are basic but close. There is no accommodation at Ritigala itself.",
    'travel_tips' => "You must register at the forest department office at the entrance. Hire a guide — it's mandatory and costs about 2000 LKR. Wear sturdy hiking shoes. Take water and snacks — there are no shops. The trail is moderate but has steep sections.",
    'nearby_travel_times' => "Habarana: 30 min. Sigiriya: 1 hr. Dambulla: 1 hr. Anuradhapura: 1 hr.",
    'works_well_with' => "Sigiriya, Dambulla, Habarana, Anuradhapura",
    'cta_text' => "We'll handle the entry permit, mandatory guide booking, and private transport from your Sigiriya or Habarana hotel.",
    'faq_json' => '[{"q":"Why is Ritigala a strict nature reserve?","a":"To protect both the ancient ruins and the forest ecosystem. Strict rules apply."},{"q":"Can I visit Ritigala without a guide?","a":"No. A forest department guide is mandatory. They\'re knowledgeable and add to the experience."},{"q":"Is Ritigala worth visiting after Sigiriya?","a":"Yes, if you want a completely different experience — quiet, spiritual, and uncrowded."}]',
    'destination_type' => 'Heritage',
    'region_slug' => '',
    'region_label' => '',
    'emoji' => '🏛️',
    'recommended_stay' => 'Half day',
    'meta_title' => 'Ritigala Ancient Monastery | Strict Nature Reserve Tours | Island Culture Travels',
    'meta_description' => 'Visit Ritigala — a mystical ancient monastery in a strict nature reserve near Sigiriya. Private guided hikes through 8th-century ruins and untouched forest.',
];

// 7. Ussangoda
$data['ussangoda'] = [
    'name' => 'Ussangoda',
    'excerpt' => "A red-earth plateau that looks like the surface of Mars, overlooking the Indian Ocean.",
    'intro' => "Ussangoda is one of Sri Lanka's strangest landscapes. A flat, barren plateau of reddish-orange soil that rises above the coconut plantations of the south coast. The ground is bare — almost nothing grows here. The soil is said to be rich in iron, giving it the Martian colour. Local legend ties the site to the Ramayana epic — it's believed to be where King Ravana's flying machine landed. Geology offers a different explanation: serpentinite rock from an ancient ocean floor. Either way, it's unforgettable. The ocean stretches to the horizon from the cliff edge. The wind never stops blowing.",
    'why_visit' => "For the sheer weirdness. You will not see anything else like Ussangoda in Sri Lanka. The colour contrast — red earth, green palms, blue ocean — is extraordinary. The Ramayana connection appeals to mythology enthusiasts. The birdlife around the cliff edge is good. And the sunsets are spectacular — the red soil glows in the fading light.",
    'things_to_do' => "Photograph the landscape (best light is late afternoon). Walk to the cliff edge for ocean views. Look for eagle and kite species riding the wind. Visit the Ramayana-related sites in the area. Explore the nearby coconut estates.",
    'best_time' => "December to March is the dry season and the best time to visit. The plateau is exposed and gets extremely hot in the middle of the day — come in the late afternoon for golden light and cooler temperatures.",
    'how_to_get_there' => "From Colombo, take the Southern Expressway to Matara (2.5 hours), then continue along the A2 coast road east toward Tangalle. The turn-off is near the village of Nonagama. From Tangalle, it's about 30 minutes west.",
    'suggested_itinerary' => "This is a one-hour stop, not a destination. Visit in the late afternoon (around 4 PM) on your way between Galle/Mirissa and Tangalle/Yala. The light is best, the heat is manageable.",
    'where_to_stay' => "Stay in Tangalle (30 min east) or Mirissa (45 min west). Tangalle offers quiet luxury — Anantara Peace Haven is excellent. Mirissa has more variety. There are no accommodation options near Ussangoda itself.",
    'travel_tips' => "The plateau has no shade at all — bring a hat, sunscreen, and water. The ground is uneven in places — wear sturdy footwear. The local guide (available at the entrance) can explain the geological and mythological stories. Entry fee is minimal.",
    'nearby_travel_times' => "Tangalle: 30 min. Mirissa: 45 min. Matara: 40 min. Galle: 1.5 hrs.",
    'works_well_with' => "Tangalle, Mirissa, Galle, Yala",
    'cta_text' => "We can include Ussangoda as a photo stop on your south coast route. Best timed for sunset with a private driver who knows the way.",
    'faq_json' => '[{"q":"What caused the red soil at Ussangoda?","a":"The soil is rich in iron from serpentinite rock. Geological uplift exposed ancient ocean floor."},{"q":"Is Ussangoda connected to the Ramayana?","a":"Local legend says it was a landing site for King Ravana\'s flying machine."},{"q":"How long should I spend here?","a":"30 minutes to an hour is enough. It\'s a photo stop, not a full-day destination."}]',
    'destination_type' => 'Nature',
    'region_slug' => 'south',
    'region_label' => 'South Coast',
    'emoji' => '🔴',
    'recommended_stay' => '1 hour',
    'meta_title' => 'Ussangoda Red Earth Plateau | Ramayana Trail Sri Lanka | Island Culture Travels',
    'meta_description' => 'Visit Ussangoda — a surreal red-earth plateau on Sri Lanka\'s south coast. Unique geology, Ramayana mythology, and stunning ocean views. Private tours from Tangalle, Mirissa, Galle.',
];

// 8. Yapahuwa
$data['yapahuwa'] = [
    'name' => 'Yapahuwa',
    'excerpt' => 'A 13th-century rock fortress with an ornamental staircase that rivals anything in the Cultural Triangle.',
    'intro' => "Yapahuwa served as Sri Lanka's capital briefly in the 13th century. The rock rises 100 metres above the surrounding plains. The approach is lined with a grand ornamental staircase — carved balustrades, stone steps, and a terrace that once held a royal palace. The Tooth Relic was kept here before it moved to Kandy. The fortress was sacked and abandoned, but the staircase survived. The climb is steep but short. The views from the top stretch across the dry zone. There are no crowds. There are no souvenir shops. Just a beautiful, forgotten capital waiting to be discovered.",
    'why_visit' => "The staircase is genuinely spectacular — intricate stone carving that doesn't get the attention it deserves. The lack of crowds means you can explore at your own pace. The rock summit offers panoramic views of the surrounding plains and the distant Knuckles range. The site has a peaceful, forgotten atmosphere that Sigiriya lost decades ago.",
    'things_to_do' => "Climb the ornamental staircase (the highlight). Explore the ruins on the summit. Visit the cave temple at the base. Walk the outer fortifications and moat. Photograph the stone carvings — the detail is exceptional. Watch the sunset from the summit.",
    'best_time' => "November to April is the coolest and driest period. The site is exposed — avoid midday in the hotter months (May-October). Early morning (8 AM) or late afternoon (4 PM) are best for light and temperature.",
    'how_to_get_there' => "From Colombo, take the A1 highway to Kurunegala (2 hours), then continue north toward Anuradhapura. The turn-off is at Maho Junction. From Sigiriya, it's about 1.5 hours west. A private vehicle is recommended.",
    'suggested_itinerary' => "Yapahuwa is a half-day trip from Kurunegala, Anuradhapura, or Sigiriya. Combine with the Panduwasnuwara ruins (nearby) for a fuller day. Have lunch in Kurunegala on the way back. No need to stay overnight.",
    'where_to_stay' => "Stay in Kurunegala (45 minutes south) or Anuradhapura (1 hour north). Kurunegala has better accommodation options for most budgets. No accommodation at Yapahuwa itself.",
    'travel_tips' => "The climb is steep but short — 10-15 minutes to the top. Wear proper shoes. Take water — there are no vendors at the summit. The site is not wheelchair accessible. Entry fee is minimal.",
    'nearby_travel_times' => "Kurunegala: 45 min. Anuradhapura: 1 hr. Sigiriya: 1.5 hrs. Dambulla: 1 hr 15 min.",
    'works_well_with' => "Anuradhapura, Sigiriya, Dambulla, Kurunegala",
    'cta_text' => "Add Yapahuwa to your Cultural Triangle route — it's the anti-Sigiriya, with a fraction of the crowds and equally impressive stonework.",
    'faq_json' => '[{"q":"How does Yapahuwa compare to Sigiriya?","a":"Smaller scale, fewer crowds, less commercial. The staircase is the highlight."},{"q":"How long should I spend at Yapahuwa?","a":"About 1.5 to 2 hours for a thorough visit. The site is compact."},{"q":"Is the climb difficult?","a":"Steep but short — about 10-15 minutes. Anyone with basic fitness can manage it."}]',
    'destination_type' => 'Heritage',
    'region_slug' => '',
    'region_label' => '',
    'emoji' => '🪜',
    'recommended_stay' => 'Half day',
    'meta_title' => 'Yapahuwa Rock Fortress | Offbeat Heritage Sri Lanka | Island Culture Travels',
    'meta_description' => 'Discover Yapahuwa — a stunning 13th-century rock fortress with an ornamental staircase, panoramic views, and no crowds. Private tours from the Cultural Triangle.',
];

// 9. Minneriya / Kaudulla
$data['minneriya-kaudulla'] = [
    'name' => 'Minneriya / Kaudulla',
    'excerpt' => 'The Gathering — up to 300 wild elephants converge on a single reservoir bed in the dry season.',
    'intro' => "Minneriya and Kaudulla are two adjacent national parks in the North Central Province. They're famous for a phenomenon called the Gathering — during the dry season, hundreds of wild elephants congregate around the receding reservoir beds to graze on fresh grass. It's one of the largest elephant gatherings in the world. The numbers peak between July and October, when 200-300 elephants can be seen in a single afternoon. The parks are small and the elephants are visible — you don't need to search for hours to find them.",
    'why_visit' => "The scale of the Gathering is extraordinary. You'll see family groups with calves, teenage bulls play-fighting, matriarchs leading their herds across the open grassland. The proximity of the elephants to the road means you get close views without disturbing them. The birdlife is excellent too — painted storks, pelicans, openbill storks, and eagles. Minneriya and Kaudulla together offer a reliable, crowd-pleasing wildlife experience.",
    'things_to_do' => "Afternoon jeep safari in Minneriya (the Gathering peaks in the late afternoon). Morning safari in Kaudulla for a different perspective. Photograph elephant herds against the golden grassland. Birdwatch around the reservoir edge. Visit the ancient Minneriya tank — a 1,700-year-old reservoir still in use.",
    'best_time' => "July to October is peak Gathering season. November to June sees fewer elephants. The best viewing is August and September. Start the safari around 3 PM for the best activity.",
    'how_to_get_there' => "From Colombo, take the A1 to Dambulla (3.5 hours), then the A9 north for 20 minutes to Habarana. The park entrance is a short drive from Habarana. Both parks are easily accessible from the Cultural Triangle hub.",
    'suggested_itinerary' => "Base yourself in Habarana or Sigiriya. Day one: arrive, afternoon Minneriya safari (3-6 PM). Day two: morning Kaudulla safari, afternoon Sigiriya or Dambulla. The parks work beautifully as a half-day add-on.",
    'where_to_stay' => "Stay in Habarana, Sigiriya, or Dambulla. Habarana has the best range near the parks — Jetwing Lake is excellent. Sigiriya offers resort options. Dambulla has budget-friendly choices.",
    'travel_tips' => "Book the safari through your hotel or a trusted operator. An afternoon safari (2-6 PM) is best for the Gathering. Take a face mask for dust. Binoculars are useful for birding.",
    'nearby_travel_times' => "Habarana: 15 min. Sigiriya: 30 min. Dambulla: 40 min. Polonnaruwa: 1 hr.",
    'works_well_with' => "Sigiriya, Dambulla, Polonnaruwa, Habarana",
    'cta_text' => "We can arrange a combined Sigiriya and Minneriya day — climb the rock at dawn, safari at dusk. The best of both worlds.",
    'faq_json' => '[{"q":"What is the Gathering?","a":"A seasonal congregation of wild elephants (up to 300) at the Minneriya reservoir bed during the dry season."},{"q":"When is the best time to see the Gathering?","a":"July to October, peaking in August and September. The afternoon safari is best."},{"q":"Can I see elephants year-round?","a":"Yes, but the numbers drop significantly outside of the Gathering season (November-June)."}]',
    'destination_type' => 'Wildlife',
    'region_slug' => 'wildlife',
    'region_label' => 'Wildlife',
    'emoji' => '🐘',
    'recommended_stay' => 'Half day',
    'meta_title' => 'Minneriya & Kaudulla National Parks | The Elephant Gathering | Island Culture Travels',
    'meta_description' => 'Witness the Gathering at Minneriya — up to 300 wild elephants in one place on a private jeep safari. Combine with Sigiriya in your Cultural Triangle itinerary.',
];

// 10. Delft Island
$data['delft-island'] = [
    'name' => 'Delft Island',
    'excerpt' => 'A remote island with wild horses, giant baobab trees, and fences made of coral. No, really.',
    'intro' => "Delft Island (Neduntheevu in Tamil) sits in the Palk Strait between Sri Lanka and India. It's a flat, arid, windswept island that feels culturally and geographically separate from the rest of the country. The Portuguese built a fort here. The Dutch left a horse-breeding station. The descendants of those horses still run wild — herds of them roam the island. Giant baobab trees — introduced by Arab traders centuries ago — stand alone in the barren landscape. The fences are built from brain coral. The houses are made of limestone. There are no luxury resorts, no restaurants, and very few vehicles.",
    'why_visit' => "The wild horses are the main attraction — hundreds of them roaming freely across the island. The baobab trees are astonishing — some are over 500 years old with trunks that would take 20 people to encircle. The coral fences are a unique sight. The ruined Dutch fort is a beautiful skeleton of a colonial past. And the isolation itself is the experience — this is a place that has not been developed for tourism at all.",
    'things_to_do' => "See the wild horses (they're everywhere). Visit the giant baobab tree — the largest on the island. Walk through the Dutch fort ruins. See the coral fences. Visit the old Portuguese church. Explore the limestone quarry. Swim at the empty beaches. Cycle around the island.",
    'best_time' => "December to March is the dry season and the only reliable time to visit. The ferry runs more regularly. April to November brings rough seas and unpredictable crossings.",
    'how_to_get_there' => "From Jaffna town, drive to Kurikadduwan jetty (about 1 hour). The ferry takes 1.5-2 hours and runs once or twice daily. Check the schedule the day before — it changes with the season.",
    'suggested_itinerary' => "This is a day trip from Jaffna. Catch the early ferry (around 7 AM). Arrive on Delft by 9 AM. Explore for 4-5 hours. Catch the afternoon ferry back (around 3 PM).",
    'where_to_stay' => "Accommodation on Delft is extremely basic — a few homestays with minimal amenities. Most visitors day-trip from Jaffna. Stay in Jaffna for comfort — Jetwing Jaffna is excellent.",
    'travel_tips' => "The ferry can be cancelled at short notice — have a backup plan. Take snacks, water, and lunch — food options on the island are limited. The sun is intense — bring sunscreen, a hat, and a long-sleeved shirt. Take cash — no ATMs.",
    'nearby_travel_times' => "Jaffna: 2 hrs (including ferry). Point Pedro: 1.5 hrs. Colombo: 7 hrs.",
    'works_well_with' => "Jaffna, Point Pedro, Nallur",
    'cta_text' => "We can organise your Jaffna and Delft Island trip — ferry tickets, vehicle on the island, and a guide to show you the wild horses and baobabs.",
    'faq_json' => '[{"q":"How do I get to Delft Island?","a":"Ferry from Kurikadduwan jetty near Jaffna. The crossing takes 1.5-2 hours."},{"q":"Is Delft Island worth the journey?","a":"Yes, if you want a genuinely off-the-beaten-path experience."},{"q":"Can I visit Delft on my own?","a":"Yes, but arranging transport and ferry tickets is easier with a local operator."}]',
    'destination_type' => 'Offbeat',
    'region_slug' => '',
    'region_label' => '',
    'emoji' => '🐴',
    'recommended_stay' => 'Day trip',
    'meta_title' => 'Delft Island Guide | Wild Horses & Baobab Trees | Island Culture Travels',
    'meta_description' => 'Discover Delft Island — wild horses, giant baobabs, and coral fences in Sri Lanka\'s remote north. Private day trips from Jaffna with ferry, vehicle, and guide arranged.',
];

// 11. Bundala National Park
$data['bundala-national-park'] = [
    'name' => 'Bundala National Park',
    'excerpt' => 'A Ramsar wetland where flamingos outnumber tourists and the lagoons turn pink in winter.',
    'intro' => "Bundala is a coastal wetland park on Sri Lanka's south-east coast, 25 kilometres east of Matara. It's a Ramsar-designated site — one of the most important wetlands in the country. The park is a mosaic of shallow lagoons, scrubland, and sand dunes. In the northern winter (November to March), tens of thousands of migratory birds arrive. The greater flamingo is the star — flocks of them turn the lagoons pink. But you'll also see pelicans, storks, spoonbills, and 200 other species. Bundala is less famous than Yala, less visited, and more peaceful.",
    'why_visit' => "The flamingos. Bundala hosts the largest wintering population of greater flamingos in Sri Lanka. Seeing 500+ flamingos wading through a shallow lagoon against a golden sunset is unforgettable. The park is excellent for migratory bird photography. The landscape is beautiful — stark, open, with sand dunes and the ocean beyond. And because Bundala is quieter than Yala, you can take your time.",
    'things_to_do' => "Birding safari (focus on the lagoon edges for waders and waterbirds). Photograph flamingos (best in late afternoon light). Explore the sand dunes and beach. Visit the ancient Bundala Lena cave temple. Watch the sunset over the main lagoon.",
    'best_time' => "November to March is the peak bird season — migratory birds from Siberia and Europe arrive in large numbers. April and October are shoulder months. May to September is the low season with mostly resident birds.",
    'how_to_get_there' => "From Colombo, take the Southern Expressway to Matara (2.5 hours), then continue along the A2 coast road east toward Hambantota — about 3 hours total.",
    'suggested_itinerary' => "Half-day trip from Tangalle, Mirissa, or Yala. A morning safari (6-10 AM) is best for bird activity. Arrive early, spend 2-3 hours exploring the lagoons.",
    'where_to_stay' => "Stay in Tangalle (30 min west) or near Yala (45 min east). Tangalle offers the best range from luxury (Anantara) to boutique.",
    'travel_tips' => "The best bird photography is in the late afternoon. Wear neutral colours. Bring binoculars and a long lens. The lagoons are shallow and the jeep can get close to the birds.",
    'nearby_travel_times' => "Tangalle: 30 min. Yala: 45 min. Matara: 1 hr. Galle: 2 hrs.",
    'works_well_with' => "Tangalle, Yala, Mirissa, Galle",
    'cta_text' => "Let us arrange a Bundala birding safari with a guide who knows where the flamingos feed at different tide levels.",
    'faq_json' => '[{"q":"Is Bundala worth visiting if I\'m not a birder?","a":"Yes. The landscape is beautiful, the flamingos are spectacular, and it\'s much quieter than Yala."},{"q":"When is the best time for flamingos?","a":"December to February when the migratory population peaks."},{"q":"Can I visit Bundala and Yala in one day?","a":"Yes — morning in Bundala, afternoon safari in Yala. It\'s a long day but rewarding."}]',
    'destination_type' => 'Wildlife',
    'region_slug' => 'wildlife',
    'region_label' => 'Wildlife',
    'emoji' => '🦩',
    'recommended_stay' => 'Half day',
    'meta_title' => 'Bundala National Park Birding | Flamingo Safaris | Island Culture Travels',
    'meta_description' => 'Explore Bundala National Park — Sri Lanka\'s premier Ramsar wetland for flamingos, migratory birds, and photographic safaris. Private birding tours from Tangalle and Yala.',
];

// 12. Meemure Village
$data['meemure-village'] = [
    'name' => 'Meemure Village',
    'excerpt' => 'A remote village in the Knuckles foothills where time stopped a century ago. No roads, no WiFi, no regrets.',
    'intro' => "Meemure is a traditional Sri Lankan village tucked into a valley at the edge of the Knuckles Mountain Range. There are no roads into the village — you park at the trailhead and hike the last kilometre across a riverbed. There are no hotels. You stay in a villager's home, eat what the family eats, and sleep on a mattress on the floor. The valley is stunning — rice terraces climb the slopes, crystal-clear streams run through the village, and the forest-covered ridges rise on all sides. The people of Meemure have maintained their traditional ways of life.",
    'why_visit' => "For the authenticity. Meemure is not a curated cultural experience — it's a living village that happens to welcome visitors. You'll help prepare the meal, learn about traditional farming, and walk through rice paddies that have been cultivated for centuries. The natural setting is breathtaking with waterfalls, swimming holes, and hiking trails accessible from the village. The stargazing at night is phenomenal — no light pollution at all.",
    'things_to_do' => "Walk across the riverbed into the village. Swim in natural pools. Hike to nearby waterfalls. Walk through rice terraces and learn about traditional farming. Cook and eat with your host family. Stargazing at night. Day hike into the Knuckles forest reserve.",
    'best_time' => "January to April is the dry season — the river is low and the hiking trails are accessible. May to July brings heavy rain. August to September is a secondary clear window. The dry season is much more comfortable.",
    'how_to_get_there' => "From Kandy, drive to the village of Loolwatte — about 2.5 hours by car (the last 30 minutes is unpaved road). Park at Loolwatte and walk 1km across the riverbed into Meemure.",
    'suggested_itinerary' => "Stay at least one night. Day one: arrive afternoon, swim, explore the village. Evening: traditional dinner and stargazing. Day two: morning hike to a waterfall, then onward to Kandy or Knuckles.",
    'where_to_stay' => "Stay in a village homestay — there are no hotels. Your host will provide a mattress, blanket, and basic facilities. The bathrooms are simple (bucket showers, squat toilets). The hospitality is genuine.",
    'travel_tips' => "Pack light — you walk across a river to reach the village. Bring a torch or headlamp — no street lighting. Take cash — no ATMs or card machines. No WiFi and limited phone signal — this is the point.",
    'nearby_travel_times' => "Loolwatte trailhead: 20 min walk. Kandy: 2.5 hrs. Knuckles: 1 hr. Colombo: 5 hrs.",
    'works_well_with' => "Knuckles Mountain Range, Kandy, Hunnasgiriya",
    'cta_text' => "We can arrange your Meemure homestay including the host family, meals, and a guide for the waterfall hikes.",
    'faq_json' => '[{"q":"Do I need a guide to visit Meemure?","a":"Not mandatory but helpful. A guide can arrange the homestay and transport."},{"q":"What facilities are available?","a":"Basic homestays with bucket showers and squat toilets. No WiFi, no shops. That\'s the point."},{"q":"Is Meemure suitable for children?","a":"Older children who can handle basic accommodation and walking will love it."}]',
    'destination_type' => 'Adventure',
    'region_slug' => 'hill',
    'region_label' => 'Hill Country',
    'emoji' => '🏡',
    'recommended_stay' => '1-2 nights',
    'meta_title' => 'Meemure Village Experience | Traditional Sri Lanka Homestay | Island Culture Travels',
    'meta_description' => 'Experience Meemure — a remote traditional village in the Knuckles foothills. Authentic homestay, river hikes, rice terraces, and stargazing. Private transfers from Kandy.',
];

// Common fields for all
$defaults = [
    'is_published' => 1,
    'is_featured' => 0,
    'sort_order' => 99,
    'province' => '',
    'temperature' => '',
];

// Insert each destination
$cols = ['slug', 'name', 'excerpt', 'intro', 'why_visit', 'things_to_do', 'best_time', 'how_to_get_there',
    'suggested_itinerary', 'where_to_stay', 'travel_tips', 'nearby_travel_times', 'works_well_with',
    'cta_text', 'faq_json', 'is_published', 'is_featured', 'sort_order',
    'destination_type', 'region_slug', 'region_label', 'emoji', 'recommended_stay',
    'meta_title', 'meta_description'];

foreach ($data as $slug => $row) {
    // Check if already exists
    $existing = fetch_one("SELECT id FROM destinations WHERE slug = :slug", ['slug' => $slug]);
    if ($existing) {
        echo "SKIP {$slug} — already exists\n";
        ++$skipped;
        continue;
    }

    try {
        $row['slug'] = $slug;
        $placeholders = [];
        $params = [];
        $allCols = array_unique(array_merge($cols, array_keys($row)));

        foreach ($allCols as $col) {
            if (array_key_exists($col, $row)) {
                $placeholders[] = "`{$col}` = :{$col}";
                $params[$col] = $row[$col];
            } elseif (array_key_exists($col, $defaults)) {
                $placeholders[] = "`{$col}` = :{$col}";
                $params[$col] = $defaults[$col];
            }
        }

        $setClause = implode(', ', $placeholders);
        $sql = "INSERT INTO destinations SET {$setClause}";
        execute_query($sql, $params);
        echo "OK   {$slug}\n";
        ++$inserted;
    } catch (\Throwable $e) {
        echo "FAIL {$slug}: {$e->getMessage()}\n";
        ++$errors;
    }
}

echo "\nDone. Inserted: {$inserted}, Skipped: {$skipped}, Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
