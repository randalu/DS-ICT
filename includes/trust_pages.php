<?php
declare(strict_types=1);
require_once __DIR__ . '/policy_content.php';
require_once __DIR__ . '/published_page_audit.php';

/** R2 proposals derived from approved R0 facts; no writes or publication. */
function trust_page_drafts(): array
{
    $content = [
        'trust-safety' => ['Trust and Safety', 'Safety planning, transport cover and practical needs for your private Sri Lanka journey.',
            '<h2>Plan around your practical needs</h2><p>Tell us about mobility, luggage and child-seat needs before confirmation. Discuss the route, stops and practical requirements with us so the agreed services can be recorded in your quote and confirmation.</p><h2>During your journey</h2><p>Follow reasonable safety instructions and use seat belts where fitted. We may decline an unsafe or unlawful route or activity and discuss practical alternatives. Weather, closures and other disruption require a review of safe alternatives and affected services; they are not automatically traveller cancellations.</p><h2>Transport and insurance</h2><p>Arranged vehicle transport has hiring-use motor insurance and passenger cover while in the vehicle, subject to policy terms, limits and exclusions. It does not provide off-vehicle or comprehensive personal travel cover. Suitable personal travel insurance is recommended; proof is not compulsory.</p><h2>Help during a confirmed trip</h2><p>Use the trip contact channels in your confirmation. Imran is the primary contact for trip emergencies, with Antony as backup. Tell us promptly about a service problem so we have an opportunity to help.</p>'],
        'drivers-vehicles' => ['Drivers and Vehicles', 'Agree the driver role, vehicle arrangements, luggage and practical needs before booking.',
            '<h2>Your agreed vehicle and service</h2><p>Transport may be provided by Island Culture Travels or independent transport partners. Your quote and confirmation identify the services being arranged. Discuss group size, luggage, arrival details and the planned route before accepting the quote.</p><h2>Driver and guide roles</h2><p>The driver provides the role stated in your confirmation. Chauffeur service does not by itself include a separately licensed tour guide. Ask for any separate guide service to be included expressly in the quote.</p><h2>Comfort and practical needs</h2><p>Tell us about child-seat, luggage or mobility needs before confirmation. A request alone does not confirm a particular vehicle feature or equipment. Check the written arrangements before accepting or paying.</p><h2>Vehicle cover and safe travel</h2><p>Arranged vehicle transport has hiring-use motor insurance and passenger cover in the vehicle, subject to policy terms, limits and exclusions. Follow reasonable safety instructions and use seat belts where fitted. Unsafe or unlawful routes or activities may be declined and practical alternatives discussed.</p>'],
        'how-booking-works' => ['How Booking Works', 'From your first enquiry to a reviewed quote, policy acceptance and written booking confirmation.',
            '<h2>1. Send your trip requirements</h2><p>Share your dates, group size, arrival details, interests, pace and practical needs. We aim to give a first reply within 24 hours; complete-plan timing is agreed separately. An enquiry does not create a booking.</p><h2>2. Review the plan and written quote</h2><p>Review the itinerary, services, inclusions and exclusions, price, currency, expiry and timezone, deposit and balance deadlines, conversion basis, refund currency, fees, cancellation rules and relevant supplier conditions. Ask for clarification before accepting or paying. A quote is not a supplier reservation.</p><h2>3. Accept the required current policies</h2><p>Accept the current booking, cancellation, payment and privacy policies at the agreed booking stage. We keep the accepted versions. Policy acceptance alone does not confirm a booking.</p><h2>4. Complete payment and availability checks</h2><p>The required funds must arrive, unless the accepted quote expressly states that no payment is due at confirmation. Supplier availability and final-service checks take place before written confirmation. We make no supplier commitments before issuing that confirmation. Payment alone does not confirm the trip.</p><h2>5. Receive written confirmation</h2><p>The operator issues written booking confirmation after the required checks. Keep it with your quote, itinerary, payment acknowledgement and accepted policies. Later changes require written review and agreement; a request alone does not amend a confirmed booking.</p>'],
        'tour-inclusions-exclusions' => ['Tour Inclusions and Exclusions', 'Use your written quote and confirmation to check exactly which private travel services are included.',
            '<h2>The written quote defines your services</h2><p>There is no universal inclusion list for every trip. Review the services, itinerary, price, currency, included or excluded taxes and charges, and relevant supplier conditions before accepting or paying. Your written confirmation records the agreed services.</p><h2>Check transport and guide arrangements</h2><p>Confirm the vehicle service, driver role, route and any practical requirements. Chauffeur service does not by itself include a separately licensed tour guide. Discuss child-seat and luggage needs before confirmation.</p><h2>Check each additional service</h2><p>Ask the team to identify whether accommodation, meals, entry tickets, permits, activities or any separate guide service are included in your particular quote. Do not assume an activity shown in an itinerary idea is included without checking the agreed services. Non-refundable supplier items and their amounts or calculation basis must be disclosed and agreed before acceptance and payment.</p><h2>Independent arrangements and insurance</h2><p>Independently arranged services are outside the services included in our confirmation. Vehicle passenger cover applies in the vehicle within policy terms; it is not personal travel insurance or off-vehicle cover. Suitable personal travel insurance is recommended.</p><h2>Changes and unused services</h2><p>Request changes in writing. Revised services, availability, price and relevant supplier charges must be explained and accepted before an amendment is confirmed. Refunds and voluntarily unused services follow the agreed cancellation and refund terms, without duplicate charges.</p>'],
    ];
    $result = [];
    foreach ($content as $slug => [$title, $description, $html]) {
        $result[$slug] = ['slug'=>$slug, 'title'=>$title, 'content'=>$html, 'meta_title'=>$title . ' | Island Culture Travels', 'meta_description'=>$description, 'faq_json'=>'', 'canonical_url'=>base_url($slug), 'is_published'=>0, 'source'=>'R2 proposal derived from approved R0 wording; owner review required'];
    }
    return $result;
}

