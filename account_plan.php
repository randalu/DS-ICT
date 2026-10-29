<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$customer = customer_require_auth();
$planId = (int) get_input('id', 0);
$plan = find_customer_plan($planId, $customer);
require_once __DIR__ . '/includes/quotation_booking.php';
$quotationBinding = $plan ? quote_booking_binding($planId) : null;
$linkedQuotation = $quotationBinding ? quote_get((int) $quotationBinding['revision_id']) : null;

if (!$plan) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$days = fetch_customer_plan_days($planId);
$requests = fetch_customer_plan_change_requests($planId);
$requestsByDay = group_customer_change_requests_by_day($requests);
$messages = fetch_contact_inquiry_messages((int) ($plan['contact_inquiry_id'] ?? 0), true);
$policyState = ict_policy_plan_acceptance_state($plan);
$bookingStatus = trim((string) ($plan['booking_status'] ?? 'planning'));

$seoTitle = 'My Itinerary Plan | ' . APP_NAME;
$seoDescription = 'Private customer itinerary planning workspace.';
$seoCanonical = base_url('account/plans/' . $planId);
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,nofollow';
$bodyClass = 'page-account-plan';

require __DIR__ . '/includes/header.php';
?>

<section class="account-hero py-5">
    <div class="container">
        <div class="account-hero-card">
            <span class="section-kicker">Planning Workspace</span>
            <h1><?= e((string) $plan['plan_title']) ?></h1>
            <p>
                <?= !empty($plan['travel_dates']) ? 'Travel dates: ' . e((string) $plan['travel_dates']) : 'Travel dates are not confirmed yet.' ?>
                <?php if (!empty($plan['trip_support_type'])): ?>
                    <br>Trip support: <?= e(customer_trip_support_type_label((string) $plan['trip_support_type'])) ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container account-container">
        <?php if ($error = flash_get('error')): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success = flash_get('success')): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 mb-4">
            <a href="<?= e(base_url('account')) ?>" class="btn btn-outline-dark btn-sm">Back to Account</a>
            <a href="#day-by-day" class="btn btn-outline-dark btn-sm">Day by Day</a>
            <a href="#requests" class="btn btn-outline-dark btn-sm">Requests</a>
            <a href="#messages" class="btn btn-outline-dark btn-sm">Messages</a>
            <a href="#inquiry" class="btn btn-outline-dark btn-sm">Original Inquiry</a>
        </div>

        <div class="account-plan-stat-grid mb-4">
            <div><strong><?= e(customer_plan_status_label((string) $plan['plan_status'])) ?></strong><span>Status</span></div>
            <div><strong><?= count($days) ?></strong><span>Plan days</span></div>
            <div><strong><?= count(array_filter($requests, static fn(array $r): bool => (string) ($r['request_status'] ?? '') === 'pending')) ?></strong><span>Pending requests</span></div>
            <div><strong><?= e((string) ($plan['traveler_summary'] ?: 'Private tour')) ?></strong><span>Travelers</span></div>
            <div><strong><?= e(ict_booking_status_label($bookingStatus)) ?></strong><span>Booking status</span></div>
        </div>

        <?php if ($linkedQuotation): $quotationDoc = $linkedQuotation['document']; $quotationOption = $quotationBinding['option_key']; ?>
            <section class="account-card mb-4"><h2>Agreed quotation</h2><p><?= e($linkedQuotation['reference']) ?> R<?= (int) $linkedQuotation['revision'] ?> · Option <?= e($quotationOption) ?></p><p><strong><?= e($quotationDoc['currency']) ?> <?= quote_money(quote_option_total($quotationDoc, $quotationOption)) ?> whole-group total</strong><br><?= e($quotationDoc['options'][$quotationOption]['label']) ?></p><a class="btn btn-outline-success" href="<?= e(base_url('account_quotation_pdf.php?plan_id=' . $planId)) ?>" target="_blank" rel="noopener">Review quotation PDF and inclusions</a></section>
        <?php endif; ?>
        <?php if ($bookingStatus === 'awaiting_acceptance' && $policyState['available']): ?>
            <section id="policy-acceptance" class="account-card mb-4 border-success">
                <span class="section-kicker">Final Plan Review</span>
                <h2>Accept the current policy versions</h2>
                <p class="text-secondary">Read each approved policy before accepting. Your acceptance records the versions shown here; written confirmation and payment instructions still apply to the final booking.</p>
                <form method="post" action="<?= e(base_url('account/plans/' . $planId . '/policy-accept')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="plan_id" value="<?= $planId ?>">
                    <input type="hidden" name="policy_snapshot_hash" value="<?= e(ict_policy_acceptance_snapshot($policyState['documents'])['hash']) ?>">
                    <input type="hidden" name="quotation_token" value="<?= e(quote_customer_token($planId)) ?>">
                    <div class="vstack gap-2 mb-3">
                        <?php foreach ([
                            'booking_terms' => 'I accept the Booking Terms and Conditions',
                            'cancellation_refund' => 'I accept the Cancellation and Refund Policy',
                            'payment_deposit' => 'I accept the Payment and Deposit Policy',
                            'privacy' => 'I have read the Privacy Policy',
                        ] as $key => $label): ?>
                            <?php $document = $policyState['documents'][$key] ?? []; ?>
                            <label class="form-check">
                                <input class="form-check-input" type="checkbox" name="<?= e('accept_' . ($key === 'cancellation_refund' ? 'cancellation_policy' : ($key === 'payment_deposit' ? 'payment_policy' : ($key === 'privacy' ? 'privacy_policy' : 'booking_terms')))) ?>" value="1" required>
                                <span class="form-check-label">
                                    <?= e($label) ?>
                                    <?php if (!empty($document['slug'])): ?>
                                        <a href="<?= e(base_url((string) $document['slug'])) ?>" target="_blank" rel="noopener">Read policy</a>
                                    <?php endif; ?>
                                    <small class="d-block text-secondary">Version <?= e((string) ($document['version'] ?? '')) ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="marketing_consent" value="1">
                        <span class="form-check-label">Send me optional travel-planning updates. This is separate from booking acceptance.</span>
                    </label>
                    <p class="small text-secondary">The acceptance record stores the policy versions and snapshot, timestamp, IP address, and browser details described in the Privacy Policy.</p>
                    <button class="btn btn-success">Record Policy Acceptance</button>
                </form>
            </section>
        <?php elseif ($bookingStatus === 'awaiting_acceptance'): ?>
            <section id="policy-acceptance" class="account-card mb-4 border-warning">
                <span class="section-kicker">Policy Review Pending</span>
                <h2>Policy acceptance is not active yet</h2>
                <p class="text-secondary mb-0">The policy pages for this plan are still being reviewed. This plan is not confirmed, and no acceptance is being requested yet.</p>
            </section>
        <?php elseif ($bookingStatus === 'accepted' && $policyState['accepted'] && $policyState['current_versions']): ?>
            <section id="policy-acceptance" class="account-card mb-4 border-success">
                <span class="section-kicker">Acceptance Recorded</span>
                <h2>Awaiting written booking confirmation</h2>
                <p class="text-secondary">Policy versions were recorded at <?= e($policyState['accepted_at']) ?>. Your booking is not confirmed until Island Culture Travels issues written confirmation.</p>
                <ul class="small text-secondary mb-0">
                    <?php foreach (['booking_terms' => 'Booking terms', 'cancellation_refund' => 'Cancellation', 'payment_deposit' => 'Payment', 'privacy' => 'Privacy'] as $key => $label): ?>
                        <li><?= e($label) ?> version: <code><?= e((string) ($policyState['versions'][$key] ?? '')) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php elseif ($bookingStatus === 'accepted' && $policyState['accepted']): ?>
            <section id="policy-acceptance" class="account-card mb-4 border-warning">
                <span class="section-kicker">Policy Update</span>
                <h2>Updated policy versions require review</h2>
                <p class="text-secondary mb-0">Previously accepted versions are retained. Island Culture Travels will provide the current versions for review before written confirmation.</p>
            </section>
        <?php elseif ($bookingStatus === 'confirmed' && $policyState['accepted']): ?>
            <section id="policy-acceptance" class="account-card mb-4 border-success">
                <span class="section-kicker">Booking Confirmed</span>
                <h2>Written confirmation issued</h2>
                <p class="text-secondary">Your policy acceptance was recorded at <?= e($policyState['accepted_at']) ?>. Refer to the customer-visible confirmation message for the agreed service and payment details.</p>
                <ul class="small text-secondary mb-0">
                    <?php foreach (['booking_terms' => 'Booking terms', 'cancellation_refund' => 'Cancellation', 'payment_deposit' => 'Payment', 'privacy' => 'Privacy'] as $key => $label): ?>
                        <li><?= e($label) ?> version: <code><?= e((string) ($policyState['versions'][$key] ?? '')) ?></code></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif; ?>

        <section id="day-by-day" class="account-card mb-4">
            <h2>Day-by-Day Plan</h2>
            <p class="text-secondary">Request changes below. Admin approval is required before confirmed day content changes.</p>

            <div class="account-plan-days">
                <?php foreach ($days as $day): ?>
                    <?php $dayId = (int) $day['id']; ?>
                    <article class="account-plan-day">
                        <div class="account-plan-day-head">
                            <span class="day-number"><?= e($quotationBinding ? 'Day ' . (int) $day['day_number'] . ((int) ($day['day_end'] ?? $day['day_number']) !== (int) $day['day_number'] ? '-' . (int) $day['day_end'] : '') : day_range_label((int) $day['day_number'], isset($day['day_end']) ? (int) $day['day_end'] : null)) ?></span>
                            <div>
                                <h3><?= e((string) $day['title']) ?></h3>
                                <div class="small text-secondary">
                                    <?= !empty($day['overnight_location']) ? 'Overnight: ' . e((string) $day['overnight_location']) : '' ?>
                                    <?= !empty($day['travel_time']) ? ' | Travel time: ' . e((string) $day['travel_time']) : '' ?>
                                </div>
                            </div>
                        </div>
                        <?php if (!empty($day['image_path'])): ?>
                            <?= responsive_image([
                                'src' => stored_media_url((string) $day['image_path']),
                                'alt' => (string) $day['title'],
                                'widths' => [400, 800],
                                'sizes' => '100vw',
                                'width' => 800,
                                'height' => 533,
                                'class' => 'account-plan-day-image',
                                'loading' => 'lazy',
                            ]) ?>
                        <?php endif; ?>
                        <?php if (!empty($day['summary'])): ?>
                            <div class="content-prose"><?= render_public_content((string) $day['summary']) ?></div>
                        <?php endif; ?>

                        <?php foreach (($requestsByDay[$dayId] ?? []) as $request): ?>
                            <div class="account-request-note">
                                <strong><?= e(customer_change_request_status_label((string) $request['request_status'])) ?></strong>
                                <span><?= e((string) ($request['created_at'] ?? '')) ?></span>
                                <?php if (!empty($request['request_reason'])): ?>
                                    <div><?= nl2br(e((string) $request['request_reason'])) ?></div>
                                <?php endif; ?>
                                <?php if (!empty($request['admin_response'])): ?>
                                    <div class="text-secondary">Admin: <?= nl2br(e((string) $request['admin_response'])) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <details class="account-change-form-wrap">
                            <summary>Request change for this day</summary>
                            <form method="post" action="<?= e(base_url('account/plans/' . $planId . '/days/' . $dayId . '/request')) ?>" class="account-change-form">
                                <?= csrf_field() ?>
                                <label for="req-title-<?= $dayId ?>" class="form-label">Suggested title</label>
                                <input type="text" id="req-title-<?= $dayId ?>" name="requested_title" class="form-control" maxlength="255" value="<?= e((string) $day['title']) ?>">
                                <label for="req-summary-<?= $dayId ?>" class="form-label">Suggested activities / summary</label>
                                <textarea id="req-summary-<?= $dayId ?>" name="requested_summary" rows="4" maxlength="<?= CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH ?>" class="form-control"><?= e(strip_tags((string) ($day['summary'] ?? ''))) ?></textarea>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="req-overnight-<?= $dayId ?>" class="form-label">Overnight location</label>
                                        <input type="text" id="req-overnight-<?= $dayId ?>" name="requested_overnight_location" class="form-control" maxlength="180" value="<?= e((string) ($day['overnight_location'] ?? '')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="req-travel-<?= $dayId ?>" class="form-label">Travel time note</label>
                                        <input type="text" id="req-travel-<?= $dayId ?>" name="requested_travel_time" class="form-control" maxlength="120" value="<?= e((string) ($day['travel_time'] ?? '')) ?>">
                                    </div>
                                </div>
                                <label for="req-reason-<?= $dayId ?>" class="form-label">Why do you want this change?</label>
                                <textarea id="req-reason-<?= $dayId ?>" name="request_reason" rows="3" maxlength="<?= CUSTOMER_CHANGE_REASON_MAX_LENGTH ?>" class="form-control" placeholder="Tell us what you prefer, want to avoid, or need adjusted."></textarea>
                                <button class="btn btn-success mt-3">Send Change Request</button>
                            </form>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="requests" class="account-card mb-4">
            <h2>Change Requests</h2>
            <?php if ($requests): ?>
                <div class="vstack gap-3">
                    <?php foreach ($requests as $request): ?>
                        <div class="account-request-note">
                            <strong><?= e(day_range_label((int) $request['day_number'], isset($request['day_end']) ? (int) $request['day_end'] : null)) ?> - <?= e(customer_change_request_status_label((string) $request['request_status'])) ?></strong>
                            <div class="small text-secondary"><?= e((string) ($request['created_at'] ?? '')) ?></div>
                            <?php if (!empty($request['request_reason'])): ?><div><?= nl2br(e((string) $request['request_reason'])) ?></div><?php endif; ?>
                            <?php if (!empty($request['admin_response'])): ?><div class="text-secondary">Admin: <?= nl2br(e((string) $request['admin_response'])) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-secondary mb-0">No change requests yet.</p>
            <?php endif; ?>
        </section>

        <section id="messages" class="account-card mb-4">
            <h2>Messages</h2>
            <?php if ($messages): ?>
                <div class="message-thread">
                    <?php foreach ($messages as $message): ?>
                        <?php $isCustomer = (string) ($message['sender_type'] ?? '') === 'customer'; ?>
                        <div class="message-bubble <?= $isCustomer ? 'is-customer' : 'is-admin' ?>">
                            <div class="message-meta"><?= $isCustomer ? 'You' : 'Island Culture Travels' ?> | <?= e((string) ($message['created_at'] ?? '')) ?></div>
                            <div><?= nl2br(e((string) ($message['message_body'] ?? ''))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-secondary">No messages yet.</p>
            <?php endif; ?>
            <form method="post" action="<?= e(base_url('account/inquiries/' . (int) $plan['contact_inquiry_id'] . '/message')) ?>" class="mt-4">
                <?= csrf_field() ?>
                <label for="plan-message-body" class="form-label">Add a message</label>
                <textarea id="plan-message-body" name="message_body" rows="4" maxlength="<?= CUSTOMER_MESSAGE_MAX_LENGTH ?>" class="form-control" required></textarea>
                <button class="btn btn-success mt-2">Send Message</button>
            </form>
        </section>

        <section id="inquiry" class="account-card">
            <h2>Original Inquiry</h2>
            <dl class="account-detail-list">
                <dt>Status</dt><dd><?= e(inquiry_status_label((string) ($plan['inquiry_status'] ?? 'new'))) ?></dd>
                <dt>Email</dt><dd><?= e((string) ($plan['email'] ?? $customer['email'] ?? '')) ?></dd>
                <?php foreach (['phone' => 'Phone', 'whatsapp' => 'WhatsApp', 'country' => 'Country', 'interests' => 'Interests', 'accommodation_preference' => 'Accommodation'] as $field => $label): ?>
                    <?php if (!empty($plan[$field])): ?><dt><?= e($label) ?></dt><dd><?= e((string) $plan[$field]) ?></dd><?php endif; ?>
                <?php endforeach; ?>
            </dl>
            <?php if (!empty($plan['inquiry_message'])): ?>
                <h3 class="h6 mt-3">Message</h3>
                <p class="text-secondary mb-0"><?= nl2br(e((string) $plan['inquiry_message'])) ?></p>
            <?php endif; ?>
        </section>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
