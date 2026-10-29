SET NAMES utf8mb4;
SET SQL_SAFE_UPDATES = 0;

-- ========================================================================
-- ITINERARY: Sri Lanka Itinerary 10 Days (slug: sri-lanka-itinerary-10-days)
-- 10 days, itinerary_id = 2
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Your driver meets you at the arrivals gate with a name board — standard but reassuring after a long flight. The transfer to Negombo or Colombo is only 20-30 minutes, so you\'re not battling jet lag in the back of a car for hours. Check in, shower, and if you have energy, walk the Negombo beach strip for a fresh seafood dinner. Skip the sightseeing today — honestly, the jet lag will hit harder than you think and you\'ve got 9 more days of driving ahead.',
    itd.title = 'Arrival and recovery'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'About 4 hours from Negombo to Sigiriya, but the road is decent and your driver will stop for tea or photos. If you arrive by midday, Dambulla Cave Temple is a solid warm-up — less physically demanding than Sigiriya and the ceiling murals are genuinely impressive. Settle into your hotel near Sigiriya or Habarana. The area is quiet, mostly lodges surrounded by jungle. Ask your hotel about the Sigiriya ticket purchase for tomorrow — the queues at the ticket booth can eat 30 minutes of your morning.',
    itd.title = 'Cultural Triangle transfer'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'This is your big Cultural Triangle day. Start early — Sigiriya opens at 7am, and climbing before 8am means you beat the heat AND the tour groups. The climb takes about 45 minutes to an hour with photo stops. Afternoon options: if it\'s June-September, Minneriya\'s elephant gathering is unbeatable (herds of 50+). If you\'re here outside those months, Kaudulla or a Polonnaruwa visit works better. Don\'t try to do Sigiriya AND a safari AND Dambulla in one day — pick two. Your legs will thank you.',
    itd.title = 'Sigiriya, Polonnaruwa, or elephant safari'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Kandy takes about 2.5 hours with stops. The route passes through Matale, where you can visit a spice garden (touristy but interesting — they\'ll try to sell you everything) or the Aluvihare Rock Temple. Arrive in Kandy by early afternoon. The Temple of the Tooth is best visited during the evening puja ceremony (around 6pm) when the drumming and chanting fill the complex. Skip Peradeniya Botanical Gardens unless flowers are your thing — it\'s lovely but eats 2 hours you could spend exploring Kandy\'s lake area instead.',
    itd.title = 'Kandy culture'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The drive from Kandy to Nuwara Eliya is one of Sri Lanka\'s best — about 3 hours through winding tea plantation roads with waterfall stops along the way. Visit a tea factory (Blue Field or Glenloch are less crowded than Mackwoods). The temperature drops as you climb, so have a jacket handy. Nuwara Eliya town itself is a bit tired — think British hill station gone slightly seedy — but the surrounding countryside is gorgeous. Your hotel out of town will be much nicer than anything in the centre.',
    itd.title = 'Nuwara Eliya tea country'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Nuwara Eliya to Ella — about 2.5 hours, one of the most scenic roads in the country. Alternatively, take the train from Nanu Oya to Ella (2 hours, book observation car in advance). Ella is tourist-central but the scenery justifies the crowds. Visit Nine Arch Bridge (go early morning before the Instagram crowd arrives), hike Little Adam\'s Peak for the valley views, and have dinner at one of the cafes with a view. The humidity is lower here than the coast, so evenings are pleasant for sitting outside.',
    itd.title = 'Ella scenic day'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Descend from Ella to the safari belt — about 3-4 hours of driving, mostly winding downhill roads. Udawalawe is the closer option (3 hours) and has the more reliable elephant sightings. Yala is further (4 hours) but better for leopard tracking. The choice depends on what you want to see. Check into your hotel by late afternoon, rest, and organise your safari jeep for tomorrow morning. Most hotels near the parks can arrange this — confirm the pickup time (usually 5am-5:30am) and whether breakfast can be packed.',
    itd.title = 'Safari base transfer'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Pre-dawn start for your safari. The early wake-up is brutal but worth it — animals are most active at sunrise. A typical safari runs 3-4 hours. By 9am you\'re back at the hotel for breakfast and a quick rest. Then it\'s another 2-3 hour drive to the south coast (Galle, Mirissa, or Tangalle depending on your choice). The afternoon will feel long after that 5am start, so plan an easy evening — Galle Fort walk, beach swim, or just dinner and sleep.',
    itd.title = 'Safari and south-coast move'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'A relaxed day with no early start — you\'ve earned it. Walk Galle Fort in the morning before the heat builds. The ramparts loop takes about an hour at a slow pace. Then head to your chosen beach: Unawatuna for swimming and cafes, Hikkaduwa for snorkelling the coral reef, Weligama for surf lessons, or Mirissa for whale watching (November-April only). The south coast has good seafood restaurants everywhere. Try the devilled prawns and a Lion Lager at a beachfront joint.',
    itd.title = 'Galle Fort and beach time'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive back to Colombo or the airport via the expressway — about 1.5 hours from Galle, 2 hours from Mirissa, 2.5 hours from Tangalle. The expressway is fast but monotonous. If your flight is in the evening, you can add a Colombo stop: shopping at Arcade Independence Square, lunch at a Ministry of Crab, or a walk through Galle Face Green. Don\'t push the timing — the expressway has occasional traffic near Colombo and security at the airport takes longer than you expect.',
    itd.title = 'Airport or Colombo departure'
WHERE i.slug = 'sri-lanka-itinerary-10-days'
  AND itd.day_number = 10
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Itinerary 14 Days (slug: sri-lanka-itinerary-14-days)
-- 14 days, itinerary_id = 4
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The gentlest start possible. Airport pickup, 20-30 minute transfer to Negombo or Colombo, and nothing on the schedule except check-in and dinner. Negombo has a pleasant beach strip with good seafood restaurants — try the lagoon crabs. If you\'re staying in Colombo, the city hotels are better but you\'ll deal with traffic getting anywhere. Either way, protect this first evening. The next 13 days are packed and you\'ll appreciate having started with a real night\'s sleep.',
    itd.title = 'Arrival and Negombo recovery'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Today you\'ve got a choice that sets the tone for the heritage portion. Anuradhapura is about 5 hours from Negombo — a long drive but you\'re rewarded with Sri Lanka\'s most sacred Buddhist city, with dagobas the size of small hills. The alternative is driving straight to Sigiriya (4 hours) and starting the classic Cultural Triangle. Anuradhapura is worth the extra drive time if you care about history. If you\'re more into visual spectacle, save the energy for Sigiriya tomorrow.',
    itd.title = 'Anuradhapura or Sigiriya approach'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Sigiriya Rock Fortress in the morning — arrive at 7am opening to beat the heat and the crowds. The climb takes 45-60 minutes and the views from the top are worth every step. Dambulla Cave Temple in the early afternoon (20 minutes from Sigiriya) — the 2,000 square metres of ceiling murals are genuinely impressive. If wildlife is your priority, add an afternoon safari at Minneriya or Kaudulla for elephant viewing. This is a packed day but doable with a 7am start.',
    itd.title = 'Sigiriya, Dambulla, and elephant country'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Polonnaruwa is about 1.5 hours from Sigiriya. Rent a bike at the entrance (USD 3-5) and cycle between the ruined palaces, Buddha statues, and the massive lotus pond. It takes about 3-4 hours to see the main sites. The alternative is a slower day with a village experience — a cooking demo, a catamaran ride on a lake, or just relaxing by the hotel pool. With two nights in Sigiriya, you\'ve earned the option to do nothing.',
    itd.title = 'Polonnaruwa and rural Cultural Triangle'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'A 3-hour drive through the countryside to Kandy, with good stops along the way. The Spice Garden in Matale is a classic tourist trap but the cinnamon and cardamom explanations are genuinely informative. Arrive in Kandy by early afternoon. The Temple of the Tooth is best at the evening puja (around 6pm) — the drumming ceremony is hypnotic. Kandy gets congested with traffic, so stay central and walk where possible. Your driver will appreciate not navigating the one-way system.',
    itd.title = 'Kandy transfer'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Start early and take the scenic route through tea country. The drive from Kandy to Nuwara Eliya takes about 3 hours but you\'ll want to stop: Ramboda Falls, a tea factory tour (Blue Field is good), and the viewpoint at the top of the pass. The temperature drops to 15-18°C in Nuwara Eliya — your first cool evening since arrival. Have dinner at your hotel or a restaurant with a fireplace. The hill club hotels serve mulled wine and it\'s surprisingly good.',
    itd.title = 'Nuwara Eliya and tea estates'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Two options for getting to Ella. The train from Nanu Oya to Ella is the classic choice — 2 hours through tea plantations, tunnels, and mountain scenery. Book the observation car (first class) if you can. The alternative is the private car, which has the advantage of stopping at viewpoints like Ravana Falls. Arrive in Ella by early afternoon. Nine Arch Bridge is a 30-minute walk from the town centre. Go late afternoon for the best light on the photos.',
    itd.title = 'Ella scenic route'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Use this second Ella day properly. Start with Little Adam\'s Peak at sunrise — a gentle 30-minute hike with panoramic views of the Ella Gap. Then hit Lipton\'s Seat in Haputale (about 45 minutes drive from Ella) for tea plantation views that stretch to the coast on a clear day. Afternoon options: Demodara Nine Arch Bridge, Ravana Falls, or just cafe-hop on the main street. Ella\'s food scene is surprisingly good for a small town — try the roti at Matey Hut.',
    itd.title = 'Ella and Haputale slow day'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The drive from Ella down to the safari belt takes about 4 hours — winding roads through tea plantations that eventually flatten into dry-zone scrubland. Yala is the classic choice for leopard-focused wildlife. Udawalawe is better for guaranteed elephant sightings and is closer (3 hours). Tissamaharama is the main town near Yala and has a range of accommodation. Settle in by late afternoon, have a swim to wash off the road, and confirm your safari pickup time (usually 5am).',
    itd.title = 'Safari base transfer'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The 5am safari start feels awful until you\'re bouncing through the park entrance as the sun comes up and the animals start appearing. Yala safaris typically run 3-4 hours. Your driver communicates with other jeeps via radio — when someone spots a leopard, the convoy converges. After the safari, breakfast back at the hotel, then a 2-hour drive to Tangalle or Hiriketiya for the south coast. The afternoon is for recovery by the beach. You\'ll crash hard.',
    itd.title = 'Safari and Tangalle coast'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning drive (about 1.5 hours) from Tangalle to Galle along the coastal road. Galle Fort is the highlight — a UNESCO-listed Dutch fort with boutique shops, cafes, and incredible architecture. Walk the ramparts for sunset, visit the lighthouse, and explore the narrow streets. Stay somewhere inside the Fort if your budget allows — waking up in the Fort is a different experience from visiting as a day-tripper. Unawatuna Beach is 10 minutes from Galle for swimming.',
    itd.title = 'Galle Fort and south-coast heritage'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 11
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Zero alarms. This is your rest day. Options: swim at Unawatuna, take a surf lesson at Weligama (20 minutes drive), book a whale watching tour from Mirissa (November-April only, book ahead), or just rotate between the beach, your hotel pool, and a book. The south coast has excellent spas — a 60-minute Ayurvedic massage costs about USD 25-35. Dinner tip: try a seafood curry at a place recommended by locals, not the hotel restaurant.',
    itd.title = 'South or west coast rest day'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 12
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Move closer to the airport — Bentota, Kalutara, or Colombo depending on your flight. Bentota has a river safari option (mangroves, monitor lizards, and birdlife). Colombo offers shopping and the National Museum. If your flight is early the next day, stay near the airport in Negombo. The expressway from Galle to Colombo takes about 1.5 hours, so you\'re never far. Pack your bags tonight so tomorrow is just a transfer.',
    itd.title = 'Bentota, Colombo, or final beach base'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 13
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport transfer from your final hotel — allow 1 hour from Negombo, 1.5 hours from Colombo. The expressway is reliable but the airport security queue has gotten longer since the new terminal construction. Arrive 3 hours before international flights. If you have time, the Airport Garden Hotel near the terminal does a decent breakfast buffet. Don\'t add sightseeing this morning — the stress of rushing for a flight is not worth one last temple visit.',
    itd.title = 'Departure day'
WHERE i.slug = 'sri-lanka-itinerary-14-days'
  AND itd.day_number = 14
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Itinerary 5 Days (slug: sri-lanka-itinerary-5-days)
-- 5 days, itinerary_id = 5
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport pickup and straight to Negombo or Colombo. This is a short trip so efficiency matters, but don\'t burn your first day sightseeing. The 20-minute transfer is quick enough that you can hit a beachside restaurant for dinner. We recommend staying in Negombo — it\'s closer to the airport and less traffic than Colombo. The Jetwing Sea or a similar beachfront hotel will set the right tone. Early night — tomorrow starts early.',
    itd.title = 'Arrival and Negombo recovery'
WHERE i.slug = 'sri-lanka-itinerary-5-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Negombo to Sigiriya takes about 4 hours with a breakfast stop. Arrive by late morning. Climb Sigiriya Rock in the early afternoon — it\'s hot but doable if you pace yourself and carry water. Alternatively, visit Dambulla Cave Temple (less physically demanding) if the heat is overwhelming. Be realistic about what you can manage. Trying to do Sigiriya AND Dambulla in one afternoon after a 4-hour drive is ambitious. Pick one and do it well.',
    itd.title = 'Sigiriya and Dambulla'
WHERE i.slug = 'sri-lanka-itinerary-5-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning drive (2.5 hours) from Sigiriya to Kandy with a stop in Matale. Visit the Temple of the Tooth in the late afternoon — go during the puja ceremony around 6pm for the drums and chanting. Kandy\'s lake area is pleasant for an evening walk. Skip the Peradeniya Botanical Gardens on this short trip; you don\'t have time. Dinner recommendation: Devi\'s Restaurant in the Mahaweli Reach Hotel for Sri Lankan curry in an atmospheric setting.',
    itd.title = 'Kandy culture route'
WHERE i.slug = 'sri-lanka-itinerary-5-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Kandy up to Nuwara Eliya (3 hours) through tea country. Stop at a tea factory for a tour and tasting. The views from Ramboda Pass are spectacular. Nuwara Eliya is cool (15-18°C) so pack a jacket. The town centre is a bit run-down but the surrounding countryside is beautiful. Have afternoon tea at the Grand Hotel if you want colonial vibes. If you prefer the beach option, skip tea country and drive to Bentota (2.5 hours from Kandy) for a relaxed coastal evening instead.',
    itd.title = 'Tea country and scenic hills'
WHERE i.slug = 'sri-lanka-itinerary-5-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Your departure options depend on where you spent night 4. From Nuwara Eliya, it\'s about 5 hours to the airport via the expressway — leave by 8am for a late afternoon flight. From Bentota or the coast, it\'s only 1.5 hours. If you have time before your flight, add a Galle Fort walk (45 minutes from Bentota) or Colombo shopping at ODEL. Time everything backward from your flight — the airport needs 3 hours for international departures. Don\'t cut it close.',
    itd.title = 'Galle, Colombo, or airport finish'
WHERE i.slug = 'sri-lanka-itinerary-5-days'
  AND itd.day_number = 5
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Short Classic Itinerary 9 Days (slug: sri-lanka-short-classic-itinerary-9-days)
-- 9 days, itinerary_id = 6
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport pickup and a short 20-minute transfer to Negombo. The town is a former fishing village turned tourist strip — it\'s not the prettiest beach in Sri Lanka but it works for a first night. Walk the beach promenade, eat fresh seafood at a beachside restaurant, and get an early night. The best Negombo tip: the lagoon fish market in the morning is worth seeing if you\'re an early riser, but don\'t push yourself after a long flight.',
    itd.title = 'Arrival in Sri Lanka and Transfer to Negombo'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Negombo to Sigiriya — about 4 hours with a breakfast stop. Check into your hotel and head straight for Sigiriya Rock if you arrive early enough (last entry is at 5pm, climbing is best before 3pm to avoid the worst heat). The climb takes about an hour and the views from the top are the reward. If you arrive too late for Sigiriya, Dambulla Cave Temple is a good substitute — it stays open until 6pm and doesn\'t require a strenuous climb.',
    itd.title = 'Negombo to Sigiriya'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Safari day. Minneriya National Park is the main option — the afternoon safari (starting around 2pm-3pm) is best because elephants gather by the reservoir in the late afternoon. The park is about 30 minutes from Sigiriya. Kaudulla is a good backup if Minneriya\'s water levels aren\'t right. A safari runs 2-3 hours and you\'ll see elephants, buffalo, crocodiles, and a variety of birds. The jeep can be dusty, so wear clothes you don\'t mind getting dirty.',
    itd.title = 'Game-Day Safari'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Kandy — about 2.5 hours with a Matale stop for a spice garden visit. Kandy\'s main attraction is the Temple of the Tooth, best visited during the evening puja (6pm). The town is centred around a pretty lake and is walkable if your hotel is central. Traffic in Kandy can be brutal — it\'s one of Sri Lanka\'s worst cities for congestion. Stay in a hotel within walking distance of the Temple if possible.',
    itd.title = 'Sigiriya to Kandy'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'One of the best driving days of the trip. The route from Kandy to Nuwara Eliya takes 3 hours through winding tea plantations. Stop at a tea factory (Glenloch or Bluefield) for a free guided tour and tasting. The waterfalls along this route — Ramboda, Devon, and St. Clair\'s — are worth short stops. Arrive in Nuwara Eliya by early afternoon. The temperature drops noticeably — it\'s usually 15-18°C here compared to Kandy\'s 25°C. Your jacket will get its first use.',
    itd.title = 'Kandy to Nuwara Eliya'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The famous hill country train. Your driver drops you at Nanu Oya station for the 2-hour scenic ride to Ella (or a shorter segment). The train passes through tea plantations, mountain tunnels, and valleys. Book first-class observation car if possible — it sells out so arrange it through your hotel before the trip. If train tickets are unavailable, don\'t feel too bad about the private car drive — the road is nearly as scenic and you can stop for photos.',
    itd.title = 'Scenic Train Experience and Hill Country Leisure'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Long driving day — about 5-6 hours from Nuwara Eliya to Bentota, with lunch stop around Ratnapura or Hatton. The first half of the drive is winding mountain roads, the second half is flatter and faster as you approach the coast. It\'s a significant journey but your driver manages it daily. You\'ll arrive in Bentota by late afternoon. The beach hotel check-in will feel well-earned. Have a sunset swim — the water at Bentota is calm and the beach is one of the best on the west coast.',
    itd.title = 'Nuwara Eliya to Bentota'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Pure beach recovery. Bentota has a wide, clean beach with calm waters — good for swimming. Optional activities: a river safari through the mangroves (watch for monitor lizards and kingfishers), a visit to the Brief Garden (architect Geoffrey Bawa\'s weekend house, beautifully landscaped), or an Ayurvedic massage at your hotel. The beach vendors here are persistent but friendly — a polite \"no, thank you\" works. Don\'t feel guilty about doing nothing today.',
    itd.title = 'Bentota Beach Leisure'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Bentota to the airport — about 1.5 hours via the expressway. If your flight is in the afternoon or evening, add a Colombo stop: visit the Gangaramaya Temple, shop at the Arcade Independence Square, or have a final Sri Lankan meal at Curry Leaf. Don\'t risk a last-minute temple visit if your flight is early — the airport has new security procedures and the queue can take 45 minutes. Aim to arrive 3 hours before departure.',
    itd.title = 'Bentota to Colombo and Departure'
WHERE i.slug = 'sri-lanka-short-classic-itinerary-9-days'
  AND itd.day_number = 9
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Culture, Hills and Safari Itinerary 12 Days (slug: sri-lanka-culture-hills-safari-itinerary-12-days)
-- 12 days, itinerary_id = 7
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Welcome to Sri Lanka. Your driver meets you at the airport and transfers you to Negombo — 20 minutes, easy. Negombo\'s beach strip has plenty of restaurants serving fresh seafood and cold beer. The town is touristy but functional for a first night. Don\'t plan anything ambitious. Check in, shower, eat, and sleep. You\'ve got 11 more days ahead and the jet lag from long-haul flights (especially from Europe or Australia) will hit hardest on day one.',
    itd.title = 'Arrival in Sri Lanka and Transfer to Negombo'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Four-hour drive from Negombo to Sigiriya. The landscape shifts from coastal flats to dry-zone scrub with giant termite mounds dotting the roadside. Check into your hotel near Sigiriya or Habarana. The rest of the day is free — use it well. Have a swim, walk the hotel grounds (many lodges here are set in jungle gardens), and prepare for tomorrow\'s Sigiriya climb. The area around Sigiriya is quiet — don\'t expect nightlife.',
    itd.title = 'Negombo to Sigiriya'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Big cultural day. Start with Sigiriya Rock Fortress at 7am opening — climb it before the heat and crowds arrive (about 45 minutes up). After descending, drive 15 minutes to Dambulla Cave Temple for the impressive ceiling murals and giant Buddha statues. Together, these two sites cover the best of Sri Lanka\'s ancient heritage. If you have energy, add a village tuk-tuk tour or just head back to the hotel for a swim. You\'ve earned it.',
    itd.title = 'Sigiriya Rock Fortress and Dambulla'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning safari at Minneriya or Kaudulla — about a 30-minute drive from Sigiriya. An afternoon safari (starting 2pm-3pm) is the standard because elephants gather by the reservoir later in the day. You\'ll see herds of elephants, water buffalo, crocodiles, and plentiful birdlife. The jeep ride can be bumpy and dusty. After the safari, return to your hotel for a late lunch and pool time. With a second night in Sigiriya, you\'re not rushing anywhere.',
    itd.title = 'Safari and Sigiriya Region Exploration'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Kandy — 2.5 hours with a spice garden stop in Matale. Arrive in Kandy by early afternoon. The Temple of the Tooth is the city\'s centrepiece — visit during the evening puja (6pm) when the relic chamber opens and the drumming begins. The lake area is pleasant for a walk. Kandy traffic is terrible. If you can walk to the temple from your hotel, do that instead of driving. Your driver will be grateful.',
    itd.title = 'Sigiriya to Kandy'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'A full day in Kandy. Peradeniya Botanical Gardens is the main attraction — 40-minute drive from central Kandy, with enormous trees, orchids, and sprawled lawns. The Royal Palace complex and Kandy Museum are worth a quick visit. If you want to see a cultural show with Kandyan dancing (fire-walking included), most hotels can arrange tickets. The shows are touristy but genuinely entertaining. Evening cooking class is another option — Kandy has several that teach Sri Lankan curry techniques.',
    itd.title = 'Kandy Sightseeing'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Scenic drive to Nuwara Eliya — 3 hours through tea country. Stop at a tea factory for the free tour and tasting (Blue Field or Glenloch are good). The waterfalls along the way are photo-worthy. As you climb, the air gets cooler and the views open up over rolling tea estates. Nuwara Eliya town centers around a lake and a golf course — it\'s a faded British hill station but has charm. Your hotel is likely outside town and that\'s where the scenery is best.',
    itd.title = 'Kandy to Nuwara Eliya'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Train day. Your driver drops you at Nanu Oya station for the 2-hour scenic ride to Ella. Book first-class observation car (USD 5-10) through your hotel ahead of time. The train snakes through tea plantations, tunnels, and mountain valleys — it\'s the most photographed train journey in Sri Lanka for good reason. Your driver meets you at Ella station with the car. Ella is touristy but charming. Settle into your hotel and explore the main street\'s cafes and restaurants.',
    itd.title = 'Train Journey to Ella'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Hike Little Adam\'s Peak in the morning (30 minutes, gentle climb, valley views at the top). Visit Nine Arch Bridge — walk the railway tracks and wait for a train to pass for the classic photo. The bridge is a 30-minute walk from town or a short tuk-tuk ride. Afternoon options: Ravana Falls (15-minute drive), tea tasting at a local factory, or cafe time. Ella Demodara Bakery does good bread if you\'re craving something non-rice-based.',
    itd.title = 'Ella Sightseeing'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Ella down to Yala — about 4 hours of winding roads descending from the hills to the dry zone. The landscape changes from green tea estates to scrub jungle and rock outcrops. Check into a hotel near Yala or in Tissamaharama. The afternoon is free for rest and pool time. Confirm your safari jeep and 5am pickup with the hotel. Don\'t book a late dinner — you\'ll be in bed by 9pm to wake up for the safari.',
    itd.title = 'Ella to Yala'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = '5am safari start at Yala National Park. You\'re in the jeep before sunrise, bouncing through the park tracks as the day\'s first light hits. Yala has the highest leopard density in the world — you have good odds of seeing one. After 3-4 hours of wildlife viewing, return to the hotel for breakfast. Then drive 2.5 hours to Galle on the south coast. You\'ll be tired after the early start, so the Galle arrival is low-key. Evening walk around Galle Fort if you have energy.',
    itd.title = 'Yala Safari and Transfer to Galle'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 11
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning exploration of Galle Fort — walk the ramparts, visit the lighthouse, browse the boutique shops, and have breakfast at a Fort cafe. The Dutch colonial architecture is beautifully preserved. Then it\'s a 1.5-hour expressway drive to the airport for your flight. If you have a late flight, add a short stop in Colombo: the Gangaramaya Temple, National Museum, or a lunch at the Galle Face Hotel. Arrive at the airport 3 hours before international departures.',
    itd.title = 'Galle to Colombo and Departure'
WHERE i.slug = 'sri-lanka-culture-hills-safari-itinerary-12-days'
  AND itd.day_number = 12
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Short Escape 5 Days (slug: sri-lanka-short-escape-5-days)
-- 5 days, itinerary_id = 8
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport pickup and 20-minute transfer to Negombo. This first day is intentionally empty — jet lag is real and fighting it with sightseeing never works. Negombo\'s beachfront has good seafood restaurants (try the lagoon crabs at Lords Restaurant). Fall asleep early because tomorrow you\'re heading into the Cultural Triangle. Pack your bag tonight with tomorrow\'s clothes accessible — you\'ll thank yourself in the morning.',
    itd.title = 'Arrival and Negombo recovery'
WHERE i.slug = 'sri-lanka-short-escape-5-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Four-hour drive to Sigiriya. The road is well-maintained and your driver will stop for a breakfast break. Sigiriya Rock is the main event — climb it in the late morning after checking your bags at the hotel. The 200-metre ascent takes about 45 minutes. If the heat is intense or you\'re not up for the climb, Dambulla Cave Temple is an easier alternative with no stairs. Both deliver serious cultural impact. Don\'t try both — this is a short trip.',
    itd.title = 'Sigiriya and Dambulla culture'
WHERE i.slug = 'sri-lanka-short-escape-5-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Kandy — 2.5 hours through the countryside. The Temple of the Tooth is your main Kandy stop. Visit during the evening puja (around 6pm) when the inner chamber opens and you can see the relic casket. Before the temple, stop at a spice garden in Matale — they\'ll demo how cinnamon is harvested and try to sell you their products, but it\'s genuinely educational. Kandy\'s lake area is lovely for an evening walk after the temple.',
    itd.title = 'Kandy via Matale'
WHERE i.slug = 'sri-lanka-short-escape-5-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Decision day. Option A: drive to Nuwara Eliya (3 hours from Kandy) for tea country scenery, cool air, and a completely different Sri Lanka experience. Visit a tea factory and enjoy the mountain views. Option B: drive to Bentota (2.5 hours from Kandy) for a beach afternoon, sunset swim, and zero planning. Your choice affects tomorrow\'s airport transfer time too. Nuwara Eliya = 5 hours to airport. Bentota = 1.5 hours. Choose based on your flight and your mood.',
    itd.title = 'Nuwara Eliya or Bentota choice'
WHERE i.slug = 'sri-lanka-short-escape-5-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'If you chose hills: drive from Nuwara Eliya to Colombo/airport (5 hours via expressway). Leave by 7am for a late-afternoon flight. Stop at a waterfall on the way down. If you chose beach: 45 minutes from Bentota to Colombo, 1.5 hours to the airport. Add a Colombo shopping stop or lunch if timing allows. Either way, don\'t cut the airport timing. Sri Lankan airport security is thorough and unpredictable. Three hours before departure is the minimum.',
    itd.title = 'Colombo shopping and airport drop-off'
WHERE i.slug = 'sri-lanka-short-escape-5-days'
  AND itd.day_number = 5
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Itinerary 7 Days (slug: sri-lanka-itinerary-7-days)
-- 7 days, itinerary_id = 9
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport to Negombo or Colombo — quick and quiet. This is your soft landing day. Don\'t cram in sightseeing. The jet lag from a Europe or Australia flight will hit you harder than you expect. Have a swim if your hotel has a pool, eat a good meal (Negombo\'s seafood is excellent), and sleep early. Tomorrow the route kicks off properly and you\'ll want the energy.',
    itd.title = 'Arrival and soft landing'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Sigiriya (4 hours). Climb Sigiriya Rock in the late afternoon when the heat softens — the views of the surrounding plains are magnificent. If you arrive too late for the climb, head to Dambulla Cave Temple instead (open until 6pm, no climb required). Both deliver the Cultural Triangle experience. Check into your hotel near Sigiriya — the lodges here are set in lush grounds with pool access.',
    itd.title = 'Sigiriya and Dambulla'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Choose your own adventure. Polonnaruwa offers a full morning of cycling through ancient city ruins (1.5-hour drive from Sigiriya, 3 hours to explore). Alternatively, an afternoon elephant safari at Minneriya or Kaudulla starting around 2pm. Polonnaruwa is better for history lovers; the safari is better for wildlife fans. If you do Polonnaruwa, skip the safari today. If you do the safari, you lose Polonnaruwa. This is a 7-day trip — you don\'t get both.',
    itd.title = 'Polonnaruwa or elephant safari'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Kandy (2.5 hours). Stop in Matale for a spice garden visit — the guided tour shows you how cinnamon, cardamom, and pepper grow. Arrive in Kandy by early afternoon. Visit the Temple of the Tooth during the evening puja (6pm). The temple complex gets crowded around this time, but the drumming ceremony is worth it. Stay in a hotel near the lake so you can walk to dinner without dealing with Kandy\'s traffic.',
    itd.title = 'Kandy via Matale'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Head into tea country. The drive from Kandy to Nuwara Eliya or Ella takes 3-4 hours through winding mountain roads. Option A: Nuwara Eliya for a cooler climate, tea factory visits, and colonial atmosphere. Option B: Ella for better views, better food, and a more vibrant scene. The train from Nanu Oya to Ella (book in advance) is the scenic option. By private car, you can stop at waterfalls and viewpoints along the way.',
    itd.title = 'Tea country and Ella scenery'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The fork in the road. Option A: drive to Udawalawe (2.5 hours from Ella) for a family-friendly elephant safari. Option B: drive to Yala (4 hours from Ella) for a leopard-focused safari. Option C: skip the safari entirely and head straight to Galle (3 hours from Ella) for beach and Fort time. This is the biggest choice in your itinerary. Safari = more driving but more wildlife. Beach = less driving but more relaxing. Pick based on what you\'ll regret missing.',
    itd.title = 'Safari or south-coast transfer'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'From Udawalawe/Galle: drive to the airport via expressway. From Udawalawe: 3 hours to the airport. From Galle: 1.5 hours. From Yala: 4+ hours. Plan your departure accordingly. If time allows, walk Galle Fort in the morning (opens at 6am, peaceful before the crowds), then head to the airport. Don\'t skip the Galle Fort if you\'re anywhere near it — it\'s the best way to end a Sri Lanka trip. Arrive at the airport 3 hours early.',
    itd.title = 'Galle, Colombo, or airport'
WHERE i.slug = 'sri-lanka-itinerary-7-days'
  AND itd.day_number = 7
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Itinerary 12 Days (slug: sri-lanka-itinerary-12-days)
-- 12 days, itinerary_id = 10
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport pickup and transfer to Negombo or Colombo — just 20-30 minutes. The first day of a 12-day trip should be gentle. You\'ve got plenty of time, so don\'t force anything. Negombo is more relaxed than Colombo. Walk the beach, eat seafood, drink a Lion Lager. If you\'re arriving from a long-haul flight, the lagoon breeze will help reset your body clock. Sleep early — tomorrow starts the Cultural Triangle.',
    itd.title = 'Arrival and coastal recovery'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Four-hour drive to Sigiriya with a breakfast stop. The Dambulla Cave Temple is a good intro to Sri Lankan Buddhist art — 5 caves with 2,000 square metres of ceiling murals. It\'s about 20 minutes before Sigiriya, so stop there first if you arrive before 3pm. Then check into your hotel near Sigiriya. The lodges here are jungle-set with wildlife in the grounds (peacocks, monkeys, monitor lizards). Dinner is typically at the hotel — the area has few standalone restaurants.',
    itd.title = 'Sigiriya transfer and Dambulla'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Big Cultural Triangle day. Start at 7am with Sigiriya Rock — you want the early morning light and no crowds. The mirror wall and frescoes are the highlights, but the views from the top are the real payoff. Afternoon options: Polonnaruwa (ancient city, cycle around, 1.5-hour drive) or an elephant safari at Minneriya/Kaudulla (afternoon session, 30-minute drive). With two nights here, you can do one today and leave the other for the morning if you\'re not too tired.',
    itd.title = 'Sigiriya, Polonnaruwa, and elephants'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Kandy (2.5 hours). Stop in Matale for a spice garden — the tour is free and you\'ll learn why Sri Lanka was the spice trade hub. Arrive in Kandy by early afternoon. The Temple of the Tooth is best visited at 6pm during the evening puja. The drumming and chanting create an atmosphere you won\'t find at any other time of day. Kandy\'s traffic is awful — walking is often faster than driving from your hotel to the temple.',
    itd.title = 'Kandy via Matale'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive through tea country to Nuwara Eliya (3 hours). The road winds through some of Sri Lanka\'s most photogenic landscapes — terraced tea plantations, waterfalls, and misty valleys. Visit a tea factory (Blue Field or Glenloch are less commercial than Mackwoods). The temperature drops as you climb; Nuwara Eliya sits at 1,800m altitude. Evening suggestion: have a pot of single-estate Ceylon at your hotel and enjoy the cold — it\'s the only place in Sri Lanka where you\'ll need a jacket indoors.',
    itd.title = 'Nuwara Eliya tea country'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'From Nuwara Eliya to Ella — take the train from Nanu Oya station (2 hours, book first-class observation car in advance) or drive 2.5 hours through the hills. The train is the more iconic experience. Your driver will meet you at Ella station with the car. Ella is a small town but popular — the main street buzzes with cafes, bakeries, and souvenir shops. Check in, have lunch at a viewpoint cafe, and plan tomorrow\'s hikes. Ella rewards people who stay active.',
    itd.title = 'Ella scenic transfer'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Active day in Ella. Morning: hike Little Adam\'s Peak (30 minutes, easy) for the valley views. Then walk to Nine Arch Bridge — about 30 minutes from town, or take a tuk-tuk. Time your visit for when a train crosses. Afternoon: Ravana Falls (short visit), Lipton\'s Seat in Haputale (45-minute drive, panoramic views), or just relax at a cafe. Ella\'s food scene is the best in the hill country. Try the roti with curry at Cafe Chill or the wood-fired pizza at 98 Acres.',
    itd.title = 'Ella soft adventure'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Ella down to the safari belt — 3-4 hours of winding roads. Yala is the classic choice (4 hours) for leopard-focused safari. Udawalawe (3 hours) is better for guaranteed elephants. Tissamaharama is the main base town near Yala. Check in, rest, and confirm your safari jeep booking. The hotels in this area range from basic to luxurious. Most can arrange the morning safari with a packed breakfast. Early dinner and early bed — 5am comes fast.',
    itd.title = 'Safari base transfer'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Dawn safari at Yala or Udawalawe (5am-9am). The early morning is prime wildlife viewing time. You\'ll see elephants, deer, wild boar, crocodiles, and possibly leopards (Yala) or more elephants (Udawalawe). After the safari, breakfast at the hotel, then drive 2-3 hours to Galle or the south coast. The afternoon is for recovery — beach, pool, or a nap. You\'ll have earned it after that 5am start.',
    itd.title = 'Safari and Galle transfer'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Slow morning exploring Galle Fort. Walk the entire rampart loop (about 2km), visit the lighthouse, browse the boutiques and cafes inside the Fort. The old Dutch Hospital building now houses shops and restaurants. Galle Fort is a living UNESCO site — people live and work inside the walls, which gives it authenticity that other preserved forts lack. Afternoon at Unawatuna Beach (15 minutes from Galle) for swimming and sunbeds. Evening seafood dinner at a Fort rooftop.',
    itd.title = 'Galle Fort and south-coast beaches'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Your dedicated rest day. Zero itinerary requirements. Options: take a surf lesson at Weligama (USD 15-25, 2-hour session with a board), book a whale watching tour from Mirissa (November-April only, USD 40-60, morning departure), get an Ayurvedic massage (USD 25-35 for 60 minutes), or simply rotate between the beach and your hotel pool. The south coast has excellent spas — many hotels offer in-room treatments. This day is why you chose 12 days instead of 7.',
    itd.title = 'Beach, wellness, or slow coast day'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 11
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Expressway drive from the south coast to the airport: 1.5 hours from Galle, 2 hours from Mirissa, 2.5 hours from Tangalle. If your flight is in the evening, add a Colombo stop — visit the National Museum, shop at Arcade Independence Square, or have a farewell meal at Ministry of Crab (one of Asia\'s top seafood restaurants). Don\'t add any stressful sightseeing. The airport has new security scanners that can take 30-45 minutes. Give yourself a 3-hour buffer.',
    itd.title = 'Airport departure'
WHERE i.slug = 'sri-lanka-itinerary-12-days'
  AND itd.day_number = 12
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Honeymoon Itinerary 12 Days (slug: sri-lanka-honeymoon-itinerary-12-days)
-- 12 days, itinerary_id = 11
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Private airport pickup — no sharing, no waiting. Transfer to a boutique hotel in Colombo or Negombo. The first night of a honeymoon sets the tone, so we pick properties with romance in mind: four-poster beds, plunge pools, and private dining options. Don\'t plan anything after check-in except room service and sleep. The next 11 days are about quality time, and that starts with you both arriving rested. If you\'re arriving late, upgrade to a suite with a late check-in package.',
    itd.title = 'Boutique arrival recovery'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Sigiriya in a premium vehicle with chilled water and WiFi. Check into a jungle lodge — think open-air bathrooms, infinity pools overlooking the plains, and peacocks in the garden. The property IS the experience here. Optional afternoon: Dambulla Cave Temple (skip if you\'d rather just be at the hotel), a private village cooking class, or a couples spa treatment. The area around Sigiriya is spectacular at sunset — your lodge should have a viewpoint for it.',
    itd.title = 'Sigiriya luxury lodge transfer'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Skip the crowded Sigiriya Rock and do Pidurangala instead — a quieter climb with equally spectacular views, especially at sunrise. The scramble over the last boulders is fun and the seclusion beats Sigiriya\'s queues. Arrange a private guide through your hotel. Afternoon option: return to the lodge for spa treatments or a private cooking class. The evening is for Sundowners on your private terrace — ask the hotel to set up a tasting of Sri Lankan arrack, the local coconut spirit.',
    itd.title = 'Sigiriya, Pidurangala, or private heritage day'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Kandy (2.5 hours) with scenic stops chosen for photo potential. The Temple of the Tooth in the evening is the main attraction, but for a honeymoon, the real magic is having a quiet dinner overlooking Kandy Lake afterwards. Stay at a hotel with a view of the lake or the temple — the extra cost is worth it for the atmosphere. Skip the Peradeniya Gardens unless you\'re the type who genuinely enjoys botanical gardens. Kandy traffic is terrible after 5pm — plan around it.',
    itd.title = 'Kandy cultural evening'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The honeymoon highlight. Drive to a tea country bungalow — restored colonial properties with working fireplaces, claw-foot bathtubs, and endless tea estate views. Spend the afternoon on a private tea tasting with a plantation manager. Walk through the estate (rubber boots provided if it\'s wet). The evening is all about the bungalow: dinner by candlelight, a board game in the lounge, and falling asleep to the sound of rain on the roof. This is the Sri Lanka most tourists never experience.',
    itd.title = 'Tea country bungalow'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Travel from tea country to Ella — by train if you want the classic experience (book first-class observation car, 2 hours from Nanu Oya), or by private car with stops at viewpoints. Ella on a honeymoon means sunset at the Nine Arch Bridge, dinner at a hilltop restaurant, and a hotel with a valley view room. 98 Acres Resort is the couple\'s favourite here. The evening should be about each other, not sightseeing. Ella after dark is quiet and romantic.',
    itd.title = 'Ella scenery and viewpoints'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Ella down to the safari belt (3-4 hours). For a honeymoon, choose Yala with a stay at a safari lodge — fenced camps with tented suites, private decks, and candlelit dinners in the bush. The lodges here are expensive but the experience is incredible: you\'ll hear jackals calling at night and wake up to birdsong. Udawalawe is the alternative (less romantic but better elephants). Check in early enough to enjoy the lodge facilities before tomorrow\'s early safari.',
    itd.title = 'Safari lodge transfer'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Private safari at dawn — just you, the guide, and the animals. No sharing the jeep with strangers. The 5am start is worth it when you have the leopard sighting to yourselves. After the safari, breakfast at the lodge, then drive 2 hours to Tangalle or Hiriketiya for a beach transition. Tangalle has some of Sri Lanka\'s most beautiful boutique beach hotels — think private coves, Ayurvedic spas, and infinity pools above the ocean. The rest of the day is for recovery and sunset swims.',
    itd.title = 'Safari and Tangalle coast'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Move to Galle Fort for the boutique stay. Check into a restored Dutch villa inside the Fort walls — narrow streets, colonial architecture, and rooftop views of the Indian Ocean. Walk the ramparts in the late afternoon when the light turns everything golden. Browse the boutique shops (clothing, jewellery, tea). Dinner at a Fort restaurant — try the Ministry of Crab or the A Minute By Tuk Tuk for excellent Sri Lankan fusion. Galle Fort at night is quiet and atmospheric.',
    itd.title = 'Galle Fort boutique stay'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Pure indulgence day. Morning spa session at your hotel or at a beachfront wellness centre. Afternoon at the beach or pool. If it\'s whale watching season (November-April), consider a private charter from Mirissa (expensive but unforgettable). Alternatively, book a private surf lesson at Weligama or a sunset catamaran cruise. The key to a honeymoon is not over-planning — leave gaps in the day for spontaneity. Let the mood guide you rather than the itinerary.',
    itd.title = 'Beach and wellness day'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Move to your final beach hotel — a luxury resort on the south or west coast with beach access, multiple restaurants, and a spa. This is your last full day, so make it count. Have a slow breakfast, swim in the ocean, read your book, drink something with a little umbrella in it. The hotel should feel like a destination, not a transit stop. Private dinner on the beach is the classic honeymoon ending — most resorts offer this with advance notice.',
    itd.title = 'Private beach finish'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 11
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Final transfer to the airport. Your driver picks you up from the hotel with plenty of buffer. The expressway from most south and west coast hotels is quick (1-2 hours to the airport). If your flight is late, a final Colombo lunch or shopping stop works — but don\'t compromise on romance. A rushed curry is not how you want this honeymoon to end. Arrive at the airport 3 hours early and start planning your next Sri Lanka trip before you\'ve even left.',
    itd.title = 'Departure with relaxed buffer'
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND itd.day_number = 12
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Family Itinerary 10 Days (slug: sri-lanka-family-itinerary-10-days)
-- 10 days, itinerary_id = 12
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Short transfer to Negombo or Colombo. For families, this first day is about resetting after the flight. Choose a hotel with a pool — it\'s non-negotiable for keeping kids happy. Many family-friendly hotels in Negombo have shallow children\'s pools and babysitting services. Let the kids swim while you sort out the check-in. Order room service or a hotel buffet dinner. Early bed for everyone. The jet lag will hit the kids at unpredictable hours — roll with it.',
    itd.title = 'Family arrival recovery'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Sigiriya (4 hours with a snack/restroom break — plan the stop). Kids will need a break about halfway. The key to family road trips in Sri Lanka: snacks, a tablet with downloaded movies, and a stop every 2 hours. Check into a family-friendly hotel near Sigiriya with a pool. Skip the major sightseeing today — the drive is enough. Let the kids run around the hotel grounds. Many lodges here have wildlife on the property (monkeys, peacocks) that kids love spotting.',
    itd.title = 'Sigiriya family base'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Kids\' day. Morning: Dambulla Cave Temple (keep it to 30 minutes — the giant Buddha statues are impressive for all ages). Midday: hotel pool. Afternoon: elephant safari at Minneriya or Kaudulla — this is the stuff kids remember for years. The jeep ride is bumpy and fun, and seeing elephants in the wild beats any zoo experience. Pack snacks and water for the safari. The jeeps are open-air and dusty, so dress kids in clothes that can get dirty.',
    itd.title = 'Dambulla, village life, or elephant safari'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Kandy (2.5 hours). The Temple of the Tooth is the main stop — make it quick, keep it interesting for kids (point out the gold, the drums, the flowers). The Royal Botanical Gardens in Peradeniya are actually great for families — giant trees, open lawns for running, and a spice garden section. Kandy traffic is bad, so plan your arrival and departure around rush hour (5-7pm). Choose a hotel with a pool — this applies every night.',
    itd.title = 'Kandy gardens and temple area'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive through tea country to Nuwara Eliya (3 hours). The scenery is spectacular but the winding roads can cause car sickness — sit kids by the windows. Stop at a waterfall for photos (Ramboda Falls is right by the road). Nuwara Eliya\'s cooler weather (15-18°C) is a novelty for kids from tropical countries. Visit a small tea factory — most have free entry and the staff are friendly with children. The town has a small park by the lake that\'s good for a run-around.',
    itd.title = 'Nuwara Eliya light hill day'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Nuwara Eliya to Udawalawe (3 hours). This is a positioning day to get to the safari area. Udawalawe is the best national park for families — the elephants are almost guaranteed and the terrain is open so you can see animals from far away. Afternoon: visit the Elephant Transit Home (feeding times at 9am, 12pm, and 3pm) where orphaned baby elephants are rehabilitated. Kids love this. The entry fee is minimal (about USD 10) and the experience is pure magic.',
    itd.title = 'Udawalawe transfer and elephant focus'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning safari at Udawalawe (6am-9am, earlier start than the kids expect but worth it). The elephants are highly visible and the jeep ride itself is an adventure. After the safari, breakfast at the hotel, then drive 2 hours to Galle or Unawatuna for the beach portion of the trip. The afternoon is for beach, pool, and decompression. Kids have been patient through the travel-heavy part — reward them with sand and sea. Unawatuna Beach has calm, swimmable water.',
    itd.title = 'Udawalawe safari and Galle transfer'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Short Galle Fort walk (30-45 minutes is enough for kids — the ramparts are fun to run on, the lighthouse is a landmark). Then hit the beach. Unawatuna is the closest to Galle and has calm waters, sun loungers, and beachfront restaurants serving kid-friendly food (rice and curry, fried rice, noodles). Banana boat rides are available for an extra fee. The afternoon should be unstructured — kids need beach time to reset after days in the car.',
    itd.title = 'Galle Fort and Unawatuna'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Full beach and pool day. Choose your beach based on your hotel location: Bentota (calm waters, good for swimming), Hikkaduwa (snorkelling, turtles), Weligama (gentle surf for older kids), or Unawatura (all-round best). Most family hotels on this coast have children\'s pools, play areas, and sometimes kids\' clubs. Let the kids swim all day while you rotate between a sunbed, a book, and a Lion Lager. This is the payoff day for the whole trip.',
    itd.title = 'Bentota or south-west beach day'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport transfer — 1.5 hours from most west coast hotels via expressway. Pack the night before and have a relaxed breakfast. If your flight is in the afternoon, a final beach or pool session in the morning works well. Don\'t try to squeeze in sightseeing on departure day — it never ends well with kids. The airport has a small play area near the departure gates, but it\'s minimal. Bring colouring books or a tablet for the wait. Arrive 3 hours before the flight.',
    itd.title = 'Airport departure'
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND itd.day_number = 10
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Wildlife Itinerary 9 Days (slug: sri-lanka-wildlife-itinerary-9-days)
-- 9 days, itinerary_id = 13
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Standard airport pickup and transfer to Negombo or Colombo. For a wildlife-focused trip, the first day is just logistics — get in, sleep, and start early tomorrow. Don\'t be tempted to visit Negombo\'s fish market or lagoon. Save your energy. The wildlife starts tomorrow when you reach the dry zone. Pack your binoculars and camera gear tonight. Check that your lenses are clean and your memory cards are empty — you\'ll need every gigabyte in the coming days.',
    itd.title = 'Arrival and recovery'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Habarana or Sigiriya (4 hours) through the dry zone. The landscape here is flat, scrubby, and dotted with giant termite mounds — classic Sri Lankan elephant country. Check into your hotel and do an afternoon reconnaissance: visit Dambulla Cave Temple if you want culture, or just scout the area around Minneriya National Park for tomorrow\'s safari. The hotel staff will know the latest on elephant movements. Don\'t visit Sigiriya unless you also want the cultural experience — this trip is about wildlife.',
    itd.title = 'Habarana and Sigiriya base'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Safari day at Minneriya or Kaudulla. The afternoon session (2pm-5pm) is best — elephants gather by the reservoir as the heat subsides. June-September offers the famous Great Gathering (200+ elephants). Outside those months, Kaudulla often has better water levels and more consistent sightings. Both parks also have buffalo, crocodiles, and abundant birdlife. Morning option: a quick climb of Pidurangala or Sigiriya if you want the views. But honestly, save your energy for the elephants.',
    itd.title = 'Minneriya or Kaudulla elephant safari'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sigiriya to Udawalawe (about 4 hours). This safari destination is guaranteed elephant territory — the park has a resident population of about 500 elephants that you\'ll almost certainly see. Check into a hotel near the park entrance. The Elephant Transit Home is a 10-minute drive from the park and worth a visit (feeding times at 9am, 12pm, 3pm). If you arrive early enough, catch the 3pm feeding. Watching baby elephants being bottle-fed is the highlight for many visitors.',
    itd.title = 'Udawalawe transfer'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Full morning safari at Udawalawe (6am-9am). The early start is rewarded with elephants at close range, often within the first 30 minutes. Udawalawe is also excellent for raptors — crested serpent eagles, steppe eagles, and changeable hawk-eagles are common. After the safari, breakfast, then rest by the pool. The afternoon is free for a second safari (half-price park entry if you keep the same ticket) or a visit to the Elephant Transit Home if you missed it yesterday.',
    itd.title = 'Udawalawe safari'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Udawalawe to Yala/Tissamaharama — about 3 hours across the dry zone. Check in near Yala National Park. For birders, add a stop at Bundala National Park (about 30 minutes from Tissamaharama) for migratory waterbirds, flamingos, and painted storks. Even if birding is not your main focus, Bundala is worth a short visit. The afternoon is for rest before the 5am start tomorrow. Confirm your Yala safari jeep and have an early meal.',
    itd.title = 'Yala or Tissamaharama base'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Dawn safari at Yala (5am-9am). Yala offers the best chance of seeing leopards in Sri Lanka — the park has the highest leopard density in the world. Your driver communicates with other jeeps via radio to track sightings. After the main safari, a quick visit to Bundala for birding if conditions are good. The afternoon is free for rest. If you\'re a serious birder, a second evening drive at Bundala or a wetland walk near Tissamaharama could add species to your list.',
    itd.title = 'Yala safari and Bundala option'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Recovery day on the south coast. Drive from Tissamaharama to Tangalle (2 hours), Hiriketiya (2.5 hours), Galle (3 hours), or Unawatuna (3 hours). After 4 consecutive days of wildlife-driven schedules (5am starts, dusty roads, long hours), your body needs a break. The south coast provides it — good beaches, cold beer, and no alarm clock. Tangalle has quieter beaches. Galle offers the Fort for heritage. Choose based on your flight tomorrow.',
    itd.title = 'Tangalle or Galle coastal rest'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport transfer via expressway — 1.5 hours from Galle, 2 hours from Tangalle, 2.5 hours from Hiriketiya. If your flight is in the afternoon, consider a morning walk at Galle Fort (opens at 6am, peaceful before the crowds). Otherwise, just enjoy a slow breakfast and head to the airport. Reflect on what you\'ve seen: elephants, leopards, crocodiles, eagles, and a national park system that rivals much of Africa for accessibility. Not bad for a 9-day trip.',
    itd.title = 'Airport departure'
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days'
  AND itd.day_number = 9
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka South Coast Itinerary 8 Days (slug: sri-lanka-south-coast-itinerary-8-days)
-- 8 days, itinerary_id = 14
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Airport pickup and transfer to Negombo or Colombo. For a south coast trip, you don\'t need to start in the Cultural Triangle — you\'re heading straight for the coast tomorrow. Tonight is about a comfortable hotel and an easy meal. If you\'re arriving early enough, Negombo\'s beach sunset is a gentle intro to Sri Lanka. Don\'t over-order at dinner — the south coast has incredible seafood and you\'ll want to save your appetite for what\'s coming.',
    itd.title = 'Arrival and Colombo or Negombo recovery'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Galle via the expressway (1.5 hours from Colombo, 2 hours from Negombo). The expressway is fast and boring but the reward is an afternoon in Galle Fort. Walk the full rampart loop, visit the lighthouse, explore the Dutch colonial streets, and have lunch at a Fort cafe. Check into a hotel inside the Fort or in nearby Unawatuna. The Fort comes alive in the late afternoon as the heat subsides. Stay for sunset on the ramparts — this is the south coast\'s best free attraction.',
    itd.title = 'Galle Fort arrival'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Beach crawl day. Unawatuna is closest to Galle — a curved bay with calm swimming water, sunbeds, and beachside restaurants. Hikkaduwa is further north (30 minutes) and has coral reef snorkelling right off the beach — rent a mask and fins for USD 5 and swim with turtles in the shallows. The reef is degraded but the turtles are reliable. Lunch at a beach cafe (try a fish skewer and a fresh lime soda). Drive time between beaches is minimal — this is an easy day.',
    itd.title = 'Unawatuna, Hikkaduwa, and reef coast'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Continue east along the coast to Weligama and Mirissa (30-40 minutes from Galle). Weligama has the best beginner surf break on the island — long, gentle waves that roll in consistently. Surf lessons cost USD 15-25 for a 2-hour session and include board rental. Mirissa is more relaxed with a gorgeous crescent beach and a headland viewpoint. The evening vibe in Mirissa is laid-back — beachfront bars with beanbags, fire dancers on weekends, and excellent seafood.',
    itd.title = 'Weligama and Mirissa'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Whale watching day (November-April only). The boats depart Mirissa at 6am and return by 10am-11am. You\'re looking for blue whales, the largest animals on earth, which feed in these waters seasonally. Book through a reputable operator — not the cheapest one. If it\'s not whale-watching season or the trips are cancelled due to weather, this becomes a dedicated beach day. Mirissa\'s Coconut Tree Hill viewpoint is Instagram-famous for a reason — go early to avoid the crowd.',
    itd.title = 'Mirissa whale watching or beach day'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive further east to Tangalle or Hiriketiya (1-1.5 hours from Mirissa). Tangalle is the quieter option with wide, empty beaches and a few high-end resort hotels. Hiriketiya is a horseshoe bay with a perfect surf break for intermediates and a cluster of boutique guesthouses and cafes. Both are less developed than Mirissa. The afternoon is for swimming, reading, and decompressing. This part of the coast feels more remote and genuine than the Galle area.',
    itd.title = 'Tangalle and Hiriketiya'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Safari day trip. Udawalawe is about 2 hours from Tangalle, 2.5 hours from Hiriketiya — making it a viable day trip from the south coast. The park is excellent for elephant sightings and you can do a morning safari (6am-9am) and be back on the coast by lunchtime. Yala is further (3+ hours from Tangalle) but possible if you want leopard hunting. Both options add a wildlife dimension to a beach trip. Book the jeep through your hotel.',
    itd.title = 'Udawalawe or Yala safari option'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Return to the airport via the expressway. From Tangalle: 2.5 hours. From Mirissa: 2 hours. From Galle: 1.5 hours. If your flight is late afternoon or evening, add a final Galle Fort walk and lunch (Fort is about halfway from all these points back to Colombo). Don\'t add a last-minute safari or activity — the expressway is reliable but Colombo traffic near the airport can be unpredictable. Three hours before departure is the rule.',
    itd.title = 'Airport or Colombo return'
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days'
  AND itd.day_number = 8
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka East Coast Itinerary 10 Days (slug: sri-lanka-east-coast-itinerary-10-days)
-- 10 days, itinerary_id = 15
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Standard start — airport pickup, short transfer to Negombo or Colombo. This trip is a hybrid of culture and east-coast beach, so the first day is about getting ready for the Cultural Triangle tomorrow. Don\'t stay up late. The drive to Sigiriya tomorrow is about 4 hours and you\'ll want an early start. East coast trips work best May-September. If you\'re travelling outside that window, the weather on the east will be riskier.',
    itd.title = 'Arrival and recovery'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Sigiriya (4 hours). Check into your hotel near the rock. If you arrive by early afternoon and still have energy, visit Dambulla Cave Temple (open until 6pm, 20-minute drive from Sigiriya). The cave temple\'s 150 Buddha statues and wall-to-wall ceiling murals are an impressive intro to Sri Lankan religious art. If you\'re tired from the drive, just relax at the hotel. You have two nights here — sightseeing can wait until tomorrow morning.',
    itd.title = 'Sigiriya transfer'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Sigiriya Rock at 7am opening — climb early to beat the heat and crowds. The 200-metre ascent passes through the Mirror Wall and fresco alcoves before reaching the summit with panoramic views. Afternoon options: an elephant safari at Minneriya or Kaudulla (30-minute drive, afternoon session 2pm-5pm) or a relaxed hotel afternoon. With two nights in Sigiriya, you can pace yourself. The east coast has a different pace — slower, beachier — so this culture section will feel different.',
    itd.title = 'Sigiriya, Dambulla, and elephant option'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning at Polonnaruwa (1.5 hours from Sigiriya). Rent a bicycle at the entrance and cycle through the ancient city — the royal palace ruins, the giant Buddha statues at Gal Vihara, and the lotus pond. Takes about 3 hours. Then drive 2 hours to Trincomalee or Nilaveli on the east coast. The road is in good condition. Check into a beach hotel. The east coast is noticeably different from the west — bigger beaches, fewer tourists, and the ocean is a different shade of blue.',
    itd.title = 'Polonnaruwa to Trincomalee'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Beach day on the east coast. Nilaveli Beach is wide, flat, and beautifully empty compared to the south coast. Pigeon Island National Park (20-minute boat ride from Nilaveli) offers excellent snorkelling — coral reefs, reef sharks, turtles, and a riot of tropical fish. The boat trip costs about USD 20-30 per person. Uppuveli is closer to Trincomalee town and has more accommodation options. Evening: seafood dinner on the beach. The east coast is famous for crab and lobster.',
    itd.title = 'Nilaveli, Uppuveli, and Pigeon Island option'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Explore Trincomalee. Visit Koneswaram Temple on the cliff overlooking the Indian Ocean — it\'s a working Hindu temple with spectacular views. Fort Frederick is a colonial-era military base that\'s still in use; parts are open to the public. The natural harbour is one of the best deep-water ports in the world and the views from the temple show why the British fought for it. Afternoon beach time at Nilaveli or Uppuveli. The water here is clean, clear, and warm.',
    itd.title = 'Koneswaram and Trincomalee coast'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive south along the east coast to Passikudah (2 hours). Passikudah is the east coast\'s resort strip — a long, shallow bay with a sandy bottom that extends 100 metres into the sea. The water is flat and warm, more like a swimming pool than the ocean. It\'s perfect for families and anyone who wants resort-style beach relaxation. The alternative is to continue to Batticaloa (another hour south) for a more local experience with lagoon boat rides and a historic Dutch fort.',
    itd.title = 'Passikudah or Batticaloa'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Surf option: Arugam Bay (3+ hours from Passikudah) is Sri Lanka\'s premier surf destination. The main point break is world-class in July-August. If you\'re not a surfer, the bay has a great backpacker vibe with good restaurants and beautiful beaches. Non-surf option: stay in Passikudah for another day of resort relaxation. The choice depends on your energy for driving and your appetite for waves. Arugam Bay adds 2 hours to your drive back north tomorrow.',
    itd.title = 'Arugam Bay or extra Passikudah rest'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'The inland return. From Passikudah: drive 2.5 hours to Kandy or Dambulla for an overnight stop. From Arugam Bay: drive 4 hours to Dambulla or 5 hours to Kandy (it\'s a long day). The road from the east coast back to the hill country goes through tea and rubber plantations. The scenery shifts from flat coastal plains to rolling green hills. Check into your hotel near Kandy or Dambulla. This positioning move prevents a 7-hour death march to the airport tomorrow.',
    itd.title = 'Inland return via Kandy or Negombo'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Final drive to the airport. From Kandy: 3 hours via expressway. From Dambulla: 4 hours. From Negombo: 30 minutes. Plan your departure based on your flight time. If you\'re stopping in Negombo, the beach is a decent final destination for a quick swim before heading to the terminal. Don\'t add a sightseeing stop this morning — the drive from inland is long and airport timing is tight. The east coast route is the most complex logistically, but the uncrowded beaches make it worth the effort.',
    itd.title = 'Airport departure'
WHERE i.slug = 'sri-lanka-east-coast-itinerary-10-days'
  AND itd.day_number = 10
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Luxury Itinerary 11 Days (slug: sri-lanka-luxury-itinerary-11-days)
-- 11 days, itinerary_id = 16
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'VIP arrival experience. Your chauffeur meets you at the arrivals hall with a name board and assists with luggage. Transfer to a boutique Colombo or Negombo hotel. The premium vehicle (Toyota KDH or similar) has reclining seats, WiFi, and chilled water. Check into your hotel — the first night should feel luxurious. The Colombo Courtyard or a Negombo beachfront villa sets the right tone. Room service, a spa treatment if you\'re not too jet-lagged, and a restful night.',
    itd.title = 'VIP arrival and soft landing'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive to Sigiriya (4 hours) in your premium vehicle. Check into a luxury lodge — Ali Adsha, Water Garden Sigiriya, or Jetwing Vil Uyana. These properties are destinations in themselves: private pools, open-air bathrooms, and grounds populated with monkeys and peacocks. The afternoon is intentionally free. This is not a check-list itinerary. Settle in, have a swim, and let the property welcome you. Dambulla or a private village experience can wait.',
    itd.title = 'Sigiriya luxury lodge'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Privately guided day. Your guide meets you at the hotel and takes you to Sigiriya before the crowds arrive. You\'ll get the history behind the frescoes, the mirror wall, and the palace ruins — details you\'d miss on your own. Afternoon options: Pidurangala for a quieter climb, Polonnaruwa for ancient-city depth, or a private cooking class at the lodge. The key is choosing fewer stops and getting better context at each one. Lunch at a local restaurant chosen by your guide.',
    itd.title = 'Private Sigiriya and heritage guiding'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive toward Kandy or directly to tea country. If you\'re staying in Kandy, check into a hillside hotel with lake views and visit the Temple of the Tooth in the evening (private tour can be arranged). If you\'re heading straight to tea country, the drive is about 3 hours with scenic stops. The luxury approach to Kandy is to skip the crowded tourist spots and enjoy a curated lunch at a garden restaurant with a view of the lake.',
    itd.title = 'Kandy or tea country transition'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Tea country bungalow stay. Properties like Ceylon Tea Trails, Goatfell, or the Grand Hotel\'s tea cottages offer a slice of colonial Ceylon. Your day includes a private tea tasting with a planter, a guided walk through the estate, and a dinner that\'s the highlight — multicourse meals served in a historic dining room. The weather here is cool (12-18°C) so pack accordingly. The evening is for the fireplace, a book from the bungalow library, and single-estate whiskey.',
    itd.title = 'Tea bungalow and highland scenery'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Travel from tea country to Ella or Haputale. Take the scenic train if the timing works (first-class observation car from Nanu Oya, 2 hours) or enjoy a private drive with stops at the best viewpoints. In Ella, the 98 Acres Resort or a hillside villa offers valley-view rooms. Hike to the Nine Arch Bridge when the light is golden. Dinner at a restaurant with a view of the Ella Gap. The luxury version of Ella is about finding the right table, not rushing between attractions.',
    itd.title = 'Ella or Haputale scenic day'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Ella to your safari lodge (3-4 hours). The luxury options near Yala — Wild Coast Tented Lodge or Jetwing Yala — are safari experiences masquerading as hotels. Think canvas-walled suites with plunge pools, outdoor showers, and candlelit dinners in the bush. Check in, settle in, and do the afternoon activity (guided nature walk or evening game drive). The lodge handles everything — just tell them your preferences. This is where the luxury itinerary justifies itself.',
    itd.title = 'Safari lodge transfer'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Private safari at dawn with your own guide and vehicle. No sharing, no radio chatter from other jeeps. Your guide knows the park intimately and will position you for the best sightings. After the safari, return to the lodge for breakfast, then drive 2 hours to Tangalle, Hiriketiya, or a wellness coastal stay. Properties like Anantara Peace Haven in Tangalle offer Ayurvedic treatments and ocean-view rooms. The afternoon is for spa, pool, and complete decompression.',
    itd.title = 'Private safari and Tangalle wellness'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Move to Galle Fort for a boutique stay. Properties inside the Fort — like Taru Villas or Mansion House — offer colonial elegance with modern amenities. Your afternoon is curated: a private Fort walking tour with a historian, a bespoke shopping experience at a jewellery designer\'s studio, and sunset drinks on a rooftop with ocean views. Dinner at a Fort restaurant that\'s been selected for the occasion. Galle Fort at night, when the day-trippers have left, feels like a private town.',
    itd.title = 'Galle boutique stay'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'A full day with nothing compulsory. Your south coast hotel should have exceptional facilities — multiple pools, spa, private beach access, and dining that rivals the best in Colombo. Book a treatment, take a cookery class, charter a catamaran for sunset, or just rotate between the pool and the daybed. The luxury trip succeeds or fails on how well it lets you do nothing. A good hotel makes doing nothing feel like an achievement.',
    itd.title = 'Beach, villa, or wellness day'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Premium vehicle transfer to the airport. Your chauffeur handles the bags and you relax in the back seat for the 1.5-hour expressway drive. If you have time, a final lunch at a Colombo restaurant (Ministry of Crab or The Cricket Club) or a quick visit to the Barefoot shop for last-minute gifts. The airport VIP lounge is included — head through security early and enjoy it. Your 11-day investment in slow, premium Sri Lanka ends here, but the memories will last longer than any standard trip.',
    itd.title = 'Airport departure with buffer'
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND itd.day_number = 11
  AND 1=1;

-- ========================================================================
-- ITINERARY: Sri Lanka Birding and Nature Itinerary 12 Days (slug: sri-lanka-birding-nature-itinerary-12-days)
-- 12 days, itinerary_id = 17
-- ========================================================================

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Arrive and transfer to Negombo or Colombo. For birders, Negombo matters — the Negombo Lagoon and Muthurajawela Wetlands are within 30 minutes of the airport and offer early waterbird sightings. If you arrive early enough, a late-afternoon visit to the lagoon could yield open-billed storks, herons, kites, and kingfishers. Don\'t push it if you\'re tired — the real birding starts tomorrow. Stay near the wetlands for convenience.',
    itd.title = 'Arrival and wetland edge recovery'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 1
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive toward Sinharaja rainforest (about 3 hours from Negombo via Ratnapura). This is a positioning day — the drive takes you through rubber and tea plantations. Ratnapura is the gem-mining capital of Sri Lanka, but you\'re here for the rainforest access. Check into a guesthouse or eco-lodge near the Sinharaja entrance. Arrange your local guide for tomorrow (mandatory for entry). Most guesthouses have good guide connections. Settle in and listen to the evening forest sounds.',
    itd.title = 'Ratnapura or Sinharaja approach'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 2
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Full day in Sinharaja rainforest. Start at dawn with your local guide. The birding is exceptional: Sri Lanka blue magpie, red-faced malkoha, Sri Lanka spurfowl, and if you\'re lucky, the Sri Lanka frogmouth. The rainforest is also home to endemic snakes, frogs, and butterflies. The trails are muddy and leech-infested after rain — wear leech socks and waterproof boots. Carry binoculars and a rain cover for your camera. Plan for 6-8 hours in the forest with a packed lunch.',
    itd.title = 'Sinharaja rainforest'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 3
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Sinharaja to Udawalawe (about 3 hours). The landscape shifts from rainforest to dry-zone scrub. Check into your hotel near Udawalawe. Late afternoon: a short visit to the Elephant Transit Home to watch the baby elephant feeding (3pm session). This isn\'t birding, but it\'s a gentle intro to the wildlife zone. Your guide will also start pointing out dry-zone bird species — bee-eaters, drongos, and parakeets are common in the hotel grounds.',
    itd.title = 'Udawalawe dry-zone transition'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 4
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning safari at Udawalawe (6am-9am). The park is excellent for dry-zone birds: crested serpent eagles, changeable hawk-eagles, painted storks, open-billed storks, and waterbirds around the reservoir. And yes, elephants — lots of them. Your nature guide will be identifying birds while the jeep driver tracks pachyderms. After the safari, breakfast, then drive toward Bundala or Tissamaharama (2 hours). Birders will want to arrive in time for a late-afternoon Bundala visit.',
    itd.title = 'Udawalawe safari and Bundala approach'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 5
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Bundala National Park in the early morning — a wetland paradise for migratory birds. In season (November-March), you\'ll see flamingos, pelicans, open-billed storks, and dozens of wader species. The park has three main lagoons and a coastal section. After lunch, a drive through Yala National Park\'s buffer zone or a full Yala safari if the schedule allows. Yala offers dry-zone mammals (leopards, sloth bears, deer) in addition to birdlife. Evening: wetland walk near Tissamaharama.',
    itd.title = 'Bundala and Yala wetland-dry-zone mix'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 6
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from the dry zone up to the highlands (4-5 hours). The route goes through Ella and continues to Horton Plains or Nuwara Eliya. This is a big transfer day but the habitat change is dramatic — you\'re going from dry-zone heat (35°C) to cloud forest (10-18°C). Check into a hotel in Nuwara Eliya or near the Horton Plains entrance. The evening is for acclimatising to the cold. Victoria Park in Nuwara Eliya is a good birding spot for highland species.',
    itd.title = 'Horton Plains or Nuwara Eliya transfer'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 7
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Dawn start for Horton Plains National Park. The walk to World\'s End viewpoint takes 3-4 hours round trip through misty cloud forest. Birding targets: Sri Lanka whistling thrush, Sri Lanka bush warbler, and yellow-eared bulbul (all endemic). Start before 7am to catch the clear views at World\'s End before the mist rolls in. The plains are cold (5-15°C) and often windy. Afternoon: return to Nuwara Eliya for Victoria Park birding or a tea estate walk.',
    itd.title = 'Highland habitat day'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 8
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Drive from Nuwara Eliya to Kandy or the Knuckles foothills (3 hours). The Knuckles Mountain Range is a UNESCO-identified biodiversity hotspot with endemic flora and fauna. If you\'re a serious birder, this is worth the detour for species like the Sri Lanka white-eye and Sri Lanka scimitar-babbler. The alternative is a day in Kandy visiting the Royal Botanical Gardens — surprisingly good for birding around the giant trees and lake. Choose based on your energy and target species.',
    itd.title = 'Kandy and Knuckles foothills'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 9
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Morning nature walk in the Knuckles foothills or a final forest-edge session near Kandy. Then drive toward Galle or the south-west coast (4-5 hours). This positions you for the return to the airport. The coastal habitat adds waterbird species — check the lagoons near Galle or Negombo for waders, terns, and kingfishers. Check into a coastal hotel. This is your last nature-focused evening. Have a final species list session with your guide over dinner.',
    itd.title = 'Forest edge or coastal nature return'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 10
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Buffer day — one of the most important in a nature itinerary. Use it for a missed target, a second visit to a rewarding site, or weather recovery. Options: repeat birding at a premier wetland, a photography session with your best subjects, or a relaxed coastal nature walk. Kalametiya Bird Sanctuary (near Tangalle) or the Lunawa Lagoon (near Colombo) are flexible options. The buffer day is why specialist routes cost more — they respect that nature doesn\'t follow a schedule.',
    itd.title = 'Flexible specialist nature day'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 11
  AND 1=1;

UPDATE itinerary_days itd
JOIN itineraries i ON i.id = itd.itinerary_id
SET itd.summary = 'Final airport transfer. From the south-west coast, you\'re 1.5 hours from the airport via expressway. Use this morning for a final checklist review with your guide — did you see everything on your target list? The species count for a 12-day specialist trip with a good guide should be 150-200 birds, multiple mammal species, and a mix of habitats that most travellers never experience. Sri Lanka punches far above its size. You\'ll leave with a species list that sparks envy in birder friends back home.',
    itd.title = 'Airport departure'
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days'
  AND itd.day_number = 12
  AND 1=1;

SET SQL_SAFE_UPDATES = 1;