/** Quill represents an empty optional field as HTML; retain the public fallback. */
function trust_page_normalize_optional_fields(array $page): array
{
    foreach (['cta_title','cta_text'] as $field) {
        if (is_string($page[$field] ?? null) && preg_replace('/[\s\p{Z}]+/u','',html_entity_decode(strip_tags($page[$field]),ENT_QUOTES|ENT_HTML5,'UTF-8'))==='') { $page[$field]=''; }
    }
    return $page;
}

/** Trust-specific publication gate; preserves the generic CMS draft workflow. */
function trust_page_publication_issues(array $page): array
{
    $issues=[];
    foreach (['title'=>150,'content'=>100000,'meta_title'=>255,'meta_description'=>320,'faq_json'=>20000,'slug'=>180,'canonical_url'=>255] as $field=>$limit) {
        $value=$page[$field] ?? '';
        if (!is_string($value) || !mb_check_encoding($value,'UTF-8') || mb_strlen($value)>$limit) { $issues[]='Invalid or oversized field: ' . $field . '.'; }
    }
    if ($issues) { return $issues; }
    $slug=(string)($page['slug'] ?? '');
    $slugs=array_merge(array_keys(published_static_page_trust_routes()),['privacy-policy']);
    if (!in_array($slug,$slugs,true)) { $issues[]='Unknown trust route.'; }
    foreach (['meta_title','meta_description'] as $field) { if (trim((string)($page[$field] ?? ''))==='') { $issues[]='Complete ' . $field . '.'; } }
    $canonical=trim((string)($page['canonical_url'] ?? ''));
    if (rtrim($canonical,'/')!==rtrim(base_url($slug),'/')) { $issues[]='Trust pages require their own canonical URL.'; }
    $reviewText=implode("\n",array_intersect_key($page,array_flip(['title','content','meta_title','meta_description'])));
    $reviewText=preg_replace('~<[^>]+>~',' ',$reviewText) ?? $reviewText;
    if (ict_policy_has_review_marker($reviewText)) { $issues[]='Complete owner review before publication.'; }
    if (preg_match('/<h1\b/i',(string)$page['content'])) { $issues[]='Use H2 headings in content; the public template supplies the H1.'; }
    $beforeFaq=count($issues);
    $faq=trim((string)($page['faq_json'] ?? ''));
    if ($faq!=='') {
        try { $items=json_decode($faq,true,32,JSON_THROW_ON_ERROR); }
        catch (JsonException) { return array_merge($issues,['FAQ JSON is invalid.']); }
        if (!is_array($items) || !array_is_list($items)) { $issues[]='FAQ JSON must be a list.'; }
        else {
            foreach ($items as $item) {
                $question=is_array($item)?($item['question'] ?? $item['q'] ?? null):null;
                $answer=is_array($item)?($item['answer'] ?? $item['a'] ?? null):null;
                if (!is_string($question) || !is_string($answer) || trim($question)==='' || trim($answer)==='') { $issues[]='Each FAQ needs a text question and answer.';break; }
            }
        }
    }
    if (count($issues)===$beforeFaq) {
        foreach (published_static_page_placeholder_issues($page) as $issue) { $issues[]=$issue['field'] . ': ' . $issue['reason']; }
    }
    return array_values(array_unique($issues));
}
