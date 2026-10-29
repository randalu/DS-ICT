SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;

-- =============================================================
-- ITINERARY: Sri Lanka Itinerary 10 Days (slug: sri-lanka-itinerary-10-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Ten days gives you the full Sri Lanka tour without the two-week commitment. Culture, hills, safari, coast — you hit every major region without feeling like you\'re racing the clock.',
    intro = 'Ten days is the sweet spot for a first Sri Lanka trip. It\'s not so short you\'re sprinting between regions, not so long you\'re eating into your leave balance. This route covers the Cultural Triangle, Kandy, tea country, Ella, a safari, and a south coast beach finish. You\'ll change hotels every 1-2 nights, but the drives are manageable — 3-4 hours max on transfer days. The car is your companion, not a punishment.

First-timers always ask if ten days is enough. Short answer: yes, if you move with purpose. You won\'t get deep into any one region, but you\'ll get a solid taste of everything Sri Lanka does well. The route works for couples, groups of friends, and families with older kids who can handle a morning drive.

The order matters: start with culture, climb through the hills, drop into the wildlife belt, and finish on the coast. That way the rhythm builds — each section adds something the last didn\'t have.',
    highlights = 'Sigiriya Rock at sunrise is the standout moment — get there before 7am and you\'ll have the top mostly to yourself. The drive from Kandy to Nuwara Eliya through winding tea plantations is better than any theme park ride. An afternoon elephant safari at Minneriya where you watch herds cross dried-up reservoir beds. Galle Fort in the late afternoon, when the light turns the old Dutch buildings gold and the cricket pitch fills with local kids.

The train ride from Ella to Kandy gets hyped, but honestly, the private car is often better — you can stop at waterfalls and viewpoints, and you\'re not fighting for a seat. Take the train only for the 2-hour scenic stretch between Nanu Oya and Ella if you want photos.',
    route_summary = 'Colombo/Negombo → Sigiriya/Habarana (Cultural Triangle) → Kandy → Nuwara Eliya (tea country) → Ella → Udawalawe/Yala (safari) → Galle/south coast → Colombo/airport. You loop clockwise from the airport, ending where you started.',
    travel_notes = 'The drive from Ella down to Udawalawe or Yala is about 4 hours on winding roads — not the day\'s highlight, but book a morning safari the next day and you\'ll reset fine. The hill country runs cooler than you expect (15-18°C at night in Nuwara Eliya), so pack a jacket even if you\'re coming from tropical beach weather. Roads in the Cultural Triangle are good; the Ella-to-safari stretch is where the surface gets patchy.

Safari jeeps are not included in transport packages — those are separate local bookings. If you\'re doing Yala, book the jeep at least a week ahead in peak season.',
    pricing_notes = 'Your biggest cost variable is hotels. Mid-range (USD 80-120/night) is fine for most of this route. The south coast beach hotels in Mirissa or Tangalle can go USD 150-250 if you want sea view. Safari jeeps run about USD 50-70 for a shared vehicle, USD 120-180 for a private one. Park entry fees are extra (around USD 25-35 per person per park). Fuel and chauffeur costs are baked into the package, so you\'re not nickel-and-diming on the road.',
    cta_text = 'Ready for the ten-day tour that actually covers the island without the burnout? Send your dates and we\'ll sketch the route.',
    faq_json = '[{"q":"Is ten days enough for Sri Lanka?","a":"It\'s the perfect first-trip length. You cover every major region without feeling like a mailbag. Want deeper dives? Go 14 days."},{"q":"Can I add more beach time?","a":"Swap one of the Ella days for an extra night on the south coast. Just know you\'ll lose the second hill-country day."},{"q":"Do I need to book the train in advance?","a":"If you want the Ella-Kandy scenic stretch, yes — tickets go fast, book 2-4 weeks ahead through your hotel or a local agent."},{"q":"What if I don\'t like long drives?","a":"The longest transfer is about 4 hours. Sri Lanka\'s small — nothing\'s truly far. We break up drives with stops."}]'
WHERE slug = 'sri-lanka-itinerary-10-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Itinerary 14 Days (slug: sri-lanka-itinerary-14-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Two weeks for the complete island circuit with proper beach rest, deeper heritage, and breathing room between every transfer. No single-night stands.',
    intro = 'Fourteen days is the goldilocks Sri Lanka trip — you\'ve got time to actually stay in places instead of just passing through. This route adds Anuradhapura for serious heritage depth, gives you two nights in Ella for proper hill-country relaxation, and finishes with multiple beach days you won\'t want to leave.

The extra days over the 10-day version let you slow down where it matters. Two nights at the Cultural Triangle instead of one. Two nights in Ella to actually do the hikes. Two or three nights on the coast. That means you\'re not packing and moving every single morning, which is the number one complaint from shorter trips.

Who\'s this for? First-timers who want the full picture. Couples who want culture plus beach time without rushing. Families who need buffer days. Anyone who hates the feeling of checking out after one night.',
    highlights = 'Anuradhapura at dawn — the scale of those dagobas is unreal when you\'re the only person there. The train from Kandy to Ella through tea country is worth the hype for exactly one segment (Nanu Oya to Ella, 2 hours, book first-class observation car). A private safari at Yala where you actually track a leopard for 40 minutes. Walking Galle Fort at sunset with zero agenda — just the ramparts, the ocean breeze, and a king coconut.

The best part that nobody tells you: the afternoon at a south coast beach hotel doing absolutely nothing. Two-week trips need those days.',
    route_summary = 'Airport/Negombo → Anuradhapura → Sigiriya/Habarana (2 nights) → Kandy → Nuwara Eliya (tea country) → Ella (2 nights) → Yala/Udawalawe → Tangalle/Hiriketiya → Galle → Bentota/Colombo → airport. Two-week loop covering every major distr',
    travel_notes = 'With two weeks, you can afford to add Anuradhapura without feeling rushed, but it\'s a long drive day from Negombo (about 5 hours). Consider spending the first night in Negombo and starting fresh the next morning. The hill country stretch (Kandy → Nuwara Eliya → Ella) spans 3 days of winding mountain roads — if anyone in your group gets carsick, stock up on meds before leaving Kandy.

The south coast roads from Tangalle toward Galle are well maintained. The expressway from Galle back to the airport takes about 1.5 hours, so your last day is stress-free.',
    pricing_notes = 'Over 14 days, hotel costs add up. Mid-range averages USD 80-120/night, but you\'ll want a nicer beach hotel for the final 2-3 nights — budget USD 150-250/night for that stretch. Entrance fees for Anuradhapura, Sigiriya, and Polonnaruwa run about USD 30 each per person. The train ticket is cheap (under USD 10 for first class) but hard to get. Safari costs are extra: jeep + park fees around USD 80-120 per person for Yala.',
    cta_text = 'Got two weeks and want to see it all properly? Drop your dates and we\'ll build a route that doesn\'t feel like a relay race.',
    faq_json = '[{"q":"Is 14 days too long for Sri Lanka?","a":"Not if you actually want to relax. Two weeks lets you see everything and still have beach days where you don\'t touch a car."},{"q":"Can I skip Anuradhapura?","a":"Sure, and use those two extra nights on the coast. Anuradhapura is for travellers who want ancient-city scale — it\'s not essential for a first trip."},{"q":"What\'s the best time of year?","a":"December to April for the south coast and Cultural Triangle. May to September still works but expect more rain in the hills."},{"q":"Do I need to pack for cold weather?","a":"Nuwara Eliya hits 12-15°C at night. Bring a fleece or light jacket. Everyone forgets this and ends up buying a questionable sweater at a roadside stall."}]'
WHERE slug = 'sri-lanka-itinerary-14-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Itinerary 5 Days (slug: sri-lanka-itinerary-5-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Five days for a sharp, focused Sri Lanka hit — culture, hills, and one region done well instead of three done badly.',
    intro = 'Five days in Sri Lanka means you\'ve got to be selective. You can\'t do the Cultural Triangle, hills, safari, AND coast — pick two, maybe three if they\'re close together. This route picks culture (Sigiriya/Dambulla) and hill country (Kandy/Nuwara Eliya) with a Colombo or Galle finish if timing allows.

The honest truth: this is a taster, not a full meal. You\'ll see the headline acts, but you won\'t linger. The route works best for business travellers tacking on a weekend, stopover passengers, or travellers combining Sri Lanka with India or the Maldives.

Fast-paced, efficient, and you need to arrive early on day one. Late arrivals lose half the itinerary.',
    highlights = 'Climbing Sigiriya Rock in the late afternoon when the crowds thin and the heat breaks. The drive up to Nuwara Eliya through Ramboda Pass with tea pickers in bright saris working the slopes. Temple of the Tooth in Kandy during the evening puja — the drumming echoes through the whole complex.

What you don\'t get time for: beaches, safaris, Galle Fort. Accept the trade-off and you\'ll still leave happy.',
    route_summary = 'Airport/Negombo → Sigiriya/Dambulla → Kandy → Nuwara Eliya (tea country) → Colombo/airport. A straight shot inland and back.',
    travel_notes = 'You absolutely need an early arrival flight. If you land after 2pm, day one is basically gone and you\'ll be playing catch-up the whole trip. The drive from Nuwara Eliya back to Colombo on day five takes about 5 hours via the expressway, so book an evening flight (after 6pm) to avoid rushing.

This route does not work well with public transport. Private car is essential for the pace.',
    pricing_notes = 'Shorter trip means fewer hotel nights — around USD 400-600 total for mid-range accommodation. The private car is your biggest fixed cost. Since there\'s no safari or train, your extra spending is limited to meals, entry fees (Sigiriya USD 30, Temple of Tooth USD 10), and tips.',
    cta_text = 'Short on time but still want the real Sri Lanka? Tell us your flight details and we\'ll build the tightest five-day route that actually works.',
    faq_json = '[{"q":"Can I really see Sri Lanka in 5 days?","a":"You can see the best bits of the hill country and culture circuit. You cannot see the whole island. Pick your priorities."},{"q":"Should I add a beach day?","a":"Only if you drop Nuwara Eliya. Swap hills for coast (Bentota) if that\'s more your style."},{"q":"Is this worth doing?","a":"If you have five days, yes. A focused route beats sitting in the airport hotel waiting for your layover to end."},{"q":"Can I include a safari?","a":"Not in five days unless you skip Kandy. The drive to a park and back eats a full day."}]'
WHERE slug = 'sri-lanka-itinerary-5-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Short Classic Itinerary 9 Days (slug: sri-lanka-short-classic-itinerary-9-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'The classic Sri Lanka circuit in nine days — Sigiriya, Kandy, tea country, safari, and a south-coast beach finish without the stretch marks.',
    intro = 'Nine days is what most people\'s vacation allowance looks like, and this route is built around that reality. You get the Cultural Triangle, Kandy, the hill country, a safari, and Bentota beach time — a complete island sweep without the two-week price tag.

The trade-off: you move every 1-2 nights. That\'s fine for most travellers, but if you hate packing and unpacking, consider dropping one region and staying put somewhere for two nights. The route is flexible that way — swap Bentota for an extra Ella night if the hills are calling.

We built this for first-time visitors who want the Instagram highlights but also want to understand why people come back to Sri Lanka. It\'s the greatest-hits album with one B-side that steals the show.',
    highlights = 'The morning climb up Sigiriya when the mist is still burning off the plains — you see why a king built his palace there. The safari at Minneriya where elephants migrate in herds of 100+ during the dry season (June-September). Bentota beach at sunset with a toddy from a roadside shop — cheap, local, unforgettable.

The train ride from Kandy to Nuwara Eliya isn\'t the famous Ella stretch, but the views are just as good and there are fewer tourists.',
    route_summary = 'Airport → Negombo → Sigiriya/Habarana → Kandy → Nuwara Eliya → Bentota → Colombo/airport. A straightforward clockwise loop with the coast finish.',
    travel_notes = 'The safari on day three works best June-September when Minneriya\'s elephants congregate. Outside those months, we\'ll redirect to Kaudulla or a shorter park visit. The drive from Nuwara Eliya all the way to Bentota takes about 5-6 hours — it\'s a proper travel day, so start early (7am departure) and you\'ll be at the beach by lunch.

Bentota\'s beach is beautiful but the currents can be strong. Swim where the locals do, not where the hotel says.',
    pricing_notes = 'Nine nights of mid-range hotels: roughly USD 700-1,000 total. The Bentota beach hotel will be your priciest night at USD 120-200. Safari jeep + park entry around USD 70-90 per person. Train is optional and cheap (USD 5-10). Everything else is minor — meals in Sri Lanka are affordable (USD 5-15 per person at local restaurants).',
    cta_text = 'Nine days, classic route, zero fluff. Send your travel dates and we\'ll put together a complete package.',
    faq_json = '[{"q":"Can I swap Bentota for Mirissa?","a":"Yes, but it adds 1-2 hours of driving on day seven. Mirissa is worth it for whale watching season (November-April)."},{"q":"Is the train ride included?","a":"Not automatically. We can add it, but you\'ll need to book tickets 2-4 weeks ahead."},{"q":"What if I don\'t want a safari?","a":"Swap it for a Polonnaruwa day or arrive at Bentota earlier. Your call — the itinerary\'s private."},{"q":"Is this good for families?","a":"Yes, with older kids (10+). Younger children might struggle with the moving pace. Consider the family itinerary instead."}]'
WHERE slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Culture, Hills and Safari Itinerary 12 Days (slug: sri-lanka-culture-hills-safari-itinerary-12-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'A twelve-day deep dive that stitches together the Cultural Triangle, tea country, Ella, Yala safari, and Galle with proper pacing.',
    intro = 'This is the route for travellers who want depth without the full 14-day commitment. Twelve days gets you two nights in most places, which means you\'re not packing every morning. You\'ll explore Sigiriya properly, take the scenic train to Ella, track leopards at Yala, and walk Galle Fort — all on a schedule that leaves room for spontaneity.

The big difference from shorter routes: you get a second night in Sigiriya (so you can do both the rock and a safari), two nights in Ella (so you can actually hike), and two nights on the coast (so you arrive home actually rested instead of exhausted).

If you\'re the type who reads trip reports before booking and cares about the difference between Kaudulla and Minneriya, this itinerary is for you.',
    highlights = 'The early morning at Sigiriya when the only sounds are bird calls and your footsteps. The train ride from Nanu Oya to Ella — 2 hours of pure Sri Lanka, with tea plantations dropping into valleys and tunnels cutting through mountains. That moment at Yala when the tracker whispers \"leopard\" and everyone in the jeep goes silent.

The Galle Fort walk at golden hour deserves its own category — it\'s not a sight, it\'s a mood. You\'ll understand why people buy houses there.',
    route_summary = 'Negombo → Sigiriya/Habarana (2 nights) → Kandy → Nuwara Eliya → Ella (2 nights) → Yala → Galle → Colombo/airport. A balanced circuit with two-night anchors at the big stops.',
    travel_notes = 'The train from Nanu Oya to Ella needs advance booking — first-class observation car sells out weeks ahead. If you can\'t get tickets, the private drive is actually scenic too, just stop at Ravana Falls on the way. Yala safaris start at 5am — it\'s early but that\'s when the animals are active. Don\'t book a late checkout for that day; you won\'t use it.

The drive from Ella down to Yala is about 4 hours on twisty roads. Your driver knows the route, but if you get carsick, sit in the front passenger seat.',
    pricing_notes = 'Twelve days of mid-range to upscale hotels: USD 900-1,500 total. The safari at Yala is the biggest single extra — jeep (USD 60-80 shared, USD 130-180 private) plus park fees (USD 25-35 per person). Train tickets: USD 5-15. Entry fees: Sigiriya USD 30, Temple of Tooth USD 10. Budget about USD 40-60 per day for meals for two people at local restaurants.',
    cta_text = 'Twelve days, every region, no compromises. Let us know your dates and we\'ll map out the full route.',
    faq_json = '[{"q":"How does this differ from the 10-day itinerary?","a":"Extra nights in Sigiriya, Ella, and the coast. That means more time for activities and less time packing."},{"q":"Can I swap Yala for Udawalawe?","a":"Yes. Yala\'s better for leopards, Udawalawe\'s better for elephants and easier to reach from Ella. Depends on what you want to see."},{"q":"Do I need a guide for Sigiriya?","a":"Not required, but the on-site guides add context that makes the climb better. Arrange at the entrance."},{"q":"What about the monsoon?","a":"October-November is the wettest. December-March is ideal for this route. April-September works too with some rain in the hills."}]'
WHERE slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Short Escape 5 Days (slug: sri-lanka-short-escape-5-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'A punchy five-day sampler for quick getaways — Sigiriya, Kandy, and your choice of hills or beach. Optimised for efficiency.',
    intro = 'Same duration as the 5-day itinerary, different emphasis. This one gives you more flexibility on the finish — choose between tea country (Nuwara Eliya) for cool mountain air or Bentota beach for sand and sunset.

Let\'s be honest: five days is enough to get hooked, not enough to see everything. What this route does well is front-load the best stuff (Sigiriya, Dambulla, Kandy) and then let you pick your ending based on your mood. The private vehicle means you\'re not waiting for buses or negotiating with tuk-tuk drivers.

Ideal for Singapore and Middle East travellers doing a short hop, or anyone adding Sri Lanka onto a longer Asia trip.',
    highlights = 'Sigiriya Rock Fortress when the sun hits the western face at 4pm and the whole structure glows orange. Dambulla Cave Temple\'s ceiling — 2,000 square metres of Buddhist murals that have survived for 22 centuries. The drive from Kandy to Nuwara Eliya if you choose the hills — waterfalls every 10 minutes.

The Bentota finish is the lazy option and honestly, it\'s the better call if you want to actually relax before flying home.',
    route_summary = 'Airport → Negombo → Sigiriya/Dambulla → Kandy → Nuwara Eliya (hills) OR Bentota (beach) → Colombo/airport. Two paths, one great short trip.',
    travel_notes = 'Key constraint: arrival time. If you land after 2pm, skip Sigiriya on day two and do Dambulla instead — Sigiriya closes at 5pm and you won\'t make it. The hill country choice means a 4-hour drive back to Colombo on day five. The beach choice is only 1.5 hours from Bentota. Choose accordingly.

Pack light. With a 5-day trip and hotel changes, a 40L backpack beats a suitcase.',
    pricing_notes = 'Four nights accommodation — roughly USD 320-500 total for mid-range hotels. Sigiriya entrance is USD 30, Dambulla is USD 10. The private car and driver is your main cost. Meals for two at local restaurants run USD 15-30 per day.',
    cta_text = 'Short break, big experience. Share your flight details and we\'ll custom-build this five-day Sri Lanka route for you.',
    faq_json = '[{"q":"Hill country or beach — which should I choose?","a":"If you\'re from a cold country, choose the beach. If you\'re from a tropical country, choose the hills. The contrast is what matters."},{"q":"Can I do both hill country AND beach in 5 days?","a":"Technically yes, but you\'ll spend 8 hours driving on the last day. Not recommended."},{"q":"What if my flight lands late?","a":"We adjust — swap day one\'s Negombo stay for a Colombo city hotel and start fresh the next morning."},{"q":"Is this suitable for solo travellers?","a":"Yes. Solo supplement on the room is the only extra. The private car is the same price for 1-3 people."}]'
WHERE slug = 'sri-lanka-short-escape-5-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Itinerary 7 Days (slug: sri-lanka-itinerary-7-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'One week, three regions, no filler. Culture, hill country, and your pick of safari or beach — but not both at full strength.',
    intro = 'Seven days forces hard choices. You can do culture + hills + safari, or culture + hills + beach, but you cannot do culture + hills + safari + beach without turning half your holiday into car time. This route picks the best combination: Cultural Triangle, Kandy, Ella or Nuwara Eliya, and then either Udawalawe (safari) or Galle (beach/heritage).

The seven-day traveller is usually someone with a job they can\'t abandon for two weeks. This route respects that — you get the core Sri Lanka experience without feeling like you missed everything. You\'ll miss some things, sure, but you\'ll see enough to know you\'re coming back.

We recommend choosing safari over beach if it\'s your first trip. You\'ve seen beaches before. You haven\'t watched an elephant family cross the road.',
    highlights = 'The approach to Sigiriya — you see it from 5km away, rising out of the flat plains like a mirage. Kandy\'s Temple of the Tooth during evening puja when the drums start and the air fills with frangipani and incense. Ella Gap view from Little Adam\'s Peak around 4pm when the light hits the valley.

If you choose the safari route: Udawalawe\'s elephant sightings are almost guaranteed — it\'s not Yala drama, but you will see elephants, usually within the first 30 minutes.',
    route_summary = 'Airport/Negombo → Sigiriya/Habarana → Kandy → Nuwara Eliya/Ella → Udawalawe (safari) OR Galle (beach) → Colombo/airport. One decision point at day five changes the whole finish.',
    travel_notes = 'The decision point at day five matters geographically: Udawalawe is inland from the south coast, Galle is on the coast. If you choose safari, your final day drive to Colombo is about 3 hours via expressway. If you choose beach, it\'s 1.5 hours from Galle. Factor this into your departure flight booking.

The hill country section works best if you spend night five in Ella rather than Nuwara Eliya — Ella gets more sun and has better cafe options.',
    pricing_notes = 'Six nights accommodation at mid-range: USD 480-720. Safari adds USD 60-90 per person for jeep and entry. The beach option has no extra activity costs unless you want whale watching (Mirissa, Nov-April, USD 40-60 per person). Entry fees: Sigiriya USD 30, Temple of Tooth USD 10.',
    cta_text = 'One week, maximum experience. Send your plans and we\'ll build a 7-day route that fits your style.',
    faq_json = '[{"q":"Safari or beach — which is better?","a":"Safari if you like wildlife or haven\'t been on an African safari. Beach if you want to actually swim in the Indian Ocean."},{"q":"Can I visit Sigiriya AND Pidurangala?","a":"You can do both in one morning — Pidurangala at sunrise (USD 5 entry), then Sigiriya at 9am when the crowds arrive. Exhausting but worth it."},{"q":"What\'s the driving time like?","a":"3-4 hours between each stop. Nothing brutal, but you\'re moving every day. This is not a stay-put holiday."},{"q":"Is Ella worth the detour?","a":"Yes. Ella is touristy but the scenery is legit. Nine Arch Bridge, the cafes, the valley views — it delivers."}]'
WHERE slug = 'sri-lanka-itinerary-7-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Itinerary 12 Days (slug: sri-lanka-itinerary-12-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'A well-paced twelve days covering every signature Sri Lanka experience — culture, tea country, Ella, safari, Galle, and beach time with two-night stays.',
    intro = 'This twelve-day route solves the biggest problem with week-long trips: you\'re always checking out. Here, you get two nights in the Cultural Triangle, two nights in Ella, and two nights on the coast. That means laundry, rest, and time to actually absorb a place before moving on.

The route covers the full island sweep: arrive at the airport, head straight for the Cultural Triangle, work your way through Kandy and tea country, down through Ella to the safari belt, then across the south coast to Galle for a beach finish. It\'s a clockwise sweep that maximises daylight and minimises backtracking.

This is our best-selling length for a reason. Ten days feels slightly rushed. Fourteen feels indulgent. Twelve is the Goldilocks zone.',
    highlights = 'An afternoon at Minneriya watching elephants during the Great Gathering (June-September) — you\'ll see herds of 50+ moving across the reservoir floor. The Nanu Oya to Ella train ride — book the observation car, bring snacks, and get ready for 2 hours of tea plantations and mountain tunnels. Galle Fort at 6am when the street dogs are still sleeping and the only person around is the newspaper delivery guy.

The food highlight nobody plans for: a roadside kottu roti stop at 10pm in Ella. It\'s messy, spicy, perfect.',
    route_summary = 'Airport/Negombo → Sigiriya/Habarana (2 nights) → Kandy → Nuwara Eliya → Ella (2 nights) → Yala/Udawalawe → Galle/south coast (2 nights) → Colombo/airport. Full loop with anchors at the big stops.',
    travel_notes = 'The two-night stays help, but you still have 4-5 hotel changes over 12 days. Pack with that in mind — separate a change of clothes for easy access. The Yala safari requires a 5am start; arrange breakfast packs with your hotel the night before. Don\'t rely on the hotel restaurant being open at that hour.

The expressway from Galle to the airport is efficient (1.5 hours) but boring. Your driver will appreciate it after the hill country roads.',
    pricing_notes = 'Eleven nights mid-range to upscale: USD 900-1,400. Safari jeep + park fees: USD 80-120 per person. Train: USD 5-15. Galle Fort walk is free. Whale watching in Mirissa (Nov-April): USD 40-60 per person. Budget about USD 50-70 per day for meals for two.',
    cta_text = 'Twelve days of Sri Lanka done right. Tell us your dates and we\'ll craft the full itinerary.',
    faq_json = '[{"q":"What\'s different from the 10-day version?","a":"Two-night stays in Sigiriya, Ella, and the coast. That\'s the difference — less packing, more relaxing."},{"q":"Can I add whale watching?","a":"Yes, on the south coast days. November-April season only. Mirissa is the main departure point."},{"q":"Should I book train tickets in advance?","a":"Absolutely. The observation car sells out. Book 3-4 weeks ahead through your hotel."},{"q":"What\'s the best safari park?","a":"Yala for leopard drama, Udawalawe for guaranteed elephants, Bundala for birders. Choose your priority."}]'
WHERE slug = 'sri-lanka-itinerary-12-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Honeymoon Itinerary 12 Days (slug: sri-lanka-honeymoon-itinerary-12-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'A romance-first route built for couples — boutique hotels, slower pacing, sunset viewpoints, and zero early-morning rush.',
    intro = 'This is not the standard Sri Lanka tour with a bottle of champagne thrown in. It\'s a fundamentally different route: fewer stops, better hotels, more time to actually be together.

We trimmed the hotel changes to 6 moves over 12 days. That means you spend 2-3 nights at the best properties — a Sigiriya jungle lodge where peacocks wake you up, a tea bungalow where you drink Ceylon tea on your private veranda, a safari camp with candlelight dinners, and a Galle Fort villa with a four-poster bed.

The route covers the same regions as the classic 12-day but with curated experiences: private sunrise at Pidurangala instead of crowded Sigiriya, a personal tea tasting instead of a factory tour, a private safari jeep so you don\'t share the moment with strangers.

For couples who care more about the hotel than the sightseeing list. We\'re not judging — that\'s exactly what a honeymoon should be.',
    highlights = 'Pidurangala at sunrise with nobody else around — just you, the rock, and 360-degree views of the jungle plains below. The tea bungalow evening when the mist rolls in and you\'re drinking single-estate Ceylon on a colonial veranda. A private Yala safari where your driver actually stops for photo angles instead of rushing to the next leopard sighting.

The Galle Fort evening walk that turns into dinner at a rooftop restaurant overlooking the Indian Ocean. You\'ll text your friends a photo and they\'ll hate you for it.',
    route_summary = 'Airport → Sigiriya jungle lodge (2 nights) → Kandy → tea country bungalow (2 nights) → Ella → safari lodge (2 nights) → Tangalle/Hiriketiya → Galle Fort boutique (2 nights) → airport. Slower, fewer moves, better beds.',
    travel_notes = 'The luxury version of this route uses a premium vehicle (SUV or minivan with WiFi and chilled water). The tea bungalows near Nuwara Eliya book out months ahead — if you\'re planning a honeymoon, start the booking process 4-6 months in advance. Safari lodges near Yala are expensive (USD 250-500/night) but the experience is night-and-day better than a standard hotel.

Pidurangala requires a 20-minute scramble over boulders at the end. Wear proper shoes. Sigiriya is easier but more crowded — for a honeymoon, do Pidurangala instead.',
    pricing_notes = 'Honeymoon pricing is higher because the properties are better. Budget USD 200-400/night for accommodation. A private safari jeep runs USD 130-180. Private guide for Pidurangala or Sigiriya: USD 50-80. The tea bungalow tasting sessions are usually complimentary with your stay. Expect total accommodation cost around USD 2,000-3,500 for 11 nights.',
    cta_text = 'Planning the Sri Lanka leg of your honeymoon? Tell us your dates and we\'ll design a route built around romance, not checklists.',
    faq_json = '[{"q":"Do we need a photographer?","a":"We can arrange one. Your driver will also know the best photo spots — these guys have seen every sunrise."},{"q":"Can we upgrade to a luxury vehicle?","a":"Yes. Suggest a Toyota KDH van or similar SUV for comfort on winding roads. Extra cost but worth it."},{"q":"Is Yala safe for a honeymoon?","a":"Perfectly safe. The safari lodge is fenced and guarded. You\'ll hear jackals at night — that\'s part of the experience."},{"q":"What if we don\'t want to share safari jeeps?","a":"Private jeeps are standard in our honeymoon package. You won\'t share with strangers."}]'
WHERE slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Family Itinerary 10 Days (slug: sri-lanka-family-itinerary-10-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Designed for families with kids — shorter drives, hotels with pools, wildlife they\'ll actually remember, and a beach finish everyone needs.',
    intro = 'Family travel in Sri Lanka is different. You can\'t drag a six-year-old through four UNESCO sites in a day and call it a holiday. This route accepts that reality and builds around it: shorter drives (max 3 hours per transfer), hotels with pools (non-negotiable), wildlife that\'s actually exciting for kids, and a beach finish where everyone crashes into the water.

The itinerary cuts some cultural depth in favour of experiences kids engage with. Dambulla Cave Temple? Keep it short. Minneriya elephant safari? That\'s the highlight of their year. Kandy\'s Temple of the Tooth? Fifteen minutes, then ice cream by the lake.

We\'ve been doing this long enough to know: the family trip succeeds or fails on pacing, not on how many sights you tick off. This route prioritises the kids\' energy curve.',
    highlights = 'The elephant safari at Minneriya — kids genuinely lose their minds seeing elephants in the wild. Udawalawe Elephant Transit Home where you watch baby elephants being bottle-fed (public feeding at 9am, 12pm, 3pm). The beach in Bentota where the water\'s calm enough for swimming and there\'s always a vendor selling fresh king coconuts.

The Unawatuna beach afternoon where the kids build sandcastles while you drink a Lion Lager and pretend to read a book. That\'s the real family holiday.',
    route_summary = 'Airport → Negombo → Sigiriya/Habarana (2 nights) → Kandy → Udawalawe → Galle/Unawatuna → Bentota (2 nights) → airport. Fewer stops, longer stays where it counts.',
    travel_notes = 'Hotels with pools are not optional on this route — we pick properties with at least one pool, preferably with a shallow end for small kids. The safari at Udawalawe is better for families than Yala because the elephants are more visible and less skittish. Yala requires patience and silence, two things kids don\'t do well.

Restaurants in tourist areas have kids\' menus, but local Sri Lankan restaurants will happily make a mild rice and curry. Just ask for \"less spicy.\" Pack mosquito repellent with DEET — the coast has more mosquitoes than the hills.',
    pricing_notes = 'Families need bigger vehicles and often two connecting rooms or family suites. Budget USD 100-200/night for accommodation. The safari is cheaper per person shared (about USD 50-70 per person for jeep + park fees). Kids under 6 often get free or half-price entry at attractions. The Elephant Transit Home entry is about USD 10 per person. A bigger vehicle (minivan) costs more than a standard car — factor in an extra USD 15-20 per day.',
    cta_text = 'Planning a Sri Lanka trip with the kids? Share your family\'s ages and interests — we\'ll build a route that keeps everyone happy.',
    faq_json = '[{"q":"Is Sri Lanka safe for young children?","a":"Yes. The main concerns are mosquito-borne illnesses (use repellent), sun exposure (hats and sunscreen), and road safety (seat belts in the private vehicle)."},{"q":"What\'s the best age for this trip?","a":"6-14 is ideal. Under 6, consider the family itinerary with more pool days. Over 14, they can handle the adult pace."},{"q":"Do we need special car seats?","a":"Our vehicles have seat belts. We can arrange child seats if you request them in advance."},{"q":"What if my kids get carsick?","a":"Sri Lankan roads are winding in the hill country. Talk to your doctor about motion sickness meds before you go. Front passenger seat helps."}]'
WHERE slug = 'sri-lanka-family-itinerary-10-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Wildlife Itinerary 9 Days (slug: sri-lanka-wildlife-itinerary-9-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Built for wildlife obsessives — Minneriya elephants, Udawalawe, Yala leopards, and Bundala birds across three distinct habitats in nine days.',
    intro = 'This is a specialist route for people who care more about animal sightings than temple visits. You\'ll still pass through cultural areas, but the focus is relentlessly on wildlife: dry-zone elephants at Minneriya, herds at Udawalawe, leopards at Yala, and wetland birds at Bundala.

The route positions you near the best parks at the right time of day. Morning safaris at Yala (5am start), afternoon drives at Minneriya (when elephants gather), and a full day at Udawalawe covering both the park and the Elephant Transit Home.

If your idea of a good holiday includes binoculars, a field guide, and the patience to sit still for an hour watching a watering hole, this is your itinerary. If you\'re a casual wildlife fan, the standard routes already include a safari — you don\'t need this one.',
    highlights = 'Minneriya National Park between June and September when the Great Gathering brings hundreds of elephants to the reservoir — it\'s one of the best wildlife spectacles in Asia. A dawn drive at Yala where you see a leopard silhouette against the rising sun. Udawalawe\'s consistent elephant sightings — you\'ll see 30-50 in a single morning drive.

Bundala for birders: flamingos in season, painted storks, and a dozen raptor species. For non-birders, the crocodiles are the main attraction.',
    route_summary = 'Airport → Habarana/Sigiriya (2 nights) → Udawalawe (2 nights) → Yala/Tissamaharama (2 nights) → Galle/Tangalle → airport. Three distinct wildlife zones connected by reasonable drives.',
    travel_notes = 'This route requires early starts (5am safari pickups) multiple days in a row. It\'s not a relaxing holiday — it\'s an expedition. Pack binoculars (don\'t rely on your phone camera), a good camera lens (200mm+), neutral-coloured clothing, and a torch. The safari jeeps are open-air and get dusty. Leave your white clothes at home.

The best wildlife season in Yala is February-July. Minneriya peaks June-September. If you\'re travelling outside these windows, wildlife volume will be lower but still good.',
    pricing_notes = 'Wildlife itineraries cost more because of park fees and safari jeeps. Budget USD 50-80 per safari for shared jeeps, USD 120-180 for private. Park entry fees USD 25-35 per person per park. With 4-5 safaris over 9 days, that adds up to USD 200-400 per person in wildlife costs alone. Accommodation near parks (especially Yala) runs higher than city hotels — USD 100-200/night.',
    cta_text = 'Serious about Sri Lankan wildlife? Tell us your target species and we\'ll design a safari route built around the best sighting windows.',
    faq_json = '[{"q":"Will I definitely see a leopard at Yala?","a":"Nothing is guaranteed with wildlife. But Yala has the highest leopard density in the world. Morning drives give you the best odds."},{"q":"Can I add Sinharaja rainforest?","a":"Only if you have 12+ days. Sinharaja is on the other side of the island. It\'s a day\'s drive from Yala."},{"q":"What camera gear do you recommend?","a":"A DSLR or mirrorless with a 200-400mm lens. Phone cameras won\'t do justice to leopards 50 metres away."},{"q":"Are the elephants guaranteed at Minneriya?","a":"June-September, yes. The Great Gathering is a natural phenomenon where 200-300 elephants gather. Outside those months, Kaudulla is the safer bet."}]'
WHERE slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka South Coast Itinerary 8 Days (slug: sri-lanka-south-coast-itinerary-8-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'All coast, all relaxed. Galle Fort, Unawatuna beaches, Mirissa whale watching or surf, Tangalle, and an optional safari day — zero Cultural Triangle.',
    intro = 'Not every Sri Lanka trip needs Sigiriya. The south coast is a world of its own — Dutch colonial forts, crescent-shaped beaches, whale watching, surf breaks, and marine life. This itinerary skips the Cultural Triangle entirely and focuses on the island\'s southwest coast.

You start in Galle, work your way east through Unawatuna, Weligama, Mirissa, and Tangalle, and add a safari at Udawalawe or Yala if you want a wildlife day. The pace is slower, the drives are shorter, and the dress code is flip-flops.

Perfect for beach lovers, casual surfers, second-time visitors who\'ve already done Sigiriya, and anyone who defines a good holiday by how little they need to think about logistics.',
    highlights = 'Walking Galle Fort at 7am before the tourist buses arrive — you\'ll have the ramparts to yourself and the Indian Ocean on both sides. Mirissa whale watching (November-April) where you see blue whales, the largest animals on earth, surfacing 100 metres from your boat. The surf at Weligama — long, gentle breaks perfect for beginners.

Hiriketiya Beach is the curveball — a natural bay shaped like a perfect horseshoe, with decent waves for bodyboarding and a handful of cafes that do great avo toast.',
    route_summary = 'Airport → Galle/Unawatuna (2 nights) → Weligama/Mirissa (2 nights) → Tangalle/Hiriketiya → Udawalawe safari (optional) → Colombo/airport. A beach crawl with a wildlife option.',
    travel_notes = 'This route only works well between November and April. May to October, the southwest monsoon hits the coast hard — rough seas, closed whale watching, and beach cleanup operations. If you\'re travelling in the off-season, consider the east coast instead.

Whale watching is weather-dependent. Even in peak season, trips get cancelled if the swell is too high. Build a buffer day if this is your priority. The surf breaks at Weligama are year-round but gentler in the high season.',
    pricing_notes = 'South coast accommodation is generally cheaper than Sigiriya or Yala. Mid-range hotels: USD 60-120/night. Whale watching boat tour: USD 40-60 per person. Surf lesson: USD 15-25 per person for a 2-hour session. The optional safari adds USD 60-90 per person. Food on the south coast is excellent and cheap — seafood, roti, fruit, and rice and curry.',
    cta_text = 'Beach, surf, whales, and Galle Fort — all coast, no rush. Send your dates and we\'ll plan your south coast escape.',
    faq_json = '[{"q":"No Sigiriya? No Cultural Triangle?","a":"Correct. This route is for south coast lovers. If you want culture too, look at the 10-day or 12-day itineraries."},{"q":"When is whale watching season?","a":"November to April. Best months are January-March when the sea is calmest."},{"q":"Is this good for beginner surfers?","a":"Weligama is one of the best beginner surf breaks in Asia. Gentle, long waves and plenty of surf schools."},{"q":"Can I add a safari without going to Yala?","a":"Yes — Udawalawe is a 2-hour drive from Tangalle and has reliable elephant sightings. Perfect for a day trip."}]'
WHERE slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka East Coast Itinerary 10 Days (slug: sri-lanka-east-coast-itinerary-10-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'A seasonal east coast route — Sigiriya and Polonnaruwa culture, then Trincomalee, Passikudah, or Arugam Bay beach time. Best from May to September.',
    intro = 'Most Sri Lanka itineraries focus on the south and west coasts. The east coast is a different animal: bigger beaches, wilder surf, better marine life, and a fraction of the tourists. This route pairs the Cultural Triangle (Sigiriya, Polonnaruwa, Dambulla) with the east coast\'s best beaches.

The catch: it\'s seasonal. The east coast shines from May to September when the southwest monsoon pounds the south coast but the east gets flat seas and blue skies. October to April, the east coast gets its own monsoon — rougher seas and patchy weather.

The route gives you the best of both Sri Lankas: the ancient cities of the dry zone and the laid-back beach culture of the east. Trincomalee for snorkelling and coral reefs, Passikudah for resort-style relaxation, or Arugam Bay for serious surf.',
    highlights = 'Polonnaruwa at 8am before the heat and crowds — you can cycle between ruined palaces and Buddha statues with barely another tourist in sight. Pasikuda Beach\'s shallow, flat water that stretches 100 metres out — like swimming in a warm swimming pool. Arugam Bay\'s main surf point in July when the swell is consistent but not terrifying.

Pigeon Island off Nilaveli — snorkelling over coral with reef sharks, turtles, and a riot of tropical fish. The boat ride out is 20 minutes and costs pennies.',
    route_summary = 'Airport → Sigiriya/Habarana (2 nights) → Polonnaruwa → Trincomalee/Nilaveli (2 nights) → Passikudah/Batticaloa → Arugam Bay (optional) → inland return via Kandy → airport. A figure-eight that loops inland then heads east.',
    travel_notes = 'The drive from Nilaveli down to Passikudah takes about 2 hours on a decent road. Passikudah to Arugam Bay is 3+ hours on roads that vary from good to potholed — budget the time. The inland return from Arugam Bay to the airport takes 7-8 hours if you go direct, so we\'ve added a Kandy stop to break it.

This route has fewer hotel options than the south coast. Booking early is essential, especially in July-August when European tourists fill the east coast resorts. The sea at Nilaveli has strong currents in places — swim within the marked areas.',
    pricing_notes = 'East coast accommodation is generally cheaper than the south coast except in peak season (July-August). Mid-range: USD 50-100/night. Snorkelling trips to Pigeon Island: USD 20-30 per person. Surf lessons at Arugam Bay: USD 10-15 per session. Polonnaruwa entry: USD 25 per person. The inland return via Kandy adds a night of accommodation and a day of driving costs.',
    cta_text = 'Ready for Sri Lanka\'s uncrowded east coast? Tell us your dates (May-September preferred) and we\'ll build a route that pairs culture with the coast.',
    faq_json = '[{"q":"When should I visit the east coast?","a":"May to September is the sweet spot. The weather\'s dry, the sea\'s calm, and the beaches are at their best."},{"q":"Can I combine east and south coast?","a":"Not in 10 days unless you fly domestically. Driving around the island takes 2 full days."},{"q":"Is Arugam Bay only for surfers?","a":"No, but it helps. The bay has a great backpacker vibe, good food, and beaches even if you don\'t surf."},{"q":"Which east coast beach is best?","a":"Nilaveli for snorkelling, Passikudah for resort relaxation, Arugam Bay for surf and social scene."}]'
WHERE slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Luxury Itinerary 11 Days (slug: sri-lanka-luxury-itinerary-11-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Premium Sri Lanka with fewer hotel changes, private guides, better rooms, and a pace designed for people who don\'t do rushed.',
    intro = 'Luxury in Sri Lanka isn\'t about gold taps and marble lobbies. The best luxury here is solitude — a jungle lodge where your only neighbours are monkeys, a tea bungalow that sleeps six guests total, a safari camp with canvas walls and hot running water, and a Galle Fort villa with a private plunge pool.

This route strips out the filler. Fewer stops (7 hotel changes over 11 days means 2-3 nights at the best properties), private guides at every cultural site, a premium vehicle with refreshments, and experiences that force you to slow down instead of speed up.

We\'ve designed the pacing so you never check out after one night except for the airport arrival. Everything else is 2-3 nights. You\'ll actually relax, which is the whole point of a luxury trip.',
    highlights = 'A private sunrise at Sigiriya with a guide who knows every carving and fresco — and knows when to shut up and let you look. Afternoon tea at a restored colonial bungalow in Hatton, sipping single-estate Ceylon while the mist rolls through the valley. A private safari jeep at Yala where the driver circles back when the tracker spots something instead of radioing the other jeeps.

Dinner at the Galle Fort Hotel\'s courtyard — candlelit, colonial arches, Sri Lankan fusion food that actually surprises you. Reserve the corner table.',
    route_summary = 'Airport → Colombo boutique → Sigiriya jungle lodge (2 nights) → tea country bungalow (2 nights) → safari lodge/Yala (2 nights) → Galle Fort boutique (2 nights) → beach resort → airport. Seven hotels, five regions, eleven days.',
    travel_notes = 'The tea bungalows book out months in advance — November to April, you\'re competing with honeymooners and retirement travellers. Lock these in early. Safari lodges near Yala are not cheap but they\'re worth the premium — you get guided walks, sundowners in the bush, and dinner under the stars. The private guides for Sigiriya and Galle Fort are arranged separately; we recommend booking the same guide for both for continuity.

The premium vehicle (a Toyota KDH or similar) makes a real difference on the Kandy-to-Nuwara Eliya winding roads. The extra legroom and suspension matter.',
    pricing_notes = 'This is the expensive option. Budget USD 300-600/night for accommodation. Private guides: USD 80-120 per day. Private safari jeep: USD 150-200. Premium vehicle upgrade: USD 30-50 per day extra. Expect total accommodation costs of USD 3,500-6,500. Meals at boutique properties are also pricier — USD 50-100 per person for dinner with drinks.',
    cta_text = 'Want Sri Lanka done properly — private guides, premium stays, zero compromises? Tell us your style and we\'ll create a luxury route that\'s worth every cent.',
    faq_json = '[{"q":"What makes this luxury vs the standard route?","a":"Better hotels (2-3 nights each), private guides, a premium vehicle, and a pace that never feels rushed."},{"q":"Can I book my own flights separately?","a":"Yes. We handle the ground arrangements. Just give us your flight details and we\'ll plan arrival and departure timing."},{"q":"Are the safari lodges inside the park?","a":"Some are outside the park boundary (still in the buffer zone). True in-park lodges are rare and very expensive. We\'ll recommend the best available."},{"q":"Do I need to tip separately?","a":"Guide tips are at your discretion (USD 10-20 per day for good service). Driver tips are separate. Most boutique hotels include service charges."}]'
WHERE slug = 'sri-lanka-luxury-itinerary-11-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

-- =============================================================
-- ITINERARY: Sri Lanka Birding and Nature Itinerary 12 Days (slug: sri-lanka-birding-nature-itinerary-12-days)
-- =============================================================
UPDATE itineraries
SET excerpt = 'Sri Lanka\'s best birding and nature habitats across rainforest, dry-zone, wetlands, and highlands — Sinharaja, Udawalawe, Bundala, Yala, and Horton Plains.',
    intro = 'Sri Lanka punches way above its weight for biodiversity. This island has 34 endemic bird species, leopards, elephants, and the highest density of amphibians in Asia. This twelve-day route connects the best habitats: Sinharaja rainforest (endemic birds, snakes, frogs), Udawalawe (elephants, raptors, waterbirds), Bundala (migratory waders, flamingos), Yala (leopards, dry-zone species), and Horton Plains (highland endemics).

It\'s not a relaxing holiday. It\'s a naturalist\'s expedition — early starts, long days in the field, and a Latin name for everything you see. You\'ll need a good local guide (we\'ll arrange one), decent binoculars, and the patience to stand still in a rainforest for 20 minutes waiting for a Sri Lanka blue magpie to show itself.

For serious birders, this is one of the best short-haul destinations in the world outside the Neotropics. For general nature lovers, it\'s a crash course in why this island fascinates biologists.',
    highlights = 'Sinharaja at dawn with a local guide who can whistle back at the birds — hearing the rainforest wake up is worth the 5am start. The Sri Lanka blue magpie (endemic, stunning, and surprisingly bold around the Sinharaja research station access road). Bundala\'s lagoons in January covered in flamingos, pelicans, and open-billed storks. Horton Plains in the morning — walking through misty cloud forest to World\'s End, the sheer drop where the plains end and the tea country begins.

Udawalawe is the reliable one — you\'ll get multiple elephant sightings plus steppe eagles, crested serpent eagles, and the occasional fishing cat if you\'re lucky.',
    route_summary = 'Airport → Sinharaja/Ratnapura (2 nights) → Udawalawe (2 nights) → Bundala/Yala (2 nights) → Horton Plains/Nuwara Eliya (2 nights) → Kandy/Knuckles → Galle/coast → airport. A looping circuit that covers the island\'s four major habitat zones.',
    travel_notes = 'Sinharaja requires a local guide — you\'re not allowed to enter without one, and honestly you wouldn\'t want to because the trails are unmarked and you\'ll miss everything. Book a guide who speaks English and knows bird calls by ear. The rainforest is muddy and leech-infested after rain. Wear leech socks and waterproof boots. It\'s not optional — ignore this and you\'ll spend the day picking leeches off your ankles.

Horton Plains is cold — 5-15°C depending on the season. The walk to World\'s End takes about 3-4 hours round trip. Start before 7am to catch the clear views before the afternoon mist rolls in.',
    pricing_notes = 'Specialist guiding costs add up. Expect USD 50-80 per day for a dedicated nature guide (worth it — they massively improve your sightings). Park fees: Sinharaja USD 15, Udawalawe USD 25, Bundala USD 15, Yala USD 35, Horton Plains USD 20. Binocular rental available at most parks if you don\'t have your own (USD 5-10 per day). Accommodation near Sinharaja is basic — USD 30-50/night. The highland and coastal hotels are more comfortable.',
    cta_text = 'Serious about Sri Lankan birds and wildlife? Tell us your target species list and we\'ll build a habitat-focused itinerary with expert guiding.',
    faq_json = '[{"q":"Do I need to be a serious birder?","a":"It helps, but nature lovers will also enjoy this. The sheer variety of wildlife is impressive even if you can\'t tell a warbler from a whistling thrush."},{"q":"What equipment should I bring?","a":"Binoculars (8x42 minimum), a camera with a 300mm+ lens, leech socks for Sinharaja, waterproof boots, a warm jacket for Horton Plains."},{"q":"Can I combine birding with beach time?","a":"The route finishes on the coast with Galle. You\'ll have one beach day before departure."},{"q":"Are the endemics guaranteed?","a":"34 endemic bird species exist here. You\'ll likely see 10-15 with a good guide, but nothing in nature is guaranteed."}]'
WHERE slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND CHAR_LENGTH(COALESCE(excerpt, '')) < 550;

SET SQL_SAFE_UPDATES = 1;
