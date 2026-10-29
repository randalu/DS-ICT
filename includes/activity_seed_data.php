<?php
declare(strict_types=1);

/**
 * Initial Sri Lanka activity content. Seed records remain drafts until an editor
 * confirms the examples, fills the Image Manager slots and publishes them.
 */
function activity_seed_data(): array
{
    $tourism = 'https://srilanka.travel/';
    $examples = static fn(array $rows): array => $rows;
    return [
        [
            'name' => 'Activities in Sri Lanka', 'slug' => 'activities', 'is_hub' => 1, 'sort_order' => 0,
            'excerpt' => 'Explore Sri Lanka through its landscapes, wildlife, heritage, food and outdoor activities. Browse concise activity highlights and the destinations where guests can find them.',
            'meta_title' => 'Activities in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Browse Sri Lanka activity highlights and destinations, from wildlife safaris and hikes to heritage, food and coastal adventures.',
            'sources' => [$tourism . 'adventure_and_sports', $tourism . 'heritage-discover-the-past'],
            'examples' => [],
        ],
        [
            'name' => 'Arts and Crafts', 'slug' => 'arts-and-crafts', 'sort_order' => 10,
            'excerpt' => 'Meet Sri Lanka’s living craft traditions through batik, mask making, handloom, wood carving and locally made keepsakes. Workshops and shops vary by provider and date.',
            'meta_title' => 'Arts and Crafts in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Discover batik, traditional masks and local crafts in Matale, Kandy, Ambalangoda and Battaramulla.',
            'sources' => [$tourism . 'essence?article=62', $tourism . 'tourist-shops', $tourism . 'attraction?attraction_id=211', 'https://saranalanka.com/faq'],
            'examples' => $examples([
                ['title'=>'Batik craft and workshop visits','destinations'=>['Matale','Kandy'],'sources'=>[$tourism.'tourist-shops',$tourism.'essence?article=62']],
                ['title'=>'Traditional mask carving and painting','destinations'=>['Ambalangoda'],'sources'=>[$tourism.'tourist-shops',$tourism.'essence?article=62']],
                ['title'=>'Handloom and village craft demonstrations','destinations'=>['Battaramulla'],'sources'=>[$tourism.'attraction?attraction_id=211','https://saranalanka.com/faq']],
                ['title'=>'Wood carving, lacquer and handmade keepsakes','destinations'=>['Kandy','Matale'],'sources'=>[$tourism.'tourist-shops',$tourism.'essence?article=62']],
            ]),
        ],
        [
            'name' => 'Camping and Hiking', 'slug' => 'camping-and-hiking', 'sort_order' => 20,
            'excerpt' => 'Walk cloud-forest paths, mountain trails and rainforest routes, or camp at designated sites. Park access, permits, weather and campsite availability should be checked for each trip.',
            'meta_title' => 'Camping and Hiking in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Find hiking and designated camping destinations in Horton Plains, Knuckles, Sinharaja and Kumana.',
            'sources' => ['https://tourism.cp.gov.lk/en/hiking-n-trekking',$tourism.'attraction?attraction_id=158',$tourism.'attraction?attraction_id=112','https://www.dwc.gov.lk/'],
            'examples' => $examples([
                ['title'=>'World’s End circular trail','destinations'=>['Horton Plains','Nuwara Eliya'],'sources'=>[$tourism.'attraction?attraction_id=158']],
                ['title'=>'Knuckles mountain and forest trails','destinations'=>['Knuckles Range','Matale','Kandy'],'sources'=>['https://tourism.cp.gov.lk/en/hiking-n-trekking','https://www.dwc.gov.lk/']],
                ['title'=>'Riverstone ridge and highland hikes','destinations'=>['Riverstone','Matale'],'sources'=>['https://tourism.cp.gov.lk/en/hiking-n-trekking']],
                ['title'=>'Designated wildlife-park camping','destinations'=>['Kumana National Park','Yala National Park'],'sources'=>[$tourism.'attraction?attraction_id=112','https://www.dwc.gov.lk/']],
            ]),
        ],
        [
            'name' => 'Boat Safari', 'slug' => 'boat-safari', 'sort_order' => 30,
            'excerpt' => 'Travel by boat through mangroves, inland lakes and wildlife-rich reservoirs. Routes, departure points and operating conditions depend on local operators and weather.',
            'meta_title' => 'Boat Safaris in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore Sri Lanka boat safaris around Madu Ganga, Koggala Lake, Gal Oya and Pigeon Island.',
            'sources' => ['https://www.dwc.gov.lk/?page_id=817',$tourism.'documents/tender2024/DMC_SOR_IFB_PRICE_SCHEDULE_2024%20_Final_Bid_Document.pdf','https://gal.oya.national.park.sri.lanka.com/safari'],
            'examples' => $examples([
                ['title'=>'Mangrove and island boat safari','destinations'=>['Madu Ganga','Balapitiya'],'sources'=>[$tourism.'documents/tender2024/DMC_SOR_IFB_PRICE_SCHEDULE_2024%20_Final_Bid_Document.pdf']],
                ['title'=>'Lake and island boat trips','destinations'=>['Koggala Lake','Galle'],'sources'=>['https://assets.ctfassets.net/fm7jt4jjkxd0/1rqp1z0DDFZ1DfXbDX6g6o/29aca9f4782f261a1831a458ed11b969/Activity-book-Final-New.compressed-_1_.pdf']],
                ['title'=>'Wildlife boat safari on the reservoir','destinations'=>['Gal Oya National Park','Senanayake Samudra','Ampara'],'sources'=>['https://gal.oya.national.park.sri.lanka.com/safari']],
                ['title'=>'Boat access for marine and reef visits','destinations'=>['Pigeon Island','Nilaveli','Trincomalee'],'sources'=>[$tourism.'documents/tender2024/DMC_SOR_IFB_PRICE_SCHEDULE_2024%20_Final_Bid_Document.pdf','https://www.dwc.gov.lk/?page_id=817']],
            ]),
        ],
        [
            'name' => 'Paragliding', 'slug' => 'paragliding', 'sort_order' => 40,
            'excerpt' => 'Sri Lanka has supervised tandem and site-flying options in selected highland and inland locations. Launch sites can change with wind, approvals and operator schedules, so confirm the current location before travel.',
            'meta_title' => 'Paragliding in Sri Lanka | Island Culture Travels',
            'meta_description' => 'See current paragliding options around Kandy, Ambuluwawa, 18 Bends and Kurunegala, subject to site and weather approval.',
            'sources' => ['https://csfa.lk/','https://www.sltda.gov.lk/storage/common_media/1%20Guideline%20for%20Paragliding.pdf','https://www.rapidadventures.lk/experiences/paragliding-in-ella'],
            'examples' => $examples([
                ['title'=>'Tandem discovery flights','destinations'=>['Kandy area'],'sources'=>['https://csfa.lk/']],
                ['title'=>'Highland site flying and pilot briefings','destinations'=>['Ambuluwawa','Gampola','Kandy'],'sources'=>['https://csfa.lk/','https://www.sltda.gov.lk/storage/common_media/1%20Guideline%20for%20Paragliding.pdf']],
                ['title'=>'Seasonal tandem flights at an approved site','destinations'=>['Kurunegala'],'sources'=>['https://www.rapidadventures.lk/experiences/paragliding-in-ella','https://www.sltda.gov.lk/storage/common_media/1%20Guideline%20for%20Paragliding.pdf']],
            ]),
        ],
        [
            'name' => 'Gemstones', 'slug' => 'gemstones', 'sort_order' => 50,
            'excerpt' => 'Learn about Sri Lanka’s gem heritage through registered gem and jewellery shops, trade centres and guided industry visits. Mining and trading access is controlled; visits and purchases need independent confirmation.',
            'meta_title' => 'Gemstone Experiences in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore Sri Lanka’s gem heritage in Ratnapura and find authorized gem and jewellery shops in Colombo, Kandy and Negombo.',
            'sources' => [$tourism.'index.php?destination=21&route=attractions%2Fdestination',$tourism.'tourist-shops','https://ngja.gov.lk/gem-tower-ratnapura/','https://rathnadeepa.ngja.gov.lk/'],
            'examples' => $examples([
                ['title'=>'Gem heritage and town visits','destinations'=>['Ratnapura','Pelmadulla'],'sources'=>[$tourism.'index.php?destination=21&route=attractions%2Fdestination']],
                ['title'=>'Gem trade centre and industry orientation','destinations'=>['Gem Tower','Ratnapura'],'sources'=>['https://ngja.gov.lk/gem-tower-ratnapura/']],
                ['title'=>'Authorized gem and jewellery shop visits','destinations'=>['Colombo','Kandy','Negombo'],'sources'=>[$tourism.'tourist-shops']],
                ['title'=>'Gem identification and certification services','destinations'=>['Ratnapura','Colombo'],'sources'=>['https://ngja.gov.lk/gem-tower-ratnapura/','https://rathnadeepa.ngja.gov.lk/']],
            ]),
        ],
        [
            'name' => 'Night Life', 'slug' => 'night-life', 'sort_order' => 60,
            'excerpt' => 'Sri Lanka’s evening scene ranges from live music, cocktail lounges and late-opening city venues to coastal hotel bars. Opening days and entertainment schedules change seasonally.',
            'meta_title' => 'Nightlife in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore evening venues and nightlife areas in Colombo, Negombo and Sri Lanka’s southern coastal towns.',
            'sources' => [$tourism.'night-life','https://www.srilanka.travel/safe-and-secure'],
            'examples' => $examples([
                ['title'=>'Cocktail lounges and rooftop bars','destinations'=>['Colombo'],'sources'=>[$tourism.'night-life']],
                ['title'=>'Live music and late-night venues','destinations'=>['Colombo'],'sources'=>[$tourism.'night-life']],
                ['title'=>'Beachfront evening venues','destinations'=>['Negombo','Bentota'],'sources'=>[$tourism.'night-life']],
                ['title'=>'Coastal bars and hotel entertainment','destinations'=>['Galle','Unawatuna','Mirissa'],'sources'=>[$tourism.'night-life']],
            ]),
        ],
        [
            'name' => 'Jeep Safari', 'slug' => 'jeep-safari', 'sort_order' => 70,
            'excerpt' => 'Guided four-wheel-drive safaris explore national parks and protected wildlife areas. Park access, safari windows and wildlife sightings vary; use authorized operators and follow park rules.',
            'meta_title' => 'Jeep Safaris in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Compare Sri Lanka wildlife jeep safari destinations including Yala, Wilpattu, Udawalawe, Minneriya and Kaudulla.',
            'sources' => ['https://www.dwc.gov.lk/?page_id=289','https://www.srilanka.travel/situation_update/resources/pdfs/Status%20Report%20-%20Sri%20Lanka%20Tourism%20-%2009-12-2025%20%281900HRS%29.pdf'],
            'examples' => $examples([
                ['title'=>'Leopard and dry-zone wildlife drives','destinations'=>['Yala National Park'],'sources'=>['https://www.dwc.gov.lk/?page_id=289']],
                ['title'=>'Forest and lake wildlife drives','destinations'=>['Wilpattu National Park'],'sources'=>['https://www.dwc.gov.lk/?page_id=289']],
                ['title'=>'Elephant and open-country wildlife safari','destinations'=>['Udawalawe National Park'],'sources'=>['https://www.dwc.gov.lk/?page_id=289']],
                ['title'=>'Seasonal elephant gathering safari','destinations'=>['Minneriya National Park','Kaudulla National Park'],'sources'=>['https://www.dwc.gov.lk/?page_id=289']],
            ]),
        ],
        [
            'name' => 'Culture and History', 'slug' => 'culture-and-history', 'sort_order' => 80,
            'excerpt' => 'Explore ancient capitals, sacred places, cave temples and living cultural traditions that tell the story of Sri Lanka across many centuries.',
            'meta_title' => 'Culture and History in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Plan culture and history visits to Anuradhapura, Polonnaruwa, Sigiriya, Dambulla and Kandy.',
            'sources' => [$tourism.'heritage-discover-the-past',$tourism.'cultural_heritage',$tourism.'pilgrimage'],
            'examples' => $examples([
                ['title'=>'Ancient city and sacred-site visits','destinations'=>['Anuradhapura'],'sources'=>[$tourism.'heritage-discover-the-past']],
                ['title'=>'Royal city ruins and archaeological sites','destinations'=>['Polonnaruwa'],'sources'=>[$tourism.'heritage-discover-the-past']],
                ['title'=>'Rock fortress and cave-temple visits','destinations'=>['Sigiriya','Dambulla'],'sources'=>[$tourism.'heritage-discover-the-past',$tourism.'attraction?attraction_id=157']],
                ['title'=>'Sacred city and Kandyan heritage visits','destinations'=>['Kandy'],'sources'=>[$tourism.'cultural_heritage',$tourism.'pilgrimage']],
            ]),
        ],
        [
            'name' => 'Sports and Adventure', 'slug' => 'sports-and-adventure', 'sort_order' => 90,
            'excerpt' => 'From river rapids to surf breaks and underwater reefs, Sri Lanka offers outdoor and water-based activities across its rivers, coastlines and highlands. Season and conditions matter.',
            'meta_title' => 'Sports and Adventure in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Find rafting, surfing, diving and snorkelling destinations across Sri Lanka’s rivers and coast.',
            'sources' => [$tourism.'adventure_and_sports','https://www.sltda.gov.lk/storage/common_media/AdventureTourismActivitiesWaterBased%26AirBasedDecsriptions.pdf'],
            'examples' => $examples([
                ['title'=>'White-water rafting','destinations'=>['Kitulgala','Kelani River'],'sources'=>[$tourism.'adventure_and_sports']],
                ['title'=>'Surfing','destinations'=>['Weligama','Arugam Bay'],'sources'=>[$tourism.'adventure_and_sports']],
                ['title'=>'Scuba diving and wreck diving','destinations'=>['Hikkaduwa','Trincomalee'],'sources'=>[$tourism.'adventure_and_sports']],
                ['title'=>'Snorkelling and coastal water sports','destinations'=>['Hikkaduwa','Unawatuna','Nilaveli'],'sources'=>[$tourism.'adventure_and_sports']],
            ]),
        ],
        [
            'name' => 'Spa and Ayurveda', 'slug' => 'spa-and-ayurveda', 'sort_order' => 100,
            'excerpt' => 'Registered wellness centres offer spa treatments and Ayurveda-inspired care in city, hill-country and coastal settings. Choose a provider by its current registration, services and qualified practitioners.',
            'meta_title' => 'Spa and Ayurveda in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Discover listed spa and wellness centres in Colombo, Kandy and coastal destinations including Bentota and Beruwala.',
            'sources' => [$tourism.'tourist-attractions/spa-and-wellness-centers',$tourism.'spa-and-wellness-centers'],
            'examples' => $examples([
                ['title'=>'Ayurveda and wellness treatments','destinations'=>['Bentota','Beruwala'],'sources'=>[$tourism.'tourist-attractions/spa-and-wellness-centers']],
                ['title'=>'Urban spa and wellness centres','destinations'=>['Colombo'],'sources'=>[$tourism.'spa-and-wellness-centers']],
                ['title'=>'Hill-country wellness centres','destinations'=>['Kandy'],'sources'=>[$tourism.'spa-and-wellness-centers']],
                ['title'=>'Coastal wellness stays and treatments','destinations'=>['Bentota','Beruwala','Galle'],'sources'=>[$tourism.'tourist-attractions/spa-and-wellness-centers']],
            ]),
        ],
        [
            'name' => 'Food and Drink', 'slug' => 'food-and-drink', 'sort_order' => 110,
            'excerpt' => 'Taste Sri Lanka through market snacks, home-style cooking, Ceylon tea and coastal seafood. Menus and cooking experiences differ by town, host and season.',
            'meta_title' => 'Food and Drink Experiences in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore Sri Lankan street food, cooking experiences, tea tasting and coastal seafood in Colombo and beyond.',
            'sources' => [$tourism.'culinary-tourism/','https://www.sltda.gov.lk/storage/common_media/Food_drink_Tourism_Toolkit.pdf'],
            'examples' => $examples([
                ['title'=>'Street-food and market tasting','destinations'=>['Colombo'],'sources'=>[$tourism.'culinary-tourism/']],
                ['title'=>'Sri Lankan home-style cooking experiences','destinations'=>['Sigiriya','Galle'],'sources'=>[$tourism.'culinary-tourism/','https://www.sltda.gov.lk/storage/common_media/Food_drink_Tourism_Toolkit.pdf']],
                ['title'=>'Ceylon tea tasting and estate visits','destinations'=>['Nuwara Eliya','Ella'],'sources'=>[$tourism.'culinary-tourism/','https://www.sltda.gov.lk/storage/common_media/Food_drink_Tourism_Toolkit.pdf']],
                ['title'=>'Seafood meals and coastal food stops','destinations'=>['Negombo','Galle','Mirissa'],'sources'=>[$tourism.'culinary-tourism/']],
            ]),
        ],
        [
            'name' => 'Bird Watching', 'slug' => 'bird-watching', 'sort_order' => 120,
            'excerpt' => 'Rainforest, cloud forest, lagoons and wetlands create very different birdwatching habitats. Guided walks and park rules help visitors watch birds with care.',
            'meta_title' => 'Bird Watching in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore birdwatching in Sinharaja, Kumana, Bundala, Horton Plains and Udawattekele.',
            'sources' => [$tourism.'attraction?attraction_id=151',$tourism.'attraction?attraction_id=166',$tourism.'attraction?attraction_id=168','https://tourism.cp.gov.lk/en/bird-watching-in-sri-lanka',$tourism.'untamed-sri-lanka'],
            'examples' => $examples([
                ['title'=>'Rainforest birding and endemic species','destinations'=>['Sinharaja Forest Reserve'],'sources'=>[$tourism.'attraction?attraction_id=151',$tourism.'untamed-sri-lanka']],
                ['title'=>'Lagoon and wetland birdwatching','destinations'=>['Kumana National Park'],'sources'=>[$tourism.'attraction?attraction_id=166']],
                ['title'=>'Migratory birdwatching in coastal wetlands','destinations'=>['Bundala National Park'],'sources'=>[$tourism.'attraction?attraction_id=168']],
                ['title'=>'Forest birding and sanctuary walks','destinations'=>['Udawattekele Sanctuary','Kandy'],'sources'=>['https://tourism.cp.gov.lk/en/bird-watching-in-sri-lanka']],
            ]),
        ],
        [
            'name' => 'Architectural Tours', 'slug' => 'architectural-tours', 'sort_order' => 130,
            'excerpt' => 'Trace Sri Lanka’s built heritage from fortified colonial streets and historic homes to ancient city planning and Geoffrey Bawa’s modern tropical architecture.',
            'meta_title' => 'Architectural Tours in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore architecture in Galle Fort, Lunuganga, Brief Garden, Anuradhapura and Polonnaruwa.',
            'sources' => ['https://ccf.gov.lk/heritage-sites/galle-fort/','https://www.aman.com/sites/default/files/2024-10/Amangalla_Experience_Guide.pdf',$tourism.'heritage-discover-the-past'],
            'examples' => $examples([
                ['title'=>'Fortifications and colonial-era buildings','destinations'=>['Galle Fort'],'sources'=>['https://ccf.gov.lk/heritage-sites/galle-fort/']],
                ['title'=>'Geoffrey Bawa landscape and garden architecture','destinations'=>['Lunuganga','Bentota','Brief Garden','Aluthgama'],'sources'=>['https://www.aman.com/sites/default/files/2024-10/Amangalla_Experience_Guide.pdf']],
                ['title'=>'Ancient urban planning and sacred architecture','destinations'=>['Anuradhapura'],'sources'=>[$tourism.'heritage-discover-the-past']],
                ['title'=>'Ancient city architecture and stonework','destinations'=>['Polonnaruwa'],'sources'=>[$tourism.'heritage-discover-the-past']],
            ]),
        ],
        [
            'name' => 'Hot Air Ballooning', 'slug' => 'hot-air-ballooning', 'sort_order' => 140,
            'excerpt' => 'Early-morning hot-air balloon flights operate in the Dambulla–Kandalama area, with views toward the Cultural Triangle depending on wind and the day’s flight path. This is one regional activity rather than separate destination products.',
            'meta_title' => 'Hot Air Ballooning in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Learn about sunrise hot-air balloon flights around Dambulla and Kandalama, with weather-dependent Cultural Triangle views.',
            'sources' => [$tourism.'hot-air-ballooning','https://www.srilankaballooning.com/','https://www.srilankaballoon.com/'],
            'examples' => $examples([
                ['title'=>'Sunrise hot-air balloon flight (route varies with wind)','destinations'=>['Dambulla','Kandalama','Sigiriya','Habarana'],'sources'=>[$tourism.'hot-air-ballooning','https://www.srilankaballooning.com/']],
            ]),
        ],
        [
            'name' => 'Cycling Tours', 'slug' => 'cycling-tours', 'sort_order' => 150,
            'excerpt' => 'Cycling routes cross south-coast backroads, village landscapes, dry-zone countryside and hill-country tea estates. Route length and difficulty depend on the operator and selected ride.',
            'meta_title' => 'Cycling Tours in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Discover Sri Lanka cycling routes around Galle, Koggala, Mirissa, Udawalawe, Matara and the hill country.',
            'sources' => ['https://srilankacyclingtours.lk/destinations','https://srilankacyclingtours.lk/destinations/south-coast','https://assets.ctfassets.net/fm7jt4jjkxd0/1rqp1z0DDFZ1DfXbDX6g6o/29aca9f4782f261a1831a458ed11b969/Activity-book-Final-New.compressed-_1_.pdf'],
            'examples' => $examples([
                ['title'=>'South-coast cycling through towns and backroads','destinations'=>['Galle','Koggala','Mirissa'],'sources'=>['https://srilankacyclingtours.lk/destinations/south-coast']],
                ['title'=>'Lagoon, cinnamon and coastal countryside ride','destinations'=>['Koggala Lake','Galle'],'sources'=>['https://srilankacyclingtours.lk/destinations/south-coast']],
                ['title'=>'Dry-zone and southern backroad cycling','destinations'=>['Udawalawe','Kalametiya','Matara'],'sources'=>['https://srilankacyclingtours.lk/destinations/south-coast']],
                ['title'=>'Cultural Triangle village and heritage cycling','destinations'=>['Sigiriya','Polonnaruwa'],'sources'=>['https://srilankacyclingtours.lk/destinations','https://assets.ctfassets.net/fm7jt4jjkxd0/1rqp1z0DDFZ1DfXbDX6g6o/29aca9f4782f261a1831a458ed11b969/Activity-book-Final-New.compressed-_1_.pdf']],
            ]),
        ],
        [
            'name' => 'City Tours', 'slug' => 'city-tours', 'sort_order' => 160,
            'excerpt' => 'Guided city visits connect historic quarters, sacred landmarks, markets and everyday urban life. Licensed guides can tailor the pace and focus to each city.',
            'meta_title' => 'City Tours in Sri Lanka | Island Culture Travels',
            'meta_description' => 'Explore Colombo, Kandy, Galle and Jaffna through guided Sri Lanka city tours and heritage walks.',
            'sources' => [$tourism.'colombo-city-tour',$tourism.'heritage-discover-the-past','https://www.srilanka.travel/tourist-guides'],
            'examples' => $examples([
                ['title'=>'Open-deck and heritage city tour','destinations'=>['Colombo'],'sources'=>[$tourism.'colombo-city-tour']],
                ['title'=>'Sacred and cultural city tour','destinations'=>['Kandy'],'sources'=>[$tourism.'heritage-discover-the-past','https://www.srilanka.travel/tourist-guides']],
                ['title'=>'Historic town walk inside the fort','destinations'=>['Galle Fort','Galle'],'sources'=>[$tourism.'heritage-discover-the-past','https://www.srilanka.travel/tourist-guides']],
                ['title'=>'Northern city and heritage visit','destinations'=>['Jaffna'],'sources'=>['https://www.srilanka.travel/tourist-guides']],
            ]),
        ],
    ];
}
