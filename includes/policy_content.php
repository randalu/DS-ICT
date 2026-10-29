<?php
declare(strict_types=1);
require_once __DIR__ . '/cancellation_settings.php';
require_once __DIR__ . '/retention_settings.php';

/** Owner-approved wording, 14 October 2026; publication is a separate operation. */
function ict_policy_business_identity(): array
{
    return ['name' => defined('APP_NAME') && trim((string) APP_NAME) !== '' ? (string) APP_NAME : 'Island Culture Travels',
        'address' => '163/3 Niwasa Udyana Mawatha, Raddolugama 11390 (Katunayake)', 'contracting_person' => 'Imran Mihiran',
        'email' => 'hello@islandculturetravels.com', 'phone' => '+94 77 949 2427', 'whatsapp' => '+94 76 919 2427'];
}

function ict_policy_versions(): array
{
    return ['booking_terms' => '2026-10-14-1',
        'cancellation_refund' => '2026-10-14-' . substr(hash('sha256', ict_cancellation_draft_html()), 0, 8),
        'payment_deposit' => '2026-10-14-1',
        'privacy' => '2026-10-23-' . substr(hash('sha256', ict_retention_policy_html()), 0, 8)];
}

function ict_policy_required_keys(): array
{
    return ['booking_terms', 'cancellation_refund', 'payment_deposit', 'privacy'];
}

/** Legacy unreviewed pages still fail the publication gate. */
function ict_policy_has_review_marker(string $content): bool
{
    $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return preg_match('/\bworking\s+draft\s+for\s+review\b/i', $text) === 1;
}

