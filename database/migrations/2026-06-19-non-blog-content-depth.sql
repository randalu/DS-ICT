-- Expands non-blog content depth for AI/search readiness.
-- Source basis: Sri Lanka Tourism official attraction themes, transport guidance,
-- UNESCO Sri Lanka World Heritage list, and the route/source-market content pack.
-- Idempotent guard: each update only runs while the audited core-content length
-- remains below the current threshold.

SET NAMES utf8mb4;

UPDATE destinations
SET travel_tips = CONCAT(COALESCE(travel_tips, ''), '

Use this destination as part of a paced private route rather than as an isolated stop. Sri Lanka Tourism presents the island through heritage, coast, wildlife, hill-country scenery, wellness, food, pilgrimage and local experience themes, so the strongest plan links each place to the nearest theme instead of rushing between districts. Build in realistic road time, early starts for heat-sensitive sites, and slower arrivals for long-haul guests.

This destination works especially well for ',
CASE slug
    WHEN 'tangalle' THEN 'Use Tangalle for quieter south coast beach nights after Yala, Udawalawe, Ella or Galle. It suits honeymoon, luxury and slower family itineraries better than one-night sightseeing routes.'
    WHEN 'batticaloa' THEN 'Use Batticaloa as an east coast culture and lagoon stop between Passikudah, Trincomalee and Arugam Bay. It works best in mid-year east coast routing.'
    WHEN 'ratnapura' THEN 'Use Ratnapura as a nature and gem-country connector for Adam Peak, Sinharaja-style rainforest interests and Sabaragamuwa scenery rather than a rushed beach detour.'
    WHEN 'horton-plains' THEN 'Use Horton Plains for early-morning walking, cloud-forest scenery and cool highland contrast from Nuwara Eliya or Ella. Weather and start time matter more than distance.'
    WHEN 'hikkaduwa' THEN 'Use Hikkaduwa for an active south-west beach base with reef, surf, diving-season and Galle access. It suits travellers who want a livelier coast.'
    WHEN 'mannar' THEN 'Use Mannar for repeat visitors, birding, remote coast, island landscapes and northern extensions from Anuradhapura or Jaffna. Keep the pace slower.'
    WHEN 'knuckles-mountain-range' THEN 'Use Knuckles for hiking, village scenery and nature-focused routes from Kandy. It needs local timing and should not be treated as a quick viewpoint stop.'
    WHEN 'passikudah' THEN 'Use Passikudah for a calmer east coast beach stay after the Cultural Triangle. It is strongest when guests want warm-water rest instead of nightlife.'
    WHEN 'dambulla' THEN 'Use Dambulla as a Cultural Triangle anchor for the cave temple, Sigiriya, Minneriya, Polonnaruwa and village experiences. It works well for efficient private touring.'
    WHEN 'weligama' THEN 'Use Weligama for beginner surf, family beach time and access to Mirissa, Galle and Ahangama. It is a practical south coast base, not just a surf stop.'
    WHEN 'matara' THEN 'Use Matara as a south coast connector for Mirissa, Weligama, Tangalle and local temples. It suits travellers who want a less polished coastal town experience.'
    WHEN 'kurunegala' THEN 'Use Kurunegala as a practical inland connector between Colombo, Kandy, Anuradhapura and the Cultural Triangle. It is useful for road pacing and regional stops.'
    WHEN 'ampara' THEN 'Use Ampara for east and south-east nature, Gal Oya style wildlife interests and off-main-route travel. It works best for repeat visitors with flexible time.'
    WHEN 'unawatuna' THEN 'Use Unawatuna for beach access close to Galle Fort, family-friendly swimming conditions in season and easy south coast day trips.'
    WHEN 'negombo' THEN 'Use Negombo as a soft airport arrival or departure base, especially after long-haul flights. It protects the first day from becoming a long transfer.'
    WHEN 'bentota' THEN 'Use Bentota for west coast resort comfort, river activities and an easier final beach stay from Colombo or Galle.'
    WHEN 'mirissa' THEN 'Use Mirissa for south coast beach time, whale watching season planning and links to Weligama, Galle and Matara. Keep ocean activities weather-aware.'
    WHEN 'arugam-bay' THEN 'Use Arugam Bay for east coast surf and slower beach culture. It requires deliberate routing and should not be squeezed into a classic short south-coast route.'
    WHEN 'adams-peak' THEN 'Use Adam Peak for pilgrimage, sunrise hiking and spiritual travel. It needs a night-start rhythm and a lighter day after the climb.'
    WHEN 'nuwara-eliya' THEN 'Use Nuwara Eliya for tea country, cool climate, gardens and Horton Plains access. It works best when guests want highland rest, not only a photo stop.'
    WHEN 'jaffna' THEN 'Use Jaffna for northern culture, Hindu temples, food, islands and repeat-visitor depth. It needs extra nights to justify the travel time.'
    WHEN 'anuradhapura' THEN 'Use Anuradhapura for sacred-city heritage, Buddhist pilgrimage and ancient reservoirs. It pairs well with Mihintale, Wilpattu-style wildlife interests and the Cultural Triangle.'
    WHEN 'galle' THEN 'Use Galle for fort heritage, coast access, food and an easy final south-coast cultural stop. It can be a day visit or an overnight base.'
    WHEN 'trincomalee' THEN 'Use Trincomalee for east coast beaches, temples, harbour views and whale or snorkelling interests in season. It pairs naturally with Sigiriya and Polonnaruwa.'
    WHEN 'polonnaruwa' THEN 'Use Polonnaruwa for compact ancient-city touring, cycling-style sightseeing and a strong heritage pairing with Sigiriya and Dambulla.'
    WHEN 'kandy' THEN 'Use Kandy for Temple of the Tooth, botanical gardens, cultural evenings and hill-country transition. Avoid overloading it after a long Cultural Triangle drive.'
    WHEN 'colombo' THEN 'Use Colombo for arrival services, markets, food, galleries and short city context. It works best as a soft start, not as a full substitute for the island route.'
    WHEN 'ella' THEN 'Use Ella for tea-country scenery, hikes, waterfalls and the rail-route story. It needs time because viewpoints, train timing and transfers compete for the same day.'
    ELSE 'Use this destination as a route-building node with realistic travel time, seasonal context and a clear reason for the guest to stay or stop.'
END)
WHERE slug IN ('tangalle', 'batticaloa', 'ratnapura', 'horton-plains', 'hikkaduwa', 'mannar', 'knuckles-mountain-range', 'passikudah', 'dambulla', 'weligama', 'matara', 'kurunegala', 'ampara', 'unawatuna', 'negombo', 'bentota', 'mirissa', 'arugam-bay', 'adams-peak', 'nuwara-eliya', 'jaffna', 'anuradhapura', 'galle', 'trincomalee', 'polonnaruwa', 'kandy', 'colombo', 'ella')
  AND CHAR_LENGTH(TRIM(CONCAT_WS(' ', COALESCE(excerpt, ''), COALESCE(intro, ''), COALESCE(why_visit, ''), COALESCE(things_to_do, ''), COALESCE(how_to_get_there, ''), COALESCE(suggested_itinerary, ''), COALESCE(travel_tips, '')))) < 900;

