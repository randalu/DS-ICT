-- PHASE 48-55: airport-transfer hub and route-specific transfer pages.
-- Route windows are planning baselines; traffic, flight formalities, stops,
-- hotel location, weather, and safari or train timing must be confirmed.

SET NAMES utf8mb4;

INSERT INTO static_pages
    (title, slug, content, faq_json, cta_title, cta_text, hero_image,
     meta_title, meta_description, canonical_url, sort_order, is_published)
VALUES
(
    'Sri Lanka Airport Transfers',
    'sri-lanka-airport-transfers',
    '<p>Start or finish a private Sri Lanka journey with a planned transfer from Bandaranaike International Airport (CMB). A useful meet-and-greet plan accounts for flight details, arrival formalities, luggage, vehicle space, and the next hotel instead of promising a fixed arrival time.</p><h2>Meet-and-greet and waiting</h2><p>Share your flight number, arrival date, passenger count, luggage needs, and first accommodation. The pickup plan can then allow for normal airport formalities and a sensible waiting approach if the flight schedule changes.</p><h2>Vehicle and luggage planning</h2><p>Vehicle choice depends on passenger count, luggage volume, child seats, comfort expectations, and whether the transfer becomes the first day of a longer private route. Confirm the vehicle and pickup details before travel.</p><h2>Popular onward routes</h2><ul><li><a href="colombo-airport-to-sigiriya">Colombo Airport to Sigiriya</a></li><li><a href="sigiriya-to-kandy-private-transfer">Sigiriya to Kandy</a></li><li><a href="galle-to-colombo-airport-private-transfer">Galle to Colombo Airport</a></li></ul><h2>Delays and changes</h2><p>Flight schedules, road conditions, and arrival times can change. Check current airport information and send updated flight details as soon as possible so the route and waiting plan can be reviewed.</p>',
    '[{"question":"What should I send for an airport transfer request?","answer":"Share your flight number, arrival or departure date, passenger count, luggage, hotel or destination, and preferred vehicle comfort."},{"question":"Can the driver wait if my flight is delayed?","answer":"Waiting arrangements depend on the booking and updated flight information. Send changes early so the practical pickup plan can be checked."},{"question":"Can an airport transfer become part of a private tour?","answer":"Yes. Airport pickup can be planned as the first or final movement in a wider route, with the vehicle and daily pace matched to the rest of the itinerary."}]',
    'Plan your airport transfer',
    'Send your flight and destination details. We will help plan a practical pickup, vehicle, and onward route.',
    'assets/images/home-hero/home-5.jpg',
    'Sri Lanka Airport Transfers | Island Culture Travels',
    'Plan a private Sri Lanka airport transfer from Bandaranaike International Airport with meet-and-greet, luggage, vehicle, and onward route guidance.',
    NULL, 450, 1
),
(
    'Colombo Airport to Sigiriya Private Transfer',
    'colombo-airport-to-sigiriya',
    '<p>A private transfer from Bandaranaike International Airport (CMB) to Sigiriya is a practical first movement for a Cultural Triangle route. Current itinerary planning uses roughly a 4–5 hour road window, but the actual day depends on arrival formalities, traffic, stops, weather, and the exact hotel location.</p><h2>Plan the arrival day</h2><p>Share the flight details and whether you want a direct transfer, a recovery night near the airport, or a stop such as Dambulla. Avoid placing a tightly timed attraction immediately after a long international arrival.</p><h2>Luggage and vehicle</h2><p>Choose a vehicle with enough space for people and luggage. If the transfer is the first day of a multi-day tour, the driver can help keep the next day realistic rather than adding every nearby attraction to arrival day.</p><h2>Continue the route</h2><p>Sigiriya can lead naturally to Kandy, the hill country, wildlife, or the south coast. Review the <a href="sigiriya-to-kandy-private-transfer">Sigiriya to Kandy transfer</a> and <a href="sri-lanka-private-tours">private tour planning hub</a> before confirming the sequence.</p>',
    '[{"question":"How long should I allow from Colombo Airport to Sigiriya?","answer":"Use a broad 4–5 hour planning window for the road transfer and confirm the timing around your flight, traffic, stops, and hotel location."},{"question":"Should I stay near the airport after a late flight?","answer":"It can be sensible after a late or disrupted arrival. Compare recovery time, the next day route, and the opening times of the attractions you want to visit."},{"question":"Can I stop at Dambulla on the way?","answer":"Possibly, depending on arrival time, luggage, attraction hours, and how much recovery the group needs. Treat it as an option to confirm rather than a fixed promise."}]',
    'Arrange the first route day',
    'Tell us your flight, group, luggage, and first hotel. We will help shape a workable airport-to-Sigiriya day.',
    'assets/images/home-hero/home-2.jpg',
    'Colombo Airport to Sigiriya Transfer | Island Culture Travels',
    'Plan a private Colombo Airport to Sigiriya transfer with a realistic arrival window, luggage guidance, and route options.',
    NULL, 451, 1
),
(
    'Sigiriya to Kandy Private Transfer',
    'sigiriya-to-kandy-private-transfer',
    '<p>Sigiriya to Kandy is a useful transition from the Cultural Triangle into the hill country. Current itinerary records use roughly 2.5–3.5 hours as a planning window, with the final timing shaped by traffic, stops, road conditions, and the chosen hotel.</p><h2>Useful stops and route logic</h2><p>Discuss whether the day should focus on a direct arrival in Kandy, a cultural stop, a garden or craft visit, or a slower route through the Matale area. One well-timed stop is usually more useful than a crowded list.</p><h2>Arrival in Kandy</h2><p>Kandy traffic and hotel access can affect the final part of the journey. Leave room for the Temple of the Tooth, lake area, gardens, or a cultural performance only after the accommodation and timing are confirmed.</p><h2>Continue to the hills</h2><p>From Kandy, compare the <a href="kandy-to-ella-private-transfer">Kandy to Ella options</a> or continue through tea country as part of a <a href="sri-lanka-private-tours">private Sri Lanka tour</a>.</p>',
    '[{"question":"How long is the Sigiriya to Kandy transfer?","answer":"Use roughly 2.5–3.5 hours as a planning window and confirm the day around traffic, stops, road conditions, and hotel location."},{"question":"Can I visit a stop on the way to Kandy?","answer":"Yes, if the stop fits the route and opening times. Share the priority before the day is fixed so the itinerary does not become unnecessarily rushed."},{"question":"Should I visit Kandy on arrival day?","answer":"That depends on arrival time and energy. A short lake or neighbourhood visit may fit, while a major site or performance may be better planned with a confirmed schedule."}]',
    'Plan the Cultural Triangle to Kandy move',
    'Share your route priorities and hotel locations. We will help balance the transfer with useful stops and a realistic Kandy arrival.',
    'assets/images/home-hero/home-3.jpg',
    'Sigiriya to Kandy Private Transfer | Island Culture Travels',
    'Plan a private Sigiriya to Kandy transfer with a realistic road window, useful stops, and hill-country route advice.',
    NULL, 452, 1
),
(
    'Kandy to Ella Private Transfer',
    'kandy-to-ella-private-transfer',
    '<p>Kandy to Ella can be planned by private road, by scenic train, or as a combination of train and private vehicle support. A road transfer commonly needs roughly 4–5 hours before stops, while the scenic train takes longer and depends on the timetable, seats, delays, and luggage plan.</p><h2>Road or train?</h2><p>Choose the train when the scenery is central to the experience and the group has enough time for uncertainty. Choose the road when arrival timing, luggage, hotel access, or route flexibility matters more. A shorter train section can sometimes combine both priorities.</p><h2>Luggage and stations</h2><p>Train tickets, station timing, carriage conditions, and luggage handling need to be checked for the travel date. A private driver can support hotel-to-station and station-to-hotel movements even when the scenic section is by rail.</p><h2>Plan time in Ella</h2><p>Review <a href="destinations/ella">Ella planning</a> and the <a href="experiences/kandy-to-ella-train">scenic train experience</a> before choosing the transfer format.</p>',
    '[{"question":"Is the Kandy to Ella train better than driving?","answer":"It depends on whether scenery or predictable timing matters more. The train is slower and seat availability can change, while a private road transfer gives more control over stops and hotel arrival."},{"question":"How long does the road transfer take?","answer":"Allow roughly 4–5 hours before stops, with the final time depending on traffic, weather, and the hotel location."},{"question":"Can a driver help if I take the train?","answer":"A driver can help with the hotel-to-station and onward hotel movements, luggage planning, and a backup road option if the train plan changes."}]',
    'Choose the right Kandy to Ella option',
    'Tell us whether the journey, the scenery, or the arrival timing matters most. We will compare the practical road and train options.',
    'assets/images/home-hero/home-3.jpg',
    'Kandy to Ella Private Transfer or Train | Island Culture Travels',
    'Compare a private Kandy to Ella road transfer with the scenic train, including timing, luggage, stops, and flexibility.',
    NULL, 453, 1
),
(
    'Ella to Yala Private Transfer',
    'ella-to-yala-private-transfer',
    '<p>Ella to Yala is a practical hill-country to safari transfer, but the departure time should protect the safari plan rather than treating the road as an afterthought. Current itinerary records use roughly 2.5–3.5 hours to the Yala or Tissamaharama area, subject to the lodge, traffic, weather, and stops.</p><h2>Protect the safari timing</h2><p>Decide whether the priority is an afternoon arrival, an evening briefing, or an early safari the next day. The best hotel base can depend on the park gate, safari operator, and the time of year.</p><h2>Luggage and hotel positioning</h2><p>Keep luggage accessible and confirm whether the accommodation is in Tissamaharama, near a park gate, or farther along the south-coast route. A private vehicle makes it easier to adjust the plan when the lodge or safari timing changes.</p><h2>Continue to the coast</h2><p>After Yala, compare the <a href="yala-to-mirissa-private-transfer">Yala to Mirissa transfer</a> or ask for a route that continues toward Galle and the south coast.</p>',
    '[{"question":"How long is the Ella to Yala transfer?","answer":"Allow roughly 2.5–3.5 hours to the Yala or Tissamaharama area and confirm the final timing around the lodge, park gate, traffic, and stops."},{"question":"Should I do a safari on arrival day?","answer":"That depends on arrival time, park-entry rules, lodge location, and the season. A next-morning safari can be the calmer option when the transfer starts in Ella."},{"question":"Where should I stay for Yala?","answer":"Compare Tissamaharama, a lodge near the relevant park gate, and onward coastal locations. The best base depends on the safari plan and the next route day."}]',
    'Plan the Ella to safari move',
    'Share your Ella hotel, safari preference, and onward coast plans. We will help place the transfer and safari timing sensibly.',
    'assets/images/home-hero/home-6.jpg',
    'Ella to Yala Private Transfer | Island Culture Travels',
    'Plan a private Ella to Yala transfer around realistic road timing, safari schedules, lodge location, luggage, and onward coast travel.',
    NULL, 454, 1
),
(
    'Ella to Udawalawe Private Transfer',
    'ella-to-udawalawe-private-transfer',
    '<p>Ella to Udawalawe is a distinct wildlife route from Ella to Yala. Allow a broad 3–5 hour planning window depending on the lodge, park gate, traffic, weather, and stops, then protect enough time for the safari plan and a comfortable arrival.</p><h2>Why choose Udawalawe?</h2><p>Udawalawe often suits travellers who want an open-terrain wildlife experience and a practical southbound route. The right choice depends on the animals, landscapes, season, accommodation, and the next destination rather than on a generic best-park claim.</p><h2>Plan the safari day</h2><p>Confirm the preferred safari time, jeep arrangement, park entry details, luggage storage, and whether the group needs a recovery night. Do not attach a fixed safari promise to a transfer until the current operator and park conditions are checked.</p><h2>Compare the route</h2><p>Use the <a href="ella-to-yala-private-transfer">Ella to Yala page</a> for a different safari route, or include Udawalawe in a <a href="sri-lanka-private-tours">private Sri Lanka tour</a>.</p>',
    '[{"question":"How is Ella to Udawalawe different from Ella to Yala?","answer":"Udawalawe and Yala offer different park settings, wildlife priorities, lodge locations, and onward routes. Choose based on the experience and the rest of the itinerary."},{"question":"How long should I allow from Ella to Udawalawe?","answer":"Use a broad 3–5 hour planning window and confirm the final timing around the lodge, park gate, traffic, weather, and stops."},{"question":"Can Udawalawe fit between Ella and the south coast?","answer":"Yes, when the route gives enough time for the transfer, safari, and recovery. Avoid forcing it into a plan that already has too many long travel days."}]',
    'Plan an Ella to Udawalawe route',
    'Tell us whether elephants, route efficiency, or a slower wildlife stay is the priority. We will help compare the practical options.',
    'assets/images/home-hero/home-6.jpg',
    'Ella to Udawalawe Private Transfer | Island Culture Travels',
    'Plan a private Ella to Udawalawe transfer with wildlife timing, lodge positioning, luggage, and south-coast route guidance.',
    NULL, 455, 1
),
(
    'Yala to Mirissa Private Transfer',
    'yala-to-mirissa-private-transfer',
    '<p>Yala to Mirissa is a natural transition from a safari base to the south coast. Allow roughly 2–4 hours for the road movement depending on the starting lodge, safari finish, traffic, weather, and the exact beach accommodation.</p><h2>Safari first, coast second</h2><p>If the group has an early safari, plan breakfast, luggage collection, and the road transfer together. A later coast arrival may be more comfortable than trying to add several stops after a dawn start.</p><h2>Choose the right south-coast base</h2><p>Mirissa can work for beach time, whale-watching plans, and a relaxed coastal finish. Compare it with Galle, Weligama, or Tangalle when the route or the travel month makes another base more practical.</p><h2>Keep the airport plan visible</h2><p>Review the <a href="galle-to-colombo-airport-private-transfer">Galle to Colombo Airport transfer</a> if the route ends on the south coast, or ask for a complete private itinerary.</p>',
    '[{"question":"How long is Yala to Mirissa?","answer":"Allow roughly 2–4 hours depending on the safari lodge, departure time, traffic, weather, stops, and beach hotel location."},{"question":"Can I travel to Mirissa after a morning safari?","answer":"Often, but the timing depends on the safari finish, breakfast, luggage, and the route. Build in a realistic coast arrival rather than promising a fixed check-in time."},{"question":"Should I stay in Mirissa or Galle?","answer":"Mirissa suits a beach and whale-watching finish, while Galle adds heritage and a stronger airport-transfer position. The best choice depends on the next flight and travel month."}]',
    'Plan the safari to south-coast move',
    'Share your safari timing, beach preference, and departure date. We will help choose a practical south-coast finish.',
    'assets/images/home-hero/home-4.jpg',
    'Yala to Mirissa Private Transfer | Island Culture Travels',
    'Plan a private Yala to Mirissa transfer with safari timing, realistic road planning, luggage, and south-coast options.',
    NULL, 456, 1
),
(
    'Galle to Colombo Airport Private Transfer',
    'galle-to-colombo-airport-private-transfer',
    '<p>Finish a south-coast journey with a planned private transfer from Galle to Bandaranaike International Airport (CMB). Current itinerary records use roughly 2–3 hours by expressway as a planning baseline, but traffic, road conditions, airport formalities, and the airline check-in deadline require a generous buffer.</p><h2>Choose the departure window</h2><p>Work backward from the airline deadline, not only from the flight time. Early flights may justify staying closer to the airport on the final night; later flights may allow a relaxed departure from Galle after breakfast.</p><h2>Luggage and vehicle</h2><p>Confirm the passenger count, luggage volume, child-seat needs, and whether a short stop is realistic. Keep the final transfer simple when the flight is important.</p><h2>Protect the route</h2><p>Use the <a href="sri-lanka-airport-transfers">airport transfer hub</a> for meet-and-greet and delay guidance, or ask for a complete private route that ends with the right airport buffer.</p>',
    '[{"question":"How long should I allow from Galle to Colombo Airport?","answer":"Use roughly 2–3 hours as a road planning baseline by expressway, then add a buffer for traffic, airport formalities, and the airline deadline."},{"question":"Should I stay in Galle before an early flight?","answer":"A closer airport or Colombo-area final night may reduce risk for an early departure. The right choice depends on the flight time, luggage, and tolerance for an early start."},{"question":"Can I stop on the way to the airport?","answer":"Only when the flight buffer remains comfortable. For important departures, keep the transfer direct and confirm the timing before adding stops."}]',
    'Plan a safe south-coast departure',
    'Send your flight time, Galle hotel, passenger count, and luggage details. We will help plan the vehicle and airport buffer.',
    'assets/images/home-hero/home-5.jpg',
    'Galle to Colombo Airport Transfer | Island Culture Travels',
    'Plan a private Galle to Colombo Airport transfer with realistic expressway timing, luggage guidance, and an appropriate flight buffer.',
    NULL, 457, 1
)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    content = VALUES(content),
    faq_json = VALUES(faq_json),
    cta_title = VALUES(cta_title),
    cta_text = VALUES(cta_text),
    hero_image = VALUES(hero_image),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description),
    canonical_url = VALUES(canonical_url),
    sort_order = VALUES(sort_order),
    is_published = VALUES(is_published);
