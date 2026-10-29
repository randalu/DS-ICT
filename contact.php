<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';
require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/published_page_links.php';

$errors = [];
ensure_contact_inquiries_extended_schema();
ensure_customer_accounts_schema();
$currentCustomer = customer_user();

$publishedItineraries = [];
if (db_table_exists('itineraries')) {
    $publishedClause = db_column_exists('itineraries', 'is_published') ? 'WHERE is_published = 1' : '';
    $publishedItineraries = fetch_all("
        SELECT id, name, days
        FROM itineraries
        {$publishedClause}
        ORDER BY sort_order ASC, days ASC, name ASC
    ");
}

$publishedItineraryMap = [];
foreach ($publishedItineraries as $itinerary) {
    $itineraryId = (int) ($itinerary['id'] ?? 0);
    if ($itineraryId > 0) {
        $publishedItineraryMap[$itineraryId] = $itinerary;
    }
}

$prefill = contact_prefill_from_query($_GET, $publishedItineraryMap);

$initialData = [];
foreach ($prefill as $key => $val) {
    $initialData[$key] = $val;
}
if (isset($_SESSION[OLD_INPUT_SESSION_KEY]) && is_array($_SESSION[OLD_INPUT_SESSION_KEY])) {
    foreach ($_SESSION[OLD_INPUT_SESSION_KEY] as $key => $val) {
        $initialData[$key] = $val;
    }
}
if (isset($initialData['interests']) && is_string($initialData['interests'])) {
    $initialData['interests'] = array_filter(array_map('trim', explode(',', $initialData['interests'])));
} elseif (!isset($initialData['interests'])) {
    $initialData['interests'] = [];
}
if (isset($initialData['preferred_itinerary_id'])) {
    $initialData['preferred_itinerary_id'] = (int) $initialData['preferred_itinerary_id'];
}

// Form configuration for the reusable wizard component
$formConfigData = [
    'id' => 'trip-inquiry',
    'steps' => [
        [
            'id' => 1,
            'title' => 'Contact Information',
            'fields' => [
                ['name' => 'name', 'label' => 'Name *', 'type' => 'text', 'required' => true, 'col' => 'col-12'],
                ['name' => 'email', 'label' => 'Email *', 'type' => 'email', 'required' => true, 'col' => 'col-12'],
                ['name' => 'phone', 'label' => 'Phone', 'type' => 'tel', 'placeholder' => 'Phone number', 'col' => 'col-md-6'],
                ['name' => 'whatsapp', 'label' => 'WhatsApp', 'type' => 'tel', 'placeholder' => 'WhatsApp number', 'col' => 'col-md-6'],
            ]
        ],
        [
            'id' => 2,
            'title' => 'Trip Details',
            'fields' => [
                ['name' => 'country', 'label' => 'Country', 'type' => 'text', 'placeholder' => 'Your country', 'col' => 'col-md-6'],
                ['name' => 'travel_dates', 'label' => 'Travel Dates', 'type' => 'text', 'placeholder' => 'e.g. 10-20 August', 'col' => 'col-md-6'],
                ['name' => 'adults_count', 'label' => 'Adults', 'type' => 'number', 'min' => 1, 'col' => 'col-md-4'],
                ['name' => 'children_count', 'label' => 'Children', 'type' => 'number', 'min' => 0, 'col' => 'col-md-4'],
                ['name' => 'group_size', 'label' => 'Group Notes', 'type' => 'text', 'placeholder' => 'e.g. family of 4', 'col' => 'col-md-4'],
            ]
        ],
        [
            'id' => 3,
            'title' => 'Trip Preferences',
            'fields' => [
                ['name' => 'preferred_itinerary_id', 'label' => 'Preferred Itinerary', 'type' => 'select', 'options' => array_values(array_filter(array_map(function($it) {
                    $id = (int)($it['id'] ?? 0);
                    if ($id <= 0) return null;
                    $days = (int)($it['days'] ?? 0);
                    $label = trim((string)($it['name'] ?? ''));
                    if ($days > 0) $label .= ' (' . $days . ' days)';
                    return ['value' => $id, 'label' => $label];
                }, $publishedItineraries))), 'col' => 'col-12'],
                ['name' => 'budget_min', 'label' => 'Budget Min', 'type' => 'number', 'placeholder' => '1500', 'col' => 'col-md-4'],
                ['name' => 'budget_max', 'label' => 'Budget Max', 'type' => 'number', 'placeholder' => '3000', 'col' => 'col-md-4'],
                ['name' => 'budget_currency', 'label' => 'Currency', 'type' => 'select', 'options' => [['value'=>'USD','label'=>'USD'],['value'=>'EUR','label'=>'EUR'],['value'=>'GBP','label'=>'GBP'],['value'=>'AUD','label'=>'AUD'],['value'=>'LKR','label'=>'LKR']], 'col' => 'col-md-4'],
                ['name' => 'accommodation_preference', 'label' => 'Accommodation Preference', 'type' => 'select', 'options' => [['value'=>'','label'=>'Select preference (optional)'],['value'=>'Budget','label'=>'Budget'],['value'=>'Mid-range','label'=>'Mid-range'],['value'=>'Luxury','label'=>'Luxury'],['value'=>'Boutique','label'=>'Boutique']], 'col' => 'col-12'],
                ['name' => 'interests', 'label' => 'Interests', 'type' => 'pills', 'options' => ['Culture', 'Safari', 'Beach', 'Honeymoon', 'Family', 'Adventure', 'Wellness'], 'col' => 'col-12'],
            ]
        ],
        [
            'id' => 4,
            'title' => 'Your Message',
            'fields' => [
                ['name' => 'message', 'label' => 'Message *', 'type' => 'textarea', 'rows' => 5, 'placeholder' => "Tell us about your dream trip...", 'required' => true, 'col' => 'col-12'],
            ]
        ],
    ],
    'validation' => [
        'name' => ['required' => true],
        'email' => ['required' => true, 'email' => true],
        'message' => ['required' => true],
    ],
    'onSubmit' => 'form.submit()',
    'initialData' => $initialData
];
$formConfig = json_encode($formConfigData);

function render_wizard_field_php(array $field): string {
    $name = e($field['name'] ?? '');
    $label = e($field['label'] ?? '');
    $type = $field['type'] ?? 'text';
    $placeholder = e($field['placeholder'] ?? '');
    $min = isset($field['min']) ? (int) $field['min'] : null;
    $required = !empty($field['required']);
    $col = e($field['col'] ?? 'col-12');
    $rows = isset($field['rows']) ? (int) $field['rows'] : 5;
    $idPrefix = 'contact-';
    $inputId = $idPrefix . $name;
    $errorId = 'error_' . $name;

    $html = '<div class="' . $col . '"><div class="wizard-field form-field">';
    $html .= '<label for="' . $inputId . '" class="form-label">' . $label . '</label>';

    if ($type === 'textarea') {
        $html .= '<textarea id="' . $inputId . '" name="' . $name . '" aria-describedby="' . $errorId . '" rows="' . $rows . '" class="form-control form-textarea" placeholder="' . $placeholder . '"></textarea>';
    } elseif ($type === 'select') {
        $options = $field['options'] ?? [];
        $html .= '<select id="' . $inputId . '" name="' . $name . '" class="form-select">';
        foreach ($options as $opt) {
            $optValue = e($opt['value'] ?? '');
            $optLabel = e($opt['label'] ?? '');
            $html .= '<option value="' . $optValue . '">' . $optLabel . '</option>';
        }
        $html .= '</select>';
    } elseif ($type === 'pills') {
        $options = $field['options'] ?? [];
        $html .= '<div class="d-flex flex-wrap gap-2">';
        foreach ($options as $option) {
            $opt = e($option);
            $html .= '<button type="button" class="wizard-interest-pill" data-pill="' . $opt . '">' . $opt . '</button>';
        }
        $html .= '</div>';
        $html .= '<input type="hidden" name="' . $name . '" value="">';
    } else {
        $minAttr = $min !== null ? ' min="' . $min . '"' : '';
        $html .= '<input type="' . $type . '" id="' . $inputId . '" name="' . $name . '" aria-describedby="' . $errorId . '" class="form-control" placeholder="' . $placeholder . '"' . $minAttr . '>';
    }

    $html .= '<div id="' . $errorId . '" class="wizard-error form-error" role="alert"></div>';
    $html .= '</div></div>';
    return $html;
}

if (!isset($_SESSION['contact_form_rendered_at'])) {
    $_SESSION['contact_form_rendered_at'] = time();
}

if (is_post()) {
    guard_post_size();
    require_csrf();

    $name = trim((string) post_input('name', ''));
    $email = trim((string) post_input('email', ''));
    $phone = trim((string) post_input('phone', ''));
    $whatsapp = trim((string) post_input('whatsapp', ''));
    $country = trim((string) post_input('country', ''));
    $travelDates = trim((string) post_input('travel_dates', ''));
    $groupSize = trim((string) post_input('group_size', ''));
    $adultsCount = max(0, (int) post_input('adults_count', 0));
    $childrenCount = max(0, (int) post_input('children_count', 0));
    $budgetMin = normalize_price_input(post_input('budget_min'));
    $budgetMax = normalize_price_input(post_input('budget_max'));
    $budgetCurrency = strtoupper(trim((string) post_input('budget_currency', 'USD')));
    $accommodationPreference = trim((string) post_input('accommodation_preference', ''));
    $preferredItineraryId = (int) post_input('preferred_itinerary_id', 0);
    $interests = trim((string) post_input('interests', ''));
    $message = trim((string) post_input('message', ''));
    $sourcePage = request_path();
    $preferredItineraryName = '';

    // Honeypot field should remain empty for real users.
    $honeypotValue = trim((string) post_input('website', ''));
    $formRenderedAt = (int) post_input('form_rendered_at', 0);
    $sessionRenderedAt = (int) ($_SESSION['contact_form_rendered_at'] ?? 0);
    if ($formRenderedAt <= 0) {
        $formRenderedAt = $sessionRenderedAt;
    }

    old_input_set($_POST);

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if ($message === '') {
        $errors[] = 'Message is required.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    $lengthLimits = [
        'name' => [$name, 150, 'Name'],
        'email' => [$email, 190, 'Email'],
        'phone' => [$phone, 50, 'Phone'],
        'whatsapp' => [$whatsapp, 80, 'WhatsApp'],
        'country' => [$country, 120, 'Country'],
        'travel_dates' => [$travelDates, 120, 'Travel dates'],
        'group_size' => [$groupSize, 50, 'Group notes'],
        'accommodation_preference' => [$accommodationPreference, 120, 'Accommodation preference'],
    ];
    foreach ($lengthLimits as [$value, $limit, $label]) {
        if ($value !== '' && mb_strlen($value) > $limit) {
            $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
        }
    }

    if ($preferredItineraryId > 0) {
        if (!isset($publishedItineraryMap[$preferredItineraryId])) {
            $errors[] = 'Please select a valid itinerary option.';
        } else {
            $preferredItineraryName = trim((string) ($publishedItineraryMap[$preferredItineraryId]['name'] ?? ''));
        }
    }

    if (!preg_match('/^[A-Z]{3}$/', $budgetCurrency)) {
        $budgetCurrency = 'USD';
    }

    if ($budgetMin !== null && $budgetMax !== null && $budgetMax < $budgetMin) {
        $errors[] = 'Budget max must be greater than or equal to budget min.';
    }

    $spamReason = '';
    $requestIp = request_ip_address();

    if ($honeypotValue !== '') {
        $spamReason = 'honeypot';
    }

    if ($spamReason === '' && $formRenderedAt > 0 && (time() - $formRenderedAt) < 3) {
        $spamReason = 'too_fast';
    }

    if ($spamReason === '' && form_submission_limit_exceeded('contact', $requestIp, 900, 5)) {
        $spamReason = 'rate_limit';
    }

    if (!$errors) {
        try {
            form_submission_log_hit('contact', $requestIp);

            $columnValueMap = [
                'name' => $name,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'whatsapp' => $whatsapp !== '' ? $whatsapp : null,
                'country' => $country !== '' ? $country : null,
                'travel_dates' => $travelDates !== '' ? $travelDates : null,
                'group_size' => $groupSize !== '' ? $groupSize : null,
                'adults_count' => $adultsCount > 0 ? $adultsCount : null,
                'children_count' => $childrenCount > 0 ? $childrenCount : null,
                'budget_min' => $budgetMin,
                'budget_max' => $budgetMax,
                'budget_currency' => ($budgetMin !== null || $budgetMax !== null) ? $budgetCurrency : null,
                'accommodation_preference' => $accommodationPreference !== '' ? $accommodationPreference : null,
                'preferred_itinerary_id' => $preferredItineraryId > 0 ? $preferredItineraryId : null,
                'preferred_itinerary_name' => $preferredItineraryName !== '' ? $preferredItineraryName : null,
                'interests' => $interests !== '' ? $interests : null,
                'message' => $message,
                'source_page' => $sourcePage,
                'inquiry_status' => $spamReason === '' ? 'new' : 'spam',
                'is_spam' => $spamReason === '' ? 0 : 1,
                'spam_reason' => $spamReason !== '' ? $spamReason : null,
                'tracking_meta_json' => json_encode([
                    'ip' => $requestIp,
                    'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300),
                    'referrer' => substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500),
                ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            ];

            if ($currentCustomer) {
                $columnValueMap['customer_user_id'] = (int) ($currentCustomer['id'] ?? 0);
            }

            $writeData = [];
            foreach ($columnValueMap as $column => $value) {
                if (db_column_exists('contact_inquiries', $column)) {
                    $writeData[$column] = $value;
                }
            }

            $columns = array_keys($writeData);
            $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

            execute_query(
                'INSERT INTO contact_inquiries (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
                $writeData
            );

            $inquiryId = (int) last_insert_id();
            $adminEmailSent = false;
            $customerEmailSent = false;
            $createdCustomerPlan = false;

            if ($spamReason === '' && $currentCustomer && $preferredItineraryId > 0) {
                $createdCustomerPlan = (bool) customer_create_plan_from_inquiry($inquiryId, (int) ($currentCustomer['id'] ?? 0));
            }

            if ($spamReason === '') {
                google_tag_event_queue('generate_lead', [
                    'lead_type' => 'tour_inquiry',
                    'source_page' => 'contact',
                    'logged_in' => (bool) $currentCustomer,
                    'preferred_itinerary_selected' => $preferredItineraryId > 0,
                ]);
                if ($createdCustomerPlan) {
                    google_tag_event_queue('itinerary_plan_started', [
                        'source_page' => 'contact',
                        'preferred_itinerary_selected' => true,
                    ]);
                }

                $mailPayload = $columnValueMap;
                $mailPayload['id'] = $inquiryId;
                $adminEmailSent = send_html_email(
                    site_contact_email(),
                    'New Inquiry #' . $inquiryId . ' - ' . APP_NAME,
                    render_contact_inquiry_email_html($mailPayload, false),
                    $email !== '' ? $email : null
                );

                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $customerEmailSent = send_html_email(
                        $email,
                        'We received your inquiry - ' . APP_NAME,
                        render_contact_inquiry_email_html($mailPayload, true)
                    );
                }

                $updateFlags = [];
                if (db_column_exists('contact_inquiries', 'email_sent_to_admin')) {
                    $updateFlags['email_sent_to_admin'] = $adminEmailSent ? 1 : 0;
                }
                if (db_column_exists('contact_inquiries', 'email_sent_to_customer')) {
                    $updateFlags['email_sent_to_customer'] = $customerEmailSent ? 1 : 0;
                }

                if ($updateFlags) {
                    $setSql = [];
                    foreach (array_keys($updateFlags) as $column) {
                        $setSql[] = $column . ' = :' . $column;
                    }
                    execute_query(
                        'UPDATE contact_inquiries SET ' . implode(', ', $setSql) . ' WHERE id = :id',
                        array_merge($updateFlags, ['id' => $inquiryId])
                    );
                }

                log_contact_inquiry_activity($inquiryId, 'created', null, 'new', 'Inquiry submitted from contact page.', null);
            } else {
                log_contact_inquiry_activity($inquiryId, 'spam_flagged', null, 'spam', 'Auto flagged by anti-spam checks: ' . $spamReason, null);
            }

            old_input_clear();
            $_SESSION['contact_form_rendered_at'] = time();

            flash_set('success', 'Your inquiry was sent successfully. We will get back to you soon.');
            flash_set('track_inquiry_cta', $currentCustomer ? 'account' : 'login');
            redirect('contact');
        } catch (Throwable $e) {
            $errors[] = 'Could not send your inquiry right now. Please try again later.';
        }
    }
}

$successMessage = flash_get('success');
$trackInquiryCta = flash_get('track_inquiry_cta');
$contactFormRenderedAt = (int) ($_SESSION['contact_form_rendered_at'] ?? time());

$seoTitle = 'Plan Your Private Sri Lanka Journey | ' . APP_NAME;
$seoDescription = 'Contact Island Culture Travels to arrange a private chauffeur-guided Sri Lanka tour with a comfortable vehicle and a personalized itinerary.';
$seoCanonical = base_url('contact');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';

$breadcrumbs = [
    ['name' => 'Home', 'url' => base_url()],
    ['name' => 'Contact', 'url' => $seoCanonical],
];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type' => 'ContactPage',
        'name' => 'Contact',
        'url' => $seoCanonical,
    ],
];
$bodyClass = 'page-contact';
require __DIR__ . '/includes/header.php';
?>