UPDATE experiences
SET cta_text = CONCAT(COALESCE(cta_text, ''), '

This experience works best when matched to the right route, season, start time, and nearby overnight base. Families need softer pacing, honeymooners need fewer transfers, photographers need better light, wildlife guests need dawn or late-afternoon timing, and long-haul guests need recovery time. Sri Lanka Tourism groups official travel ideas around wildlife, beaches, heritage, wellness, food, pilgrimage, adventure and scenic travel, so the strongest experience pages should explain where the experience fits in that bigger island story.

This experience works especially well for ',
CASE slug
    WHEN 'sri-lankan-food-market-tour' THEN 'Plan this in Colombo, Kandy, Galle or Negombo with a local market, home-style meal, seafood or tea-country tasting. Keep a lighter drive day after spicy or market-heavy food experiences.'
    WHEN 'tea-country-nuwara-eliya' THEN 'Use this for Nuwara Eliya, Kandy and Ella routes where tea estates, cooler weather, gardens and highland viewpoints are the main story.'
    WHEN 'minneriya-elephant-gathering' THEN 'Base it from Sigiriya, Dambulla or Habarana and keep the park choice flexible between nearby elephant parks depending on local conditions.'
    WHEN 'udawalawe-elephant-safari' THEN 'Use this for reliable elephant-focused family safari planning between Ella, Yala, Tangalle and the south coast.'
    WHEN 'mirissa-whale-watching' THEN 'Plan this as a seasonal ocean activity from Mirissa or Weligama with a spare coastal day because sea conditions can affect departures.'
    WHEN 'ayurveda-wellness-retreat' THEN 'Use this for slower beach or hill-country stays, not as a quick add-on between transfers. Wellness needs time and the right hotel setting.'
    WHEN 'family-wildlife-beach-route' THEN 'Use this to connect Sigiriya, Kandy, Udawalawe or Yala and a beach base with two-night stays and limited early starts.'
    WHEN 'luxury-slow-travel-sri-lanka' THEN 'Use this for premium lodges, tea bungalows, safari stays and beach resorts where the route protects time to enjoy each property.'
    WHEN 'galle-fort-southern-coast' THEN 'Use this for a heritage walk, food stop and south coast beach pairing around Galle, Unawatuna, Hikkaduwa, Weligama or Mirissa.'
    WHEN 'sinharaja-birding-nature' THEN 'Use this for rainforest, birding and repeat-visitor nature routes with early starts, local guides and flexible weather planning.'
    WHEN 'buddhist-heritage-pilgrimage' THEN 'Use this for Anuradhapura, Kandy, Dambulla, Polonnaruwa and Adam Peak routes with respectful clothing and slower temple timing.'
    WHEN 'south-coast-surf-lessons' THEN 'Use this around Weligama, Hikkaduwa, Ahangama or Arugam Bay depending on season and skill level; do not split a short trip across both coasts.'
    WHEN 'yala-leopard-safari' THEN 'Use this for leopard-focused wildlife routes with an overnight near the park and a quieter beach or hill-country day after the early drive.'
    WHEN 'honeymoon-beach-boutique-stays' THEN 'Use this for couples who want fewer bases, better room choices, soft adventure and a restful beach finish after culture and tea country.'
    WHEN 'kandy-to-ella-train' THEN 'Use this when train timing and reserved seats support the route; send luggage with the driver and keep backup road stops ready.'
    WHEN 'sigiriya-pidurangala-sunrise' THEN 'Use this for active travellers, photographers and Cultural Triangle routes; choose Sigiriya for heritage depth and Pidurangala for the view.'
    ELSE 'Use this experience only when timing, season and nearby overnight logistics make it stronger than another transfer day.'
END)
WHERE slug IN ('sri-lankan-food-market-tour', 'tea-country-nuwara-eliya', 'minneriya-elephant-gathering', 'udawalawe-elephant-safari', 'mirissa-whale-watching', 'ayurveda-wellness-retreat', 'family-wildlife-beach-route', 'luxury-slow-travel-sri-lanka', 'galle-fort-southern-coast', 'sinharaja-birding-nature', 'buddhist-heritage-pilgrimage', 'south-coast-surf-lessons', 'yala-leopard-safari', 'honeymoon-beach-boutique-stays', 'kandy-to-ella-train', 'sigiriya-pidurangala-sunrise')
  AND CHAR_LENGTH(TRIM(CONCAT_WS(' ', COALESCE(excerpt, ''), COALESCE(intro, ''), COALESCE(where_to_experience, ''), COALESCE(best_time, ''), COALESCE(cta_text, '')))) < 650;

