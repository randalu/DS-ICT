SET NAMES utf8mb4;

-- Static pages
INSERT INTO static_pages (title, slug, content, meta_title, meta_description, canonical_url, sort_order, is_published)
VALUES
('About Us', 'about-us',
'Island Culture Travels creates private Sri Lanka journeys for couples, families, and small groups. We focus on flexible itineraries, comfortable vehicles, and practical travel planning across the island.',
'About Island Culture Travels — Private Chauffeur-Guided Tours',
'A professional private chauffeur-guided travel service offering safe vehicles, local expertise, and flexible itineraries across Sri Lanka.',
NULL,
1, 1),
('Why Choose Us', 'why-choose-us',
'Island Culture Travels focuses on private Sri Lanka journeys designed around real traveler needs rather than generic package tourism.

Why travelers choose us:

• Private chauffeur and comfortable vehicle
• Flexible route planning based on your dates and interests
• Comfortable vehicles for couples, families, and small groups
• Driver-guide support with local knowledge
• Family-friendly pacing and practical itinerary planning
• Easy communication before and during the trip
• Customized travel support across Sri Lanka',
'Why Choose Island Culture Travels for Private Sri Lanka Tours',
'Discover why travelers choose Island Culture Travels — private vehicles, professional chauffeurs, flexible itineraries, and reliable service across Sri Lanka.',
NULL,
2, 1),
('FAQ', 'faq',
'Find answers to common questions about private tours, itinerary planning, family travel, airport pickup, and safari routes in Sri Lanka.',
'FAQ — Private Sri Lanka Tours with Chauffeur and Vehicle',
'Frequently asked questions about private Sri Lanka tours, chauffeur services, transport options, and itinerary planning.',
NULL,
3, 1);

-- Tours
INSERT INTO tours
(name, slug, excerpt, intro, highlights, sample_itinerary, pricing_notes, vehicle_options, faq_json, meta_title, meta_description, canonical_url, is_featured, sort_order, is_published)
VALUES
(
'Private Sri Lanka Tour',
'private-sri-lanka-tour',
'Flexible private Sri Lanka tours with comfortable vehicles, driver-guides, and custom planning for couples and families.',
'Private Sri Lanka Tour is ideal for travelers who want to explore the island at their own pace with a private vehicle, local support, and a route tailored to their interests.',
'• Private vehicle with driver-guide
• Custom route planning
• Family-friendly travel pacing
• Flexible start and end points',
'Day 1 arrival, Day 2–N cultural sites, scenic travel, beach or safari extensions depending on the route.',
'Pricing depends on group size, vehicle type, hotel category, and travel season.',
'• Sedan
• Hybrid car
• KDH van
• Hiace van',
'[{"question":"Can the route be customized?","answer":"Yes. Every private tour can be adjusted to your dates, interests, and travel style."}]',
'Hire a Private Driver in Sri Lanka with Comfortable Vehicle Options | Island Culture Travels',
'Travel anywhere in Sri Lanka with a professional chauffeur, modern vehicle, and flexible itinerary designed around your travel plans.',
NULL,
1, 1, 1
),
(
'Sri Lanka Family Tour',
'sri-lanka-family-tour',
'Family-friendly Sri Lanka tours with comfortable transport, sensible pacing, and private travel support.',
'Sri Lanka Family Tour is designed for parents, children, and multigenerational travelers who want a smoother private travel experience across the island.',
'• Family-friendly pacing
• Comfortable vehicle options
• Flexible sightseeing
• Child-friendly route planning',
'Day 1 arrival, followed by a balanced mix of culture, scenery, wildlife, and relaxing overnight stops.',
'Pricing depends on family size, hotel level, and travel season.',
'• Sedan for small families
• KDH van
• Hiace van',
'[{"question":"Are family tours customizable?","answer":"Yes. We adjust the route, pace, and stop choices based on your family''s needs."}]',
'Private Family Tour in Sri Lanka with Chauffeur and Comfortable Vehicle | Island Culture Travels',
'Travel safely with your family using spacious vehicles, flexible schedules, and a trusted private driver throughout your Sri Lanka holiday.',
NULL,
1, 2, 1
),
(
'Sri Lanka Wildlife Safari Tour',
'sri-lanka-wildlife-safari-tour',
'Explore Sri Lanka''s top wildlife parks with private transport, safari planning, and flexible travel.',
'Sri Lanka Wildlife Safari Tour is designed for travelers who want to experience the island''s leading safari regions with the comfort of private transport and a well-paced route.',
'• Leopard and elephant safari regions
• Private vehicle with driver-guide
• Flexible multi-park planning
• Family and couple friendly',
'Day 1 arrival and transfer, followed by safari-focused travel through one or more of Sri Lanka''s top wildlife destinations.',
'Pricing depends on parks included, jeep charges, hotel category, and group size.',
'• Sedan
• Hybrid car
• KDH van
• Hiace van',
'[{"question":"Which parks can be included?","answer":"This tour can include Yala, Udawalawe, Wilpattu, Minneriya, and other wildlife regions."}]',
'Private Wildlife Safari Tour in Sri Lanka with Experienced Driver Guide | Island Culture Travels',
'Explore Sri Lanka''s national parks with safe private transport, early-morning departures, and knowledgeable local guidance.',
NULL,
1, 3, 1
);