<section class="page-hero-banner has-media page-hero-contact">
    <?= responsive_image([
        'src' => DEFAULT_OG_IMAGE,
        'alt' => 'Contact Island Culture Travels',
        'widths' => [800, 1280, 1920],
        'sizes' => '100vw',
        'width' => 1920,
        'height' => 1080,
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'class' => 'page-hero-bg-img',
        'picture_class' => 'page-hero-media',
    ]) ?>
    <div class="container">
        <div class="hero-content">
            <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
            <span class="section-kicker" style="color:var(--accent-light);">Get In Touch</span>
            <h1 class="hero-title">Plan Your Private<br>Sri Lanka Journey</h1>
            <div class="hero-summary-box">
                <p class="lead mb-0">Share your travel dates and preferences. We'll send a first reply within 24 hours; timing for your tailored private plan is agreed separately.</p>
            </div>
        </div>
    </div>
</section>

<section class="contact-form-section py-5">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-7">
                <?php if ($successMessage): ?>
                    <div class="contact-alert contact-alert-success form-success" role="status">
                        <div class="contact-alert-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#2d6a41"/><path d="M6 10l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <div>
                            <div class="contact-alert-text"><?= e($successMessage) ?></div>
                            <?php if ($trackInquiryCta): ?>
                                <div class="mt-1">
                                    <a class="contact-alert-link" href="<?= e(base_url($trackInquiryCta === 'account' ? 'account' : 'account/login')) ?>">
                                        <?= $trackInquiryCta === 'account' ? 'View this inquiry in your account' : 'Track this inquiry with Google' ?> &rarr;
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="contact-alert contact-alert-error form-error" role="alert" style="display:block;background:rgba(220,53,69,0.06);border:1px solid rgba(220,53,69,0.18);color:#842029;">
                        <div class="contact-alert-icon">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="10" fill="#dc3545"/><path d="M7 7l6 6M13 7l-6 6" stroke="#fff" stroke-width="2" stroke-linecap="round"/></svg>
                        </div>
                        <div>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?= e($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="contact-info-card">
                    <span class="section-kicker">How It Works</span>
                    <h2 class="contact-info-title">Tell us about your trip</h2>
                    <p class="contact-info-text">We use this detail to prepare a route, vehicle, and hotel-level recommendation that fits your travel style.</p>
                    <div class="contact-info-meta">First reply within 24 hours; plan timing agreed separately &middot; Private chauffeur-guided travel &middot; Couples, families, and small groups</div>
                </div>

                <?php $bookingInformationLinks = published_booking_page_links(); ?>
                <?php if ($bookingInformationLinks): ?>
                    <div id="contact-booking-information" class="mb-4">
                        <h2 class="h5">Before you book</h2>
                        <p class="text-secondary mb-2">Read our booking information while planning your trip:</p>
                        <ul class="list-unstyled d-flex flex-wrap gap-3 mb-0">
                            <?php foreach ($bookingInformationLinks as $bookingLink): ?>
                                <li><a href="<?= e($bookingLink['url']) ?>"><?= e($bookingLink['label']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" id="contact-wizard-form" action="<?= e(base_url('contact')) ?>" class="inquiry-wizard">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_rendered_at" value="<?= (int) $contactFormRenderedAt ?>">
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="d-none" aria-hidden="true">

                    <!-- Wizard Progress Header -->
                    <div class="wizard-progress">
                        <div class="wizard-progress-steps">
                            <!-- Step 1 -->
                            <div class="wizard-step-item wizard-step-active" id="wizard-step-1">
                                <div class="wizard-step-dot"><span>1</span></div>
                                <div class="wizard-step-label">Contact</div>
                            </div>
                            <div class="wizard-step-line" id="wizard-line-1"></div>
                            <!-- Step 2 -->
                            <div class="wizard-step-item" id="wizard-step-2">
                                <div class="wizard-step-dot"><span>2</span></div>
                                <div class="wizard-step-label">Details</div>
                            </div>
                            <div class="wizard-step-line" id="wizard-line-2"></div>
                            <!-- Step 3 -->
                            <div class="wizard-step-item" id="wizard-step-3">
                                <div class="wizard-step-dot"><span>3</span></div>
                                <div class="wizard-step-label">Preferences</div>
                            </div>
                            <div class="wizard-step-line" id="wizard-line-3"></div>
                            <!-- Step 4 -->
                            <div class="wizard-step-item" id="wizard-step-4">
                                <div class="wizard-step-dot"><span>4</span></div>
                                <div class="wizard-step-label">Message</div>
                            </div>
                        </div>
                        <div class="wizard-progress-bar-track">
                            <div class="wizard-progress-bar-fill" id="wizard-progress-fill" style="width: 25%;"></div>
                        </div>
                    </div>

                    <div class="wizard-step-content wizard-step-visible" id="wizard-content-1">
                        <h3 class="h5 mb-3">Contact Information</h3>
                        <div class="row g-3">
                            <?php foreach ($formConfigData['steps'][0]['fields'] as $field): ?>
                                <?= render_wizard_field_php($field) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step-content" id="wizard-content-2">
                        <h3 class="h5 mb-3">Trip Details</h3>
                        <div class="row g-3">
                            <?php foreach ($formConfigData['steps'][1]['fields'] as $field): ?>
                                <?= render_wizard_field_php($field) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step-content" id="wizard-content-3">
                        <h3 class="h5 mb-3">Trip Preferences</h3>
                        <div class="row g-3">
                            <?php foreach ($formConfigData['steps'][2]['fields'] as $field): ?>
                                <?= render_wizard_field_php($field) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-step-content" id="wizard-content-4">
                        <h3 class="h5 mb-3">Your Message</h3>
                        <div class="row g-3">
                            <?php foreach ($formConfigData['steps'][3]['fields'] as $field): ?>
                                <?= render_wizard_field_php($field) ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-nav">
                        <div>
                            <button type="button" class="wizard-btn wizard-btn-back" id="wizard-back" style="display:none;">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M10 3L5 8l5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                Back
                            </button>
                        </div>
                        <div>
                            <button type="button" class="wizard-btn wizard-btn-next" id="wizard-next">
                                Next
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M6 3l5 5-5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" class="wizard-btn wizard-btn-submit" id="wizard-submit" style="display:none;">
                                Send Inquiry
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M3 8l4 4 6-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </div>
                </form>

                <script>
                (function() {
                    var currentStep = 1;
                    var totalSteps = 4;
                    var form = document.getElementById('contact-wizard-form');
                    var backBtn = document.getElementById('wizard-back');
                    var nextBtn = document.getElementById('wizard-next');
                    var submitBtn = document.getElementById('wizard-submit');
                    var progressFill = document.getElementById('wizard-progress-fill');

                    function updateWizardUI() {
                        // Update step indicators
                        for (var i = 1; i <= totalSteps; i++) {
                            var stepEl = document.getElementById('wizard-step-' + i);
                            var contentEl = document.getElementById('wizard-content-' + i);
                            if (i === currentStep) {
                                stepEl.classList.add('wizard-step-active');
                                stepEl.classList.remove('wizard-step-completed');
                                contentEl.classList.add('wizard-step-visible');
                                contentEl.style.display = 'block';
                            } else if (i < currentStep) {
                                stepEl.classList.remove('wizard-step-active');
                                stepEl.classList.add('wizard-step-completed');
                                contentEl.classList.remove('wizard-step-visible');
                                contentEl.style.display = 'none';
                            } else {
                                stepEl.classList.remove('wizard-step-active', 'wizard-step-completed');
                                contentEl.classList.remove('wizard-step-visible');
                                contentEl.style.display = 'none';
                            }
                        }
                        // Update lines
                        for (var j = 1; j < totalSteps; j++) {
                            var lineEl = document.getElementById('wizard-line-' + j);
                            if (j < currentStep) {
                                lineEl.classList.add('wizard-line-complete');
                            } else {
                                lineEl.classList.remove('wizard-line-complete');
                            }
                        }
                        // Update progress bar
                        var progress = (currentStep / totalSteps) * 100;
                        progressFill.style.width = progress + '%';
                        // Update buttons
                        backBtn.style.display = currentStep > 1 ? 'inline-flex' : 'none';
                        if (currentStep < totalSteps) {
                            nextBtn.style.display = 'inline-flex';
                            submitBtn.style.display = 'none';
                        } else {
                            nextBtn.style.display = 'none';
                            submitBtn.style.display = 'inline-flex';
                        }
                    }

                    function validateStep() {
                        var stepFields = {
                            1: ['name', 'email'],
                            2: [],
                            3: [],
                            4: ['message']
                        };
                        var fields = stepFields[currentStep] || [];
                        var valid = true;
                        for (var i = 0; i < fields.length; i++) {
                            var input = form.querySelector('[name="' + fields[i] + '"]');
                            if (input && !input.value.trim()) {
                                valid = false;
                                input.classList.add('wizard-input-invalid');
                            } else if (input) {
                                input.classList.remove('wizard-input-invalid');
                            }
                        }
                        return valid;
                    }

                    backBtn.addEventListener('click', function() {
                        if (currentStep > 1) {
                            currentStep--;
                            updateWizardUI();
                        }
                    });

                    nextBtn.addEventListener('click', function() {
                        if (validateStep() && currentStep < totalSteps) {
                            currentStep++;
                            updateWizardUI();
                        }
                    });

                    submitBtn.addEventListener('click', function() {
                        if (validateStep()) {
                            form.submit();
                        }
                    });

                    // Init
                    updateWizardUI();
                })();
                </script>
            </div>

            <div class="col-lg-5">
                <div class="contact-sidebar-card">
                    <div class="contact-sidebar-icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><circle cx="14" cy="14" r="14" fill="var(--brand-soft)"/><path d="M9 14h10M14 9v10" stroke="var(--brand)" stroke-width="2" stroke-linecap="round"/></svg>
                    </div>
                    <h2 class="contact-sidebar-title">What helps us quote accurately</h2>
                    <ul class="contact-sidebar-list">
                        <li>Approximate dates and duration</li>
                        <li>Adults and children count</li>
                        <li>Preferred regions or itinerary</li>
                        <li>Budget comfort range</li>
                        <li>Any special requests</li>
                    </ul>
                </div>

                <div class="contact-sidebar-card contact-sidebar-wa">
                    <div class="contact-sidebar-icon">
                        <svg width="28" height="28" viewBox="0 0 28 28" fill="none"><circle cx="14" cy="14" r="14" fill="rgba(37, 211, 102, 0.12)"/><path d="M9.5 18.5l.7-2.5c-.5-.8-.7-1.7-.6-2.6.1-1.7.9-3.2 2.2-4.1 1.3-.9 3-1.1 4.5-.5 1.5.5 2.6 1.7 3 3.2.4 1.5.1 3.1-.8 4.3-.9 1.2-2.2 2-3.7 2.1l-2.5.7h-.8z" stroke="var(--brand-green, #25D366)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <h2 class="contact-sidebar-title">Prefer WhatsApp?</h2>
                    <p class="contact-sidebar-text">Send your dates, traveler count, and places you want to visit. We will shape a route and suggest the right vehicle setup.</p>
                    <a href="<?= e(site_whatsapp_url(site_default_whatsapp_text())) ?>" target="_blank" rel="noopener" class="contact-wa-btn" data-track-event="whatsapp_click">
                        Chat on WhatsApp
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none"><path d="M4 12l-1 4 4-1c1.2.6 2.5 1 4 1 3.9 0 7-3.1 7-7s-3.1-7-7-7-7 3.1-7 7c0 1.5.4 2.8 1 4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