UPDATE itineraries
SET travel_notes = CONCAT(COALESCE(travel_notes, ''), '

This itinerary is designed for private chauffeur-guided travel, so it should protect arrival windows, hotel check-in timing, wildlife start times and realistic hill-country drives. Sri Lanka is compact but road travel can be slow, and official tourism guidance highlights that scenic drives are part of the island experience rather than dead time. For Europe, North America and Australia travellers, the route should also protect recovery after long-haul flights and avoid too many one-night stays.

This itinerary works best for ',
CASE slug
    WHEN 'sri-lanka-south-coast-itinerary-8-days' THEN 'Best for Galle, Unawatuna, Hikkaduwa, Weligama, Mirissa, Tangalle and Bentota with one heritage day and several beach-paced days.'
    WHEN 'sri-lanka-itinerary-7-days' THEN 'Best for first-time travellers who want the classic highlights without forcing every region. Keep the beach or safari choice focused.'
    WHEN 'sri-lanka-family-itinerary-10-days' THEN 'Best for families that need pools, wildlife, culture and beach time with two-night stays and limited early starts.'
    WHEN 'sri-lanka-birding-nature-itinerary-12-days' THEN 'Best for rainforest, highland, dry-zone safari and birding interests with flexible weather and local guide timing.'
    WHEN 'sri-lanka-east-coast-itinerary-10-days' THEN 'Best for mid-year beach planning around Sigiriya, Polonnaruwa, Trincomalee, Passikudah, Batticaloa or Arugam Bay.'
    WHEN 'sri-lanka-wildlife-itinerary-9-days' THEN 'Best for Yala, Udawalawe, Minneriya-style elephant parks and dry-zone landscapes, with beach rest after safari days.'
    WHEN 'sri-lanka-itinerary-12-days' THEN 'Best for travellers who want culture, tea country, safari and coast with fewer compromises than a seven-day route.'
    WHEN 'sri-lanka-luxury-itinerary-11-days' THEN 'Best for slower premium stays, private transfers, tea bungalows, safari lodges and better coastal hotels.'
    WHEN 'sri-lanka-honeymoon-itinerary-12-days' THEN 'Best for couples who want culture, scenery, boutique hotels and a beach finish without daily hotel changes.'
    WHEN 'sri-lanka-itinerary-5-days' THEN 'Best as a compact extension or short private tour focused on Sigiriya, Kandy, Ella or one coast, not a full-island promise.'
    WHEN 'sri-lanka-itinerary-14-days' THEN 'Best for long-haul travellers who want the complete island arc with heritage, tea country, wildlife and a seasonal beach.'
    WHEN 'sri-lanka-itinerary-10-days' THEN 'Best for first-time Europe, American and Australian guests balancing Sigiriya, Kandy, Ella, safari and the south coast.'
    ELSE 'Best when the route is paced around traveller type, season and the correct coast rather than a fixed checklist.'
END)
WHERE slug IN ('sri-lanka-south-coast-itinerary-8-days', 'sri-lanka-itinerary-7-days', 'sri-lanka-family-itinerary-10-days', 'sri-lanka-birding-nature-itinerary-12-days', 'sri-lanka-east-coast-itinerary-10-days', 'sri-lanka-wildlife-itinerary-9-days', 'sri-lanka-itinerary-12-days', 'sri-lanka-luxury-itinerary-11-days', 'sri-lanka-honeymoon-itinerary-12-days', 'sri-lanka-itinerary-5-days', 'sri-lanka-itinerary-14-days', 'sri-lanka-itinerary-10-days')
  AND CHAR_LENGTH(TRIM(CONCAT_WS(' ', COALESCE(excerpt, ''), COALESCE(intro, ''), COALESCE(highlights, ''), COALESCE(route_summary, ''), COALESCE(best_for, ''), COALESCE(travel_notes, ''), COALESCE(pricing_notes, '')))) < 850;