function ict_policy_documents(): array
{
    $identity = ict_policy_business_identity(); $versions = ict_policy_versions();
    $party = e($identity['contracting_person']) . ', trading as ' . e($identity['name']);
    $address = e($identity['address']); $email = e($identity['email']); $phone = e($identity['phone']); $whatsapp = e($identity['whatsapp']);
    $contact = "<p>{$party}<br>{$address}<br>Email: {$email}<br>WhatsApp: {$whatsapp}<br>Calls: {$phone}<br>Imran is primary for confirmations, refunds, complaints and trip emergencies; Antony is the backup.</p>";
    $effective = '<p>Effective version: 14 October 2026. Keep these terms with your written quote, itinerary and booking confirmation. The published version accepted for your booking and any expressly agreed booking-specific variations apply, subject to rights that cannot lawfully be excluded.</p>';
    $confirmation = '<p>We review the final itinerary and provide the written quote, services, price and currency, payment schedule, cancellation rules and relevant supplier conditions. You accept the required current policies; the required payment is received, or the accepted quote expressly states that no payment is due at confirmation. We complete supplier availability and final-service checks before issuing written booking confirmation. An enquiry, policy acceptance, itinerary label or payment alone is not booking confirmation. A no-payment-due term does not waive later payments stated in the quote. We make no supplier commitments before issuing written confirmation.</p>';
    $support = "<p>Phone and WhatsApp enquiries are available 24/7 in Sri Lanka time. We aim to give a first reply within 24 hours; complete-plan timing is agreed separately. Confirmed trips have 24/7 emergency support through WhatsApp {$whatsapp} and calls {$phone}. Contact Imran first; Antony is the backup if further assistance or escalation is needed.</p>";
    $insurance = '<p>Vehicle transport arranged for your trip includes hiring-use motor insurance and passenger cover while you are in the vehicle, subject to the applicable policy terms, limits and exclusions. This is not a promise of cover for off-vehicle activities or a replacement for personal travel insurance. We recommend suitable personal travel insurance for medical care, cancellation, baggage, disruption and planned activities. Proof of personal travel insurance is not a compulsory booking requirement.</p>';
    $retention = ict_retention_policy_html();
    return [
        'booking_terms' => [
            'slug'=>'booking-terms-and-conditions','title'=>'Booking Terms and Conditions',
            'meta_title'=>'Booking Terms and Conditions | Island Culture Travels',
            'meta_description'=>'Booking confirmation, quotes, payments, insurance, changes and support for private Sri Lanka travel.',
            'version'=>$versions['booking_terms'],'content'=><<<HTML
{$effective}
<h2>1. Your travel service and agreement</h2>
<p>{$party}, at {$address}, arranges private Sri Lanka travel planning, chauffeur-guided journeys, transfers and related services. Vehicles and drivers may be supplied by us or transport partners. Your written quote and confirmation identify the services, inclusions, exclusions, dates, price and booking-specific terms. Ask us to clarify them before accepting or paying. A person booking for a group must have authority to provide its details and agree its arrangements; minors travel under a responsible adult's arrangements.</p>
<h2>2. When a booking is confirmed</h2>
{$confirmation}
<p>Each quote has its own expiry date and timezone. An unexpired quote does not reserve supplier availability. If checks fail, we cannot confirm, or you withdraw before written confirmation, payments received are returned under the cancellation and refund policy without a confirmed-trip cancellation charge or supplier-commitment deduction.</p>
<h2>3. Traveller information and practical needs</h2>
<p>Provide accurate contact details, travel dates, passenger count, age when needed, country of origin, arrival details and planning preferences. Tell us about practical assistance needs early so we can discuss suitable arrangements. Check the names, dates and services in your documents. You are responsible for passports, visas, entry requirements and health preparations applicable to your journey. Avoid sending unnecessary identity, medical or financial documents through ordinary enquiry messages.</p>
<h2>4. Itineraries, suppliers and changes</h2>
<p>Travel times and arrangements depend on roads, weather, closures, attraction schedules and supplier availability. Requested services are confirmed only when included in our written confirmation. We will explain a significant change and discuss suitable alternatives, any price adjustment or refund for affected services. Amendments require written agreement; a request alone does not change a confirmed booking. Supplier conditions or non-refundable items must be explained and agreed before they affect your booking.</p>
<p>A driver provides the role stated in your confirmation; chauffeur service does not by itself include a separately licensed tour guide. Follow reasonable safety instructions, use seat belts where fitted and tell us about child-seat or luggage needs before confirmation. We may decline an unsafe or unlawful route or activity and will discuss practical alternatives. Independently arranged services are outside the services included in our confirmation.</p>
<h2>5. Insurance</h2>
{$insurance}
<h2>6. Price and payment</h2>
<p>Your quote states currency, taxes or charges included or excluded, deposit amount and due date, balance deadline, transfer/conversion fee allocation, conversion-rate basis, refund currency and late-payment consequences before acceptance and payment. We accept direct bank deposits, bank transfers and Western Union in USD, LKR or EUR. We do not currently offer an online card gateway. Deposits are part-payment unless a different treatment is expressly disclosed and agreed; there is no universal deposit percentage or automatic late-payment penalty in these terms.</p>
<h2>7. Cancellations, disruption and complaints</h2>
<p>Written changes and cancellations follow our cancellation and refund policy. Tell us promptly about a service problem so we have an opportunity to help. For a formal complaint, email {$email} with your booking reference, relevant dates, a clear description and supporting information. Imran handles the matter, with Antony as backup. We will acknowledge your complaint, investigate and explain the response and any information still needed.</p>
<p>If weather, closures, government action or another event beyond reasonable control disrupts a trip, we will discuss safe alternatives and explain the treatment of affected services, payments and recoverable costs. Such disruption is not automatically treated as your cancellation and does not remove mandatory rights.</p>
{$support}
<h2>8. Responsibility and governing law</h2>
<p>We provide our agreed services and arrange relevant third-party services with reasonable care and skill. Nothing here excludes liability or consumer rights that cannot lawfully be excluded. Sri Lankan law governs these terms, subject to any mandatory law that applies to you. Please contact us first to seek a practical resolution; unresolved matters may be referred to an appropriate authority or a court with jurisdiction. An invalid provision does not invalidate the remaining lawful terms.</p>
<h2>9. Contact and later versions</h2>
<p>We may update terms for future bookings. We retain the version accepted for an existing booking; a later website update does not silently replace it. A change to an existing agreement requires your agreement or an applicable legal requirement.</p>
{$contact}
HTML],
        'cancellation_refund' => [
            'slug'=>'cancellation-refund-policy','title'=>'Cancellation and Refund Policy',
            'meta_title'=>'Cancellation and Refund Policy | Island Culture Travels',
            'meta_description'=>'Written cancellation notice, disclosed supplier costs, refunds and processing times for confirmed Sri Lanka travel.',
            'version'=>$versions['cancellation_refund'],'content'=>ict_cancellation_draft_html()],
        'payment_deposit' => [
            'slug'=>'payment-deposit-policy','title'=>'Payment and Deposit Policy',
            'meta_title'=>'Payment and Deposit Policy | Island Culture Travels',
            'meta_description'=>'Quote-specific deposits, balances, payment methods, receipts and refunds for private Sri Lanka travel.',
            'version'=>$versions['payment_deposit'],'content'=><<<HTML
{$effective}
<h2>1. Quote, price and currency</h2>
<p>Before accepting or paying, review your written quote's services, exclusions, price, currency, taxes or other charges, expiry and timezone, payment schedule, conversion-rate basis, refund currency and transfer/conversion fee allocation. We accept USD, LKR and EUR. Availability is checked again before written confirmation; quote expiry does not guarantee a reservation. A material price or service change requires a revised quote or written agreement.</p>
<h2>2. Payment methods and instructions</h2>
<p>Direct bank deposits, bank transfers and Western Union are accepted. We do not currently offer an online card payment gateway. Use the booking-specific payment instructions we provide and confirm unexpected changes through our established contact channels. We never need your password, one-time code or card security details. Bank or beneficiary details are supplied privately, not in this policy.</p>
<h2>3. Deposit, balance and booking confirmation</h2>
<p>Your quote sets the deposit amount, deposit due date and balance deadline. Amounts and deadlines are booking-specific; there is no universal deposit percentage. A deposit is part of the price and of the cancellation calculation, rather than an extra cancellation fee, unless a different treatment was expressly disclosed and agreed.</p>
{$confirmation}
<h2>4. Funds received and receipts</h2>
<p>Payment is received when funds arrive, not merely when a transfer instruction or sender screenshot is produced. We send a written acknowledgement by email and a WhatsApp copy when agreed with you. Keep the acknowledgement and payment reference. Imran handles payments and confirmations, with Antony as backup.</p>
<h2>5. Late or failed payment</h2>
<p>The quote explains late- or failed-payment consequences before acceptance. Contact us promptly if a payment may be delayed. We will explain any effect on availability or the booking; no undisclosed automatic penalty is created by this policy. Supplier consequences must follow the agreed terms and our rule against supplier commitments before written confirmation.</p>
<h2>6. Refunds</h2>
<p>Refund eligibility and any agreed supplier-cost exception follow the cancellation and refund policy without double deductions. Before written confirmation, payments are returned if the booking cannot proceed or you withdraw, without confirmed-trip cancellation charges or supplier-commitment deductions. Any quote-disclosed transfer or conversion fee treatment remains separate.</p>
<p>We provide a written assessment within 5 business days of receiving cancellation notice, counted from the next business day. This is separate from the cancellation policy's processing target, counted from the next business day after the amount is agreed in writing and necessary return-payment details are received. We explain unresolved amounts and process an agreed undisputed portion without waiting for unrelated supplier reconciliation. Business days exclude weekends and Sri Lankan public holidays.</p>
<p>We use the original payment channel where feasible or agree a documented alternative. Refund currency, conversion basis and fees follow the pre-disclosed quote. Bank processing may add time; we tell you the expected processing date and applicable mandatory deadlines still apply.</p>
<h2>7. Contact</h2>
{$contact}
HTML],
        'book_with_confidence' => [
            'slug'=>'book-with-confidence','title'=>'Book with Confidence',
            'meta_title'=>'Book with Confidence | Island Culture Travels',
            'meta_description'=>'Clear quotes, written confirmation, insured vehicle transport and personal support for your Sri Lanka journey.',
            'version'=>'2026-10-14-1','content'=><<<HTML
<h2>1. Tell us about your journey</h2>
<p>Share your dates, group size, arrival details, interests, pace and practical needs. We aim to give a first reply within 24 hours; complete-plan timing is agreed separately.</p>
<h2>2. Review a practical plan and clear quote</h2>
<p>Review your route, vehicle and services together with inclusions, exclusions, price and currency, expiry, payment dates, fees, conversion basis, refund currency and cancellation conditions. Ask us to explain anything unclear before accepting or paying. A quote is not a supplier reservation.</p>
<h2>3. Accept the current terms and complete the agreed checks</h2>
{$confirmation}
<h2>4. Travel with insured vehicle transport</h2>
{$insurance}
<h2>5. Changes, refunds and support</h2>
<p>Use written notice for changes or cancellation. The cancellation policy sets the approved charge bands and any pre-disclosed supplier-cost exception. We provide a refund assessment within 5 business days; payment processing has a separate target and start date. Costs are itemised and are not deducted twice.</p>
{$support}
<h2>6. Your contact team</h2>
{$contact}
HTML],
        'privacy' => [
            'slug'=>'privacy-policy','title'=>'Privacy Policy',
            'meta_title'=>'Privacy Policy | Island Culture Travels',
            'meta_description'=>'How Island Culture Travels handles enquiries, bookings, accounts, privacy choices, records and data requests.',
            'version'=>$versions['privacy'],'content'=><<<HTML
<p>Effective version: 23 October 2026. This notice describes information handled for our Sri Lanka travel service and website.</p>
<h2>1. Operator and privacy contact</h2>
<p>{$party}, at {$address}, is your contact for information handled for these services. Send privacy questions or requests to {$email}. Imran is primary; Antony is the backup.</p>
<h2>2. Information and purposes</h2>
<p>Depending on the feature used, we receive contact details, country, travel dates, passenger count, age when needed, planning preferences, messages, account identifiers, itinerary changes, payment references, technical logs and recorded choices. We use information to answer enquiries, prepare and manage requested services, communicate changes, operate accounts, prevent abuse, maintain security and keep necessary business or legal records. Policy acceptance evidence includes the accepted versions and snapshot, time, IP address and browser user-agent. Avoid sending unnecessary passport, bank or medical details in ordinary messages.</p>
<h2>3. Sharing and service providers</h2>
<p>For routine supplier planning we share travel dates, passenger count, age when required and country of origin with relevant accommodation, transport or activity providers. If another specific detail is needed for a service, we explain the need and recipient before requesting or sharing it. Website collection is separate from supplier sharing.</p>
<p>Hosting and email are supplied through Randalu Web Solutions. We also use email and spreadsheet working records and relevant travel/payment providers for the service requested. Provider infrastructure or support may involve processing outside Sri Lanka; naming a local provider does not mean all processing is local. We disclose relevant additional provider handling when a feature or service needs it. We may disclose information where a lawful obligation requires it. We do not treat a booking as permission for unrelated marketing.</p>
<h2>4. Website choices and optional services</h2>
<p>Essential session, account and security functions support the website. First-party website statistics operate for public page visits regardless of optional-service choices. They count pageviews, browser sessions, visits and enquiry-link clicks, with public URL paths without queries, country when available, coarse device/browser/OS categories and referrer domains. Random keys use the existing website session; IP addresses and full browser strings are not stored in these statistics. Privacy Choices lets you separately choose optional Google analytics, translation, reviews and configured chat, and change or reject those choices. The current preference lasts up to 180 days; expiry or a changed purpose revision requires a new choice. Operator switches can keep a service unavailable even when you choose it. Booking-policy acknowledgement and optional marketing are separate from these website choices.</p>
<p>Optional features, when enabled, may involve Google Tag Manager/analytics, GTranslate, Trustpilot, TripAdvisor or the chat provider identified in the relevant notice. Google sign-in, if offered, uses a separate sign-in action. Operator settings determine availability; allowing a feature does not enable a disabled provider. Links you choose to follow to other websites are governed by those sites' own privacy practices. Shared fonts and libraries are served locally.</p>
<h2>5. Optional AI search</h2>
<p>Trip listings and contact planning work without AI. If AI search is enabled, its page explains that selecting Search with AI sends your submitted text to OpenRouter and its selected model provider to match website content. Avoid private information. Typing a query, opening an old link or choosing another optional service does not itself send an AI request. The public search application does not save query text in booking, analytics or request-limit tables. Session counters and an IP-derived bucket limit abuse; the bucket is pseudonymous, not anonymous. Host and provider logging may differ, so POST is not a promise of zero retention or no training. Provider-specific handling is disclosed before enabling the feature.</p>
<h2>6. Retention and security</h2>
{$retention}
<p>We use reasonable technical and organisational precautions to protect information and avoid unnecessary copies. Access should be limited to people who need information for the service or record-handling role. No system or transmission can be guaranteed absolutely secure.</p>
<h2>7. Your requests and choices</h2>
<p>Contact {$email} to ask about access, correction, deletion, objection, restriction, withdrawal of optional consent or a privacy complaint, as applicable. We may ask for proportionate information to verify identity and locate records. We respond within applicable deadlines and explain any lawful retention, refusal or extension. Withdrawing an optional choice stops future use for that purpose and does not automatically remove records needed for a booking or legal obligation. You may also use the relevant regulatory or legal complaint route where available.</p>
<h2>8. Updates and contact</h2>
<p>We update this notice when relevant handling changes. Published policy versions and accepted records are retained; a later website change does not rewrite past acceptance evidence. Contact us if you need clarification about a service, provider or request.</p>
{$contact}
HTML],
    ];
}