-- Destinations
INSERT INTO destinations
(name, slug, excerpt, intro, why_visit, things_to_do, best_time, how_to_get_there, suggested_itinerary, where_to_stay, travel_tips, faq_json, cta_title, cta_text, province, temperature, destination_type, recommended_stay, cover_image, hero_image, og_image, meta_title, meta_description, canonical_url, is_featured, sort_order, is_published)
VALUES
(
'Colombo',
'colombo',
'Sri Lanka''s vibrant capital — the perfect gateway for city culture, dining, colonial architecture, and a coastal waterfront.',
'Colombo is the main arrival point for most Sri Lanka trips and a destination worth exploring in its own right. The city offers a mix of colonial architecture, modern dining, local markets, and a lively waterfront.',
'Travelers visit Colombo for city sightseeing, street food, shopping, colonial buildings, temples, and practical travel convenience before heading inland.',
'City tours, food experiences, shopping malls, colonial buildings, temples, Galle Face Green waterfront, Pettah market.',
'Colombo can be visited year-round and works well as a flexible city stop before or after longer routes.',
'Colombo is easily reached from Bandaranaike International Airport and connects smoothly with the rest of the island by road.',
'Use Colombo as a short stop before heading inland or along the southern coast.',
'City hotels range from budget stays to premium business and leisure properties.',
'Do not overload Colombo into a long route unless you specifically want city exploration. One to two days is usually enough.',
'[{"question":"What is Colombo best known for?","answer":"Colombo is known for city experiences, food, shopping, and travel convenience as the island''s main gateway."}]',
'Plan Your Colombo Stay',
'We''ll help you include Colombo in a private route that fits your timing and travel style.',
'Western Province',
'27–31°C',
'City & Culture',
'1–2 Days',
NULL,
NULL,
NULL,
'Visit Colombo with a Private Driver and Comfortable Vehicle | Island Culture Travels',
'Explore Colombo with a professional chauffeur guide, private city travel, and flexible Sri Lanka itinerary support.',
NULL,
1, 1, 1
),
(
'Kandy',
'kandy',
'Sri Lanka''s cultural heart — home to the sacred Temple of the Tooth, lush hills, traditional dance, and a serene lake at its centre.',
'Kandy is one of Sri Lanka''s most important cultural and religious destinations. Sitting in the heart of the hill country, it offers a blend of Buddhist heritage, colonial history, and scenic highland surroundings.',
'Kandy is ideal for travelers who want to experience Sri Lanka''s cultural depth — with the Temple of the Tooth, traditional Kandyan dance, botanical gardens, and a relaxed lakeside atmosphere.',
'Temple of the Tooth Relic, Kandy Lake, Royal Botanical Gardens, traditional Kandyan dance performances, Kandy Market, tea country day trips.',
'Kandy can be visited year-round. The Esala Perahera festival in July–August is one of the most spectacular events in Asia.',
'Kandy is typically reached by private vehicle or train from Colombo, Sigiriya, Nuwara Eliya, or Ella.',
'Combine Kandy with Sigiriya, Dambulla, Nuwara Eliya, or Ella for a strong multi-destination route.',
'Kandy has a wide range of accommodation from guesthouses to boutique hotels overlooking the lake.',
'Book temple visits early during peak season. Traffic in the city can be slow — allow extra time for connections.',
'[{"question":"What is Kandy best known for?","answer":"Kandy is best known for the Temple of the Tooth Relic, Sri Lanka''s most sacred Buddhist site, and its beautiful hilltop setting."}]',
'Plan Your Kandy Visit',
'We''ll help you add Kandy to a private Sri Lanka route with the right timing and transport.',
'Central Province',
'24–28°C',
'Culture & Heritage',
'1–2 Days',
'kandy-temple-lk.jpg',
'kandy-temple-lk.jpg',
NULL,
'Travel to Kandy with a Private Driver — Cultural Heart of Sri Lanka | Island Culture Travels',
'Explore Kandy with a professional chauffeur guide, flexible travel planning, and private transport tailored to your Sri Lanka journey.',
NULL,
1, 2, 1
),
(
'Ella',
'ella',
'A scenic hill-country retreat famous for the Nine Arch Bridge, Ella Rock hike, tea plantations, and one of the world''s great train rides.',
'Ella is one of Sri Lanka''s most popular hill-country destinations, loved for its scenic beauty, cool climate, and relaxed pace. It sits at around 1,000 metres and is surrounded by tea estates and misty ridgelines.',
'Ella is ideal for travelers looking for cooler weather, viewpoints, tea-country scenery, hiking, and a slower pace away from the busier coastal areas.',
'Nine Arch Bridge, Ella Rock hike, Little Adam''s Peak, tea estate walks, train journey from Kandy, waterfalls, scenic drives.',
'Ella can be visited in most seasons. The hill-country train route from Kandy through Nuwara Eliya to Ella is best in the morning light.',
'Ella is typically reached by private vehicle from Kandy, Nuwara Eliya, Yala, or by train from Kandy or Colombo.',
'Combine Ella with Nuwara Eliya, Yala, Udawalawe, or Belihuloya for a scenic private journey.',
'Ella has a wide range of guesthouses, boutique hotels, and scenic hill-country stays at various price points.',
'Plan driving times carefully if combining Ella with safari destinations in a short trip. The roads can be slow.',
'[{"question":"What is Ella best known for?","answer":"Ella is best known for the Nine Arch Bridge, scenic hill-country views, hiking, and the legendary Kandy-to-Ella train route."}]',
'Plan Your Ella Trip',
'We''ll help you include Ella in a smooth private Sri Lanka route.',
'Uva Province — Hill Country',
'18–26°C',
'Scenery & Adventure',
'2–3 Days',
'nine-arch-bridge-lk.jpg',
'nine-arch-bridge-lk.jpg',
NULL,
'Travel to Ella with a Private Driver and Scenic Mountain Routes | Island Culture Travels',
'Experience the beauty of Ella with reliable private transport, flexible travel timing, and expert local guidance throughout your journey.',
NULL,
1, 3, 1
),
(
'Sigiriya',
'sigiriya',
'The legendary Lion Rock — a UNESCO World Heritage Site rising 200 metres above the jungle, with ancient frescoes and palace ruins at its summit.',
'Sigiriya is Sri Lanka''s most iconic landmark and one of the most impressive archaeological sites in Asia. The ancient rock fortress rises dramatically from the surrounding plains and offers sweeping views of the Cultural Triangle.',
'Sigiriya is a must-visit for history lovers, photographers, and anyone seeking a dramatic Sri Lanka experience unlike anywhere else.',
'Sigiriya Rock climb, ancient frescoes, water gardens, mirror wall, Pidurangala Rock viewpoint, Dambulla Cave Temple nearby.',
'January to September is the best time to visit Sigiriya, with drier conditions for climbing.',
'Sigiriya is best reached by private vehicle from Colombo, Kandy, or Dambulla. It sits in the heart of the Cultural Triangle.',
'Combine Sigiriya with Dambulla, Polonnaruwa, Anuradhapura, or Kandy for a Cultural Triangle route.',
'Sigiriya has jungle lodges, boutique properties, and comfortable mid-range hotels near the rock.',
'Start the rock climb early in the morning to avoid heat and crowds. Allow at least 2–3 hours for the full visit.',
'[{"question":"What is Sigiriya best known for?","answer":"Sigiriya is famous as the Lion Rock — a 5th-century rock fortress and UNESCO World Heritage Site with ancient palace ruins and panoramic views."}]',
'Plan Your Sigiriya Visit',
'We''ll help you include Sigiriya in a Cultural Triangle private route with the right timing.',
'North Central Province — Cultural Triangle',
'25–32°C',
'Heritage & Adventure',
'1–2 Days',
NULL,
NULL,
NULL,
'Visit Sigiriya with a Private Driver — UNESCO Lion Rock Sri Lanka | Island Culture Travels',
'Explore Sigiriya Rock with a professional chauffeur guide, private transport, and flexible Cultural Triangle itinerary planning.',
NULL,
1, 4, 1
),
(
'Yala',
'yala',
'Sri Lanka''s most famous safari destination — best known for leopard sightings, open-terrain wildlife, and dramatic coastal scenery in the deep south.',
'Yala is one of Sri Lanka''s best-known wildlife destinations and a popular choice for safari-focused private travel. It offers some of the world''s highest leopard densities alongside elephants, crocodiles, and coastal birdlife.',
'Yala is ideal for travelers who want classic Sri Lanka safari experiences with strong wildlife appeal and practical routing in the southern region.',
'Jeep safari, wildlife viewing, leopard search, elephant sightings, nature stays, and southern route combinations.',
'February to July is generally the best time for Yala safaris, when water levels are lower and wildlife congregates.',
'Yala is best reached by private vehicle from Ella, Tangalle, Udawalawe, or Colombo.',
'Pair Yala with Udawalawe, Ella, Tangalle, or Galle for a stronger multi-stop southern route.',
'Yala offers safari lodges, mid-range stays, and upscale nature-focused accommodation near the park entrance.',
'Use early morning safari timings (typically 6am), wear neutral-coloured clothing, and keep expectations realistic for wildlife sightings.',
'[{"question":"What is Yala best known for?","answer":"Yala is best known for wildlife safaris, especially leopard sightings and elephant encounters in a dramatic southern Sri Lanka setting."}]',
'Plan Your Yala Safari',
'We''ll help you add Yala to a private Sri Lanka route with the right safari timing and transport.',
'Southern Province — National Park',
'27–34°C',
'Wildlife Safari',
'1–2 Days',
'yala-national-park-lk.avif',
'yala-national-park-lk.avif',
NULL,
'Visit Yala with a Private Driver and Comfortable Vehicle | Island Culture Travels',
'Explore Yala National Park with a professional chauffeur guide, private safari planning, and flexible itinerary support.',
NULL,
1, 5, 1
),
(
'Galle',
'galle',
'A UNESCO-listed Dutch colonial fort town on Sri Lanka''s southern coast — with cobbled streets, boutique hotels, ocean views, and artisan character.',
'Galle Fort is one of the best-preserved colonial fortresses in Asia and a highlight of the southern Sri Lanka route. The old town inside the fort walls is a living, working community with churches, mosques, boutiques, and guesthouses.',
'Galle suits travelers who appreciate history, architecture, coastal scenery, and a relaxed atmosphere with quality dining and shopping.',
'Galle Fort walk, lighthouse viewpoint, colonial architecture, boutique shopping, coastal cafes, Unawatuna beach nearby.',
'The southern coast is best visited between November and April, when the seas are calmer on the west and south.',
'Galle is reached by private vehicle from Colombo (approximately 2–3 hours), Mirissa, or Tangalle.',
'Combine Galle with Mirissa, Tangalle, Udawalawe, or Colombo for a southern coastal route.',
'Galle has boutique hotels inside the fort walls and a wide range of options in the surrounding area.',
'Walk the fort walls at sunset for the best views. Reserve popular restaurants inside the fort in advance.',
'[{"question":"What is Galle best known for?","answer":"Galle is famous for its UNESCO-listed Dutch colonial fort, cobbled streets, lighthouse, and boutique atmosphere on the southern coast."}]',
'Plan Your Galle Stop',
'We''ll help you add Galle to a private southern Sri Lanka route with the right timing and transport.',
'Southern Province — Heritage Coast',
'26–30°C',
'History & Culture',
'1–2 Days',
'galle-lighthouse.jpg',
'galle-lighthouse.jpg',
NULL,
'Visit Galle with a Private Driver — Colonial Fort and Southern Coast | Island Culture Travels',
'Explore Galle Fort with a professional chauffeur guide, private transport along the southern coast, and flexible Sri Lanka itinerary support.',
NULL,
1, 6, 1
),
(
'Nuwara Eliya',
'nuwara-eliya',
'Sri Lanka''s cool highland retreat — rolling tea estates, misty mornings, colonial-era bungalows, and a gentler pace at over 1,800 metres.',
'Nuwara Eliya sits high in the heart of Sri Lanka''s tea country and offers a completely different atmosphere from the island''s coast and jungle. The town has a distinct colonial character with English-style gardens and a cooler, refreshing climate.',
'Nuwara Eliya suits travelers who want a break from the heat, enjoy scenic highland landscapes, and want to see working tea estates up close.',
'Tea estate visits, tea factory tours, scenic train journey, colonial town walk, Victoria Park, Horton Plains day trip, waterfalls.',
'April is the most famous month — the racing season brings a festive atmosphere. May–September is fresh and green after the rains.',
'Nuwara Eliya is best reached by private vehicle from Kandy, Ella, or Colombo, or by the legendary hill-country train from Kandy.',
'Combine Nuwara Eliya with Kandy, Ella, Horton Plains, or a tea estate overnight for a complete hill-country route.',
'Nuwara Eliya has colonial bungalows, tea estate stays, boutique guesthouses, and hotel options at various price points.',
'Bring warm layers — temperatures can drop significantly at night. The train journey through this region is one of the most scenic in Asia.',
'[{"question":"What is Nuwara Eliya best known for?","answer":"Nuwara Eliya is best known for its tea estates, cool highland climate, scenic train journey, and colonial-era character."}]',
'Plan Your Nuwara Eliya Visit',
'We''ll help you include Nuwara Eliya in a hill-country private route with the right pacing.',
'Central Province — Hill Country',
'14–22°C',
'Tea Country & Scenery',
'1–2 Days',
'adams-peak-lk.jpg',
'adams-peak-lk.jpg',
NULL,
'Visit Nuwara Eliya with a Private Driver — Tea Country Sri Lanka | Island Culture Travels',
'Explore Nuwara Eliya with a professional chauffeur guide, scenic highland routes, and flexible private travel planning.',
NULL,
0, 7, 1
),
(
'Mirissa',
'mirissa',
'A relaxed beach destination on Sri Lanka''s south coast — best known for whale watching, dolphin sightings, and a laid-back coastal atmosphere.',
'Mirissa is one of the most popular beach stops on Sri Lanka''s southern route. The crescent bay offers calm swimming conditions in season, good seafood, and easy access to whale watching in the Indian Ocean.',
'Mirissa suits travelers who want a relaxed beach stop with good food, ocean activities, and an easy connection to Galle or Tangalle.',
'Whale watching tours, dolphin watching, swimming, beach dining, sunset views, coconut hill viewpoint.',
'November to April is the best time for Mirissa — calm seas and ideal whale watching conditions.',
'Mirissa is reached by private vehicle from Galle (approximately 45 minutes) or Tangalle.',
'Combine Mirissa with Galle, Tangalle, Udawalawe, or Yala for a complete southern route.',
'Mirissa has beachfront guesthouses, boutique hotels, and a good range of dining options.',
'Book whale watching boats early during peak season. Depart by 6–6:30am for the best conditions.',
'[{"question":"What is Mirissa best known for?","answer":"Mirissa is best known for whale watching tours and a relaxed beach atmosphere on Sri Lanka''s southern coast."}]',
'Plan Your Mirissa Visit',
'We''ll help you add Mirissa to a southern Sri Lanka route with the right timing.',
'Southern Province — Coastal',
'27–31°C',
'Beach & Wildlife',
'1–2 Days',
'mirissa-beach-lk.jpg',
'mirissa-beach-lk.jpg',
NULL,
'Visit Mirissa with a Private Driver — Whale Watching and Southern Beach | Island Culture Travels',
'Explore Mirissa with a professional chauffeur guide, whale watching planning, and flexible southern Sri Lanka itinerary support.',
NULL,
0, 8, 1
),
(
'Udawalawe',
'udawalawe',
'One of the best safari destinations in Sri Lanka — especially for elephant herds in open terrain, accessible year-round with easy private transport.',
'Udawalawe is one of Sri Lanka''s most accessible and rewarding safari destinations, especially for elephant-focused travel. The open landscape makes wildlife sightings reliable and the park is well-suited to families and first-time safari travelers.',
'It is especially attractive for families and first-time safari travelers who want a practical wildlife stop with high success rates.',
'Elephant safari, open-terrain wildlife viewing, elephant transit home visit, countryside stays, and southern route combinations.',
'Udawalawe can work well year-round depending on route and weather patterns. Dry season from May to September is particularly rewarding.',
'Udawalawe is easiest to reach by private vehicle from Colombo, Ella, Tangalle, or Yala.',
'Combine Udawalawe with Yala, Ella, Tangalle, or Colombo in a private route.',
'Accommodation ranges from budget lodges to more comfortable safari stays close to the park.',
'Use private transport and keep enough time between transfers and safaris. The Elephant Transit Home is worth including.',
'[{"question":"What is Udawalawe best known for?","answer":"Udawalawe is best known for elephant safaris and reliable wildlife viewing in open landscape."}]',
'Plan Your Udawalawe Safari',
'We''ll help you include Udawalawe in a smooth and flexible wildlife itinerary.',
'Sabaragamuwa Province — National Park',
'28–33°C',
'Wildlife Safari',
'1–2 Days',
'tissamaharama-lk.jpg',
'tissamaharama-lk.jpg',
NULL,
'Visit Udawalawe with a Private Driver and Comfortable Vehicle | Island Culture Travels',
'Explore Udawalawe National Park with a professional chauffeur guide, elephant safaris, and flexible private wildlife travel.',
NULL,
0, 9, 1
);