UPDATE tours
SET vehicle_options = CONCAT(COALESCE(vehicle_options, ''), '

When quoting this package, keep private air-conditioned vehicle, driver accommodation, fuel, parking and route timing clearly separated from hotels, attraction tickets and specialist activities unless those are included in the final offer. Good packages explain the likely rhythm: airport transfer, cultural base, hill-country section, safari or nature day, seasonal coast and departure buffer. This makes the package easier for travellers to understand because timing, attractions and inclusions are explicit.

This package works best for ',
CASE slug
    WHEN 'sri-lanka-10-day-itinerary-package' THEN 'Use for first-time travellers who want Sigiriya, Kandy, Ella, one safari and the south coast with practical drive times.'
    WHEN 'sri-lanka-family-tour-package' THEN 'Use for families that need flexible starts, safer meal stops, wildlife, beach time, pools and fewer one-night hotels.'
    WHEN 'sri-lanka-wildlife-safari-package' THEN 'Use for Yala, Udawalawe, Minneriya-style elephant parks and nature routes, with early starts and realistic park transfers.'
    WHEN 'luxury-sri-lanka-slow-travel-package' THEN 'Use for premium hotels, tea bungalows, safari lodges and slow coastal stays where the guest values comfort over maximum stops.'
    WHEN 'sri-lanka-honeymoon-package' THEN 'Use for couples who want boutique hotels, scenic drives, privacy, soft adventure and a relaxed beach finish.'
    WHEN 'sri-lanka-14-day-grand-tour-package' THEN 'Use for long-haul guests who want a complete culture, hill-country, wildlife and coast route without compressing the island.'
    ELSE 'Use when the package can explain timing, vehicle, inclusions, attraction logic and the best season clearly.'
END)
WHERE slug IN ('sri-lanka-10-day-itinerary-package', 'sri-lanka-family-tour-package', 'sri-lanka-wildlife-safari-package', 'luxury-sri-lanka-slow-travel-package', 'sri-lanka-honeymoon-package', 'sri-lanka-14-day-grand-tour-package')
  AND CHAR_LENGTH(TRIM(CONCAT_WS(' ', COALESCE(excerpt, ''), COALESCE(intro, ''), COALESCE(highlights, ''), COALESCE(sample_itinerary, ''), COALESCE(pricing_notes, ''), COALESCE(vehicle_options, '')))) < 750;

UPDATE country_pages
SET travel_info = CONCAT(COALESCE(travel_info, ''), '

This page helps travellers from this market choose the right trip length, likely flight recovery, seasonal coast choice, comfort level, route pace, and whether a private vehicle saves time. It should link toward the best matching itinerary, package, and experience rather than repeating generic Sri Lanka copy.

Travellers from this market often value ',
CASE slug
    WHEN 'malaysia' THEN 'Malaysia travellers often suit short to medium breaks with food, cool hill country, family pacing, shopping and easy cultural highlights.'
    WHEN 'poland' THEN 'Poland travellers often value winter sun, good-value private touring, wildlife, heritage and a clear 10 to 14 day route.'
    WHEN 'italy' THEN 'Italy travellers often respond to culture, food, boutique coastal stays, heritage towns and slower scenic touring.'
    WHEN 'japan' THEN 'Japan travellers often need clear timing, comfort, safety, heritage depth, gardens, tea country and efficient private transfers.'
    WHEN 'spain' THEN 'Spain travellers often suit culture, beaches, food, wildlife and 10 to 14 day leisure pacing with seasonal coast guidance.'
    WHEN 'france' THEN 'France travellers often value boutique hotels, heritage, food, scenery, wildlife and slower routes with strong local detail.'
    WHEN 'switzerland' THEN 'Switzerland travellers often suit premium private touring, nature, rail scenery, wildlife and high-comfort hotel pacing.'
    WHEN 'bangladesh' THEN 'Bangladesh travellers often need short holiday routes, family-friendly pacing, shopping, food, hill-country cooling and beach add-ons.'
    WHEN 'denmark' THEN 'Denmark travellers often value winter sun, nature, wildlife, responsible travel, beaches and relaxed 10 to 14 day touring.'
    WHEN 'canada' THEN 'Canada travellers usually benefit from longer stays after long-haul flights, with culture, wildlife, tea country and beach rest.'
    WHEN 'israel' THEN 'Israel travellers often suit active private routes with hiking, beaches, food, wildlife and flexible timing around holidays.'
    WHEN 'netherlands' THEN 'Netherlands travellers often value cycling-style heritage stops, wildlife, beaches, local food and efficient private routing.'
    ELSE 'Use a country-specific route angle that explains season, trip length, flight recovery, comfort and the best matching itinerary.'
END)
WHERE slug IN ('malaysia', 'poland', 'italy', 'japan', 'spain', 'france', 'switzerland', 'bangladesh', 'denmark', 'canada', 'israel', 'netherlands')
  AND CHAR_LENGTH(TRIM(CONCAT_WS(' ', COALESCE(excerpt, ''), COALESCE(intro, ''), COALESCE(why_sri_lanka, ''), COALESCE(recommended_itinerary, ''), COALESCE(travel_info, ''), COALESCE(best_time, '')))) < 750;