-- Itineraries
INSERT INTO itineraries
(name, slug, days, excerpt, intro, route_summary, best_for, travel_notes, meta_title, meta_description, canonical_url, is_featured, sort_order, is_published)
VALUES
(
'Sri Lanka Itinerary 7 Days',
'sri-lanka-itinerary-7-days',
7,
'Best for short first visits with a balanced mix of scenery, culture, and private travel comfort.',
'A 7 day Sri Lanka itinerary works well for first-time visitors who want a short but meaningful private journey across the island.',
'Colombo / Airport → Cultural or scenic stop → hill country or safari → southern or city return',
'First-time visitors, couples, families, short private holidays',
'Private vehicle travel is recommended to keep this route practical and comfortable.',
'7 Day Sri Lanka Itinerary with Private Driver and Vehicle | Island Culture Travels',
'Discover cultural sites, wildlife parks, and scenic landscapes with a private chauffeur and reliable transport throughout your 7 day Sri Lanka journey.',
NULL,
1,
1,
1
),
(
'Sri Lanka Itinerary 10 Days',
'sri-lanka-itinerary-10-days',
10,
'Ideal for couples and families wanting a fuller Sri Lanka route with culture, scenery, wildlife, or beaches.',
'A 10 day Sri Lanka itinerary gives enough time for a deeper and better-paced island journey.',
'Colombo / Airport → cultural region → hill country → safari or beach → Colombo',
'Families, couples, first-time visitors, travelers wanting more variety',
'Ten days gives enough flexibility for a more rewarding multi-region route.',
'10 Day Sri Lanka Itinerary — Private Travel Across the Island | Island Culture Travels',
'Travel comfortably across Sri Lanka with a professional driver, modern vehicle, and a balanced itinerary covering culture, nature, and relaxation.',
NULL,
1,
2,
1
),
(
'Sri Lanka Wildlife Safari Itinerary 7 Days',
'sri-lanka-wildlife-safari-itinerary-7-days',
7,
'A 7 day wildlife itinerary covering major safari regions with private transport and flexible planning.',
'This safari-focused itinerary is ideal for travelers who want to experience Sri Lanka''s best wildlife regions in a short but well-paced private journey.',
'Colombo / Airport → Udawalawe → Yala → Southern Coast / Galle → Colombo',
'Wildlife lovers, couples, families, nature photographers',
'Morning and evening safari timing should be planned carefully depending on season and route flow.',
'7 Day Sri Lanka Wildlife Safari Itinerary with Private Driver | Island Culture Travels',
'Explore Sri Lanka''s national parks with private transport, flexible safari planning, and expert local guidance.',
NULL,
1,
3,
1
);

-- Country pages
INSERT INTO country_pages
(country_name, slug, excerpt, intro, why_sri_lanka, recommended_itinerary, travel_info, best_time, faq_json, meta_title, meta_description, canonical_url, sort_order, is_published)
VALUES
(
'India',
'india',
'Plan a private Sri Lanka tour from India with flexible itineraries, comfortable vehicles, and short or extended travel options.',
'Sri Lanka is one of the easiest and most rewarding international holiday options for travelers from India.',
'Sri Lanka suits Indian travelers because it offers short travel time, varied landscapes, familiar cultural touchpoints, and flexible private routes.',
'A good starting option is a 4 to 7 day private tour covering hill country, city highlights, wildlife, or a short scenic route.',
'Private vehicle travel is the most comfortable way to explore Sri Lanka for families and small groups.',
'Sri Lanka can be visited throughout the year depending on the route and travel focus.',
'[{"question":"Is Sri Lanka good for a short holiday from India?","answer":"Yes. Sri Lanka works very well for short private holidays thanks to manageable flight times and varied experiences."}]',
'Private Sri Lanka Tours for Travelers from India | Island Culture Travels',
'Plan your Sri Lanka holiday from India with a trusted chauffeur service, comfortable vehicle options, and personalized travel itineraries.',
NULL,
1,
1
);

-- Reviews
INSERT INTO reviews
(customer_name, country, rating, review_text, trip_type, is_featured, is_published, sort_order)
VALUES
(
'Priya M.',
'India',
5,
'We had a very comfortable private trip across Sri Lanka and really enjoyed the safari experiences. The planning was smooth, the vehicle was comfortable, and the driver was helpful throughout the journey.',
'Wildlife Tour',
1,
1,
1
);

-- Destination relations
INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'yala' AND d2.slug = 'udawalawe';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'yala' AND d2.slug = 'ella';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'udawalawe' AND d2.slug = 'yala';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'udawalawe' AND d2.slug = 'ella';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'kandy' AND d2.slug = 'sigiriya';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'kandy' AND d2.slug = 'ella';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'sigiriya' AND d2.slug = 'kandy';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'ella' AND d2.slug = 'nuwara-eliya';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'galle' AND d2.slug = 'mirissa';

INSERT IGNORE INTO destination_related (destination_id, related_destination_id)
SELECT d1.id, d2.id FROM destinations d1, destinations d2
WHERE d1.slug = 'mirissa' AND d2.slug = 'galle';

-- Destination ↔ Tour
INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'yala' AND t.slug = 'sri-lanka-wildlife-safari-tour';

INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'udawalawe' AND t.slug = 'sri-lanka-wildlife-safari-tour';

INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'ella' AND t.slug = 'private-sri-lanka-tour';

INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'kandy' AND t.slug = 'private-sri-lanka-tour';

INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'sigiriya' AND t.slug = 'private-sri-lanka-tour';

INSERT IGNORE INTO destination_tour (destination_id, tour_id)
SELECT d.id, t.id FROM destinations d, tours t
WHERE d.slug = 'galle' AND t.slug = 'private-sri-lanka-tour';

-- Destination ↔ Itinerary
INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id)
SELECT d.id, i.id FROM destinations d, itineraries i
WHERE d.slug = 'yala' AND i.slug = 'sri-lanka-wildlife-safari-itinerary-7-days';

INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id)
SELECT d.id, i.id FROM destinations d, itineraries i
WHERE d.slug = 'udawalawe' AND i.slug = 'sri-lanka-wildlife-safari-itinerary-7-days';

INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id)
SELECT d.id, i.id FROM destinations d, itineraries i
WHERE d.slug = 'ella' AND i.slug = 'sri-lanka-itinerary-10-days';

INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id)
SELECT d.id, i.id FROM destinations d, itineraries i
WHERE d.slug = 'kandy' AND i.slug = 'sri-lanka-itinerary-7-days';

INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id)
SELECT d.id, i.id FROM destinations d, itineraries i
WHERE d.slug = 'sigiriya' AND i.slug = 'sri-lanka-itinerary-7-days';

-- Itinerary ↔ Destination
INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i, destinations d
WHERE i.slug = 'sri-lanka-wildlife-safari-itinerary-7-days' AND d.slug = 'yala';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i, destinations d
WHERE i.slug = 'sri-lanka-wildlife-safari-itinerary-7-days' AND d.slug = 'udawalawe';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i, destinations d
WHERE i.slug = 'sri-lanka-itinerary-10-days' AND d.slug = 'ella';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i, destinations d
WHERE i.slug = 'sri-lanka-itinerary-7-days' AND d.slug = 'kandy';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i, destinations d
WHERE i.slug = 'sri-lanka-itinerary-7-days' AND d.slug = 'sigiriya';
