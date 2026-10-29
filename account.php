<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/schema.php';

$customer = customer_require_auth();
customer_link_inquiries_by_email((int) $customer['id'], (string) $customer['email']);
customer_ensure_plans_for_customer($customer);
$inquiries = fetch_customer_inquiries($customer);
$plans = fetch_customer_plans($customer);
$activePlan = fetch_customer_active_plan($customer);
$activePlanDays = $activePlan ? array_slice(fetch_customer_plan_days((int) $activePlan['id']), 0, 4) : [];
$activePlanRequests = $activePlan ? fetch_customer_plan_change_requests((int) $activePlan['id']) : [];

$seoTitle = 'My Account | ' . APP_NAME;
$seoDescription = 'Track your Island Culture Travels inquiries and messages.';
$seoCanonical = base_url('account');
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,nofollow';
$bodyClass = 'page-account';

require __DIR__ . '/includes/header.php';
?>

<section class="account-hero py-5">
    <div class="container">
        <div class="account-hero-card">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <span class="section-kicker">My Account</span>
                    <h1><?= $activePlan ? e((string) $activePlan['plan_title']) : 'Welcome' . (!empty($customer['name']) ? ', ' . e((string) $customer['name']) : '') ?></h1>
                    <p><?= $activePlan ? 'Customize your private day-by-day plan. Change requests are reviewed by admin before your confirmed plan is updated.' : 'Track inquiries linked to ' . e((string) $customer['email']) . ' and start a private itinerary plan.' ?></p>
                </div>
                <?php if (!empty($customer['avatar_url'])): ?>
                    <?php $localAvatar = ict_privacy_local_avatar_url((string) $customer['avatar_url']); ?>
                    <?php if ($localAvatar !== ''): ?><img class="account-avatar" src="<?= e($localAvatar) ?>" alt="Profile avatar">
                    <?php else: ?><span class="account-avatar account-avatar-local" role="img" aria-label="Profile avatar"><?= e(mb_strtoupper(mb_substr((string) ($customer['name'] ?? 'Guest'), 0, 1))) ?></span><?php endif; ?>
                <?php endif; ?>
            </div>
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

        <?php if ($activePlan): ?>
            <div class="account-plan-dashboard mb-4">
                <div class="account-card account-plan-main">
                    <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                        <div>
                            <span class="account-status"><?= e(customer_plan_status_label((string) ($activePlan['plan_status'] ?? 'planning'))) ?></span>
                            <h2 class="mb-1"><?= e((string) $activePlan['plan_title']) ?></h2>
                            <p class="text-secondary mb-0">
                                <?= !empty($activePlan['travel_dates']) ? 'Travel dates: ' . e((string) $activePlan['travel_dates']) : 'Travel dates not confirmed yet.' ?>
                            </p>
                        </div>
                        <a class="btn btn-success align-self-start" href="<?= e(base_url('account/plans/' . (int) $activePlan['id'])) ?>">Open Planning Workspace</a>
                    </div>

                    <div class="account-plan-stat-grid mb-4">
                        <div><strong><?= (int) ($activePlan['day_count'] ?? 0) ?></strong><span>Plan days</span></div>
                        <div><strong><?= (int) ($activePlan['pending_request_count'] ?? 0) ?></strong><span>Pending requests</span></div>
                        <div><strong><?= (int) ($activePlan['message_count'] ?? 0) ?></strong><span>Messages</span></div>
                        <div><strong><?= e((string) ($activePlan['traveler_summary'] ?: 'Private tour')) ?></strong><span>Travelers</span></div>
                    </div>

                    <div class="account-plan-progress-grid mb-4">
                        <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#day-by-day')) ?>">
                            <span>My Plan</span>
                            <strong><?= (int) ($activePlan['day_count'] ?? 0) ?> day-by-day items</strong>
                        </a>
                        <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#day-by-day')) ?>">
                            <span>Approved Plan</span>
                            <strong><?= in_array((string) ($activePlan['plan_status'] ?? ''), ['confirmed', 'completed'], true) ? 'Confirmed by admin' : 'Not confirmed yet' ?></strong>
                        </a>
                        <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#requests')) ?>">
                            <span>Waiting Approvals</span>
                            <strong><?= (int) ($activePlan['pending_request_count'] ?? 0) ?> pending</strong>
                        </a>
                        <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#messages')) ?>">
                            <span>Confirmations</span>
                            <strong><?= e(customer_plan_status_label((string) ($activePlan['plan_status'] ?? 'planning'))) ?></strong>
                        </a>
                    </div>

                    <?php if ($activePlanDays): ?>
                        <div class="account-day-preview-list">
                            <?php foreach ($activePlanDays as $day): ?>
                                <div class="account-day-preview">
                                    <span><?= e(day_range_label((int) $day['day_number'], isset($day['day_end']) ? (int) $day['day_end'] : null)) ?></span>
                                    <strong><?= e((string) $day['title']) ?></strong>
                                    <?php if (!empty($day['overnight_location'])): ?>
                                        <small>Overnight: <?= e((string) $day['overnight_location']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="account-card account-plan-side">
                    <h3>Workspace</h3>
                    <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#day-by-day')) ?>">My Plan</a>
                    <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#requests')) ?>">Change Requests</a>
                    <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#messages')) ?>">Messages</a>
                    <a href="<?= e(base_url('account/plans/' . (int) $activePlan['id'] . '#inquiry')) ?>">Inquiry Details</a>
                    <?php if ($activePlanRequests): ?>
                        <div class="small text-secondary mt-3">Latest request: <?= e(customer_change_request_status_label((string) ($activePlanRequests[0]['request_status'] ?? 'pending'))) ?></div>
                    <?php endif; ?>
                </aside>
            </div>
        <?php else: ?>
            <div class="account-card mb-4">
                <h2>No active itinerary plan yet</h2>
                <p class="text-secondary mb-3">Select an itinerary when submitting an inquiry, or ask us to assign one. Your private day-by-day workspace will appear here after it is linked.</p>
                <a href="<?= e(base_url('contact')) ?>" class="btn btn-success">Start with an Inquiry</a>
            </div>
        <?php endif; ?>

        <div class="account-grid mb-4">
            <a class="account-action-card" href="<?= e(base_url('contact')) ?>">
                <span>New Inquiry</span>
                <strong>Plan another tour</strong>
                <small>Use the existing contact form with your account email.</small>
            </a>
            <a class="account-action-card" href="<?= e($activePlan ? base_url('account/plans/' . (int) $activePlan['id'] . '#messages') : base_url('account')) ?>">
                <span>Messages</span>
                <strong>Contact our team</strong>
                <small><?= $activePlan ? 'Open the message thread for your active plan.' : 'Your inquiry messages will appear here after a plan is linked.' ?></small>
            </a>
            <a class="account-action-card" href="<?= e(base_url('itineraries')) ?>">
                <span>Route Ideas</span>
                <strong>Browse itineraries</strong>
                <small>Choose another published route as a starting point for your next inquiry.</small>
            </a>
        </div>

        <?php if (count($plans) > 1): ?>
            <div class="mb-4">
                <h2 class="h5 mb-3">Other Plans</h2>
                <div class="vstack gap-2">
                    <?php foreach ($plans as $plan): ?>
                        <?php if ($activePlan && (int) $plan['id'] === (int) $activePlan['id']) continue; ?>
                        <a class="account-inquiry-card" href="<?= e(base_url('account/plans/' . (int) $plan['id'])) ?>">
                            <div>
                                <div class="account-card-title"><?= e((string) $plan['plan_title']) ?></div>
                                <div class="small text-secondary"><?= e((string) ($plan['travel_dates'] ?: 'Dates not confirmed')) ?></div>
                            </div>
                            <div class="account-inquiry-meta">
                                <span class="account-status"><?= e(customer_plan_status_label((string) $plan['plan_status'])) ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
            <div>
                <h2 class="mb-1">Inquiries & Messages</h2>
                <p class="text-secondary mb-0">Inquiry records remain available below for reference.</p>
            </div>
            <a href="<?= e(base_url('account/logout')) ?>" class="btn btn-outline-dark">Sign Out</a>
        </div>

        <?php if ($inquiries): ?>
            <div class="vstack gap-3">
                <?php foreach ($inquiries as $inquiry): ?>
                    <?php
                    $inquiryId = (int) ($inquiry['id'] ?? 0);
                    $status = inquiry_status_label((string) ($inquiry['inquiry_status'] ?? 'new'));
                    $lastActivity = trim((string) ($inquiry['last_message_at'] ?? '')) ?: trim((string) ($inquiry['created_at'] ?? ''));
                    ?>
                    <a class="account-inquiry-card" href="<?= e(base_url('account/inquiries/' . $inquiryId)) ?>">
                        <div>
                            <div class="account-card-title">Inquiry #<?= $inquiryId ?></div>
                            <div class="text-secondary small">
                                <?= !empty($inquiry['travel_dates']) ? 'Travel dates: ' . e((string) $inquiry['travel_dates']) : 'Submitted: ' . e((string) ($inquiry['created_at'] ?? '')) ?>
                            </div>
                            <?php if (!empty($inquiry['preferred_itinerary_name'])): ?>
                                <div class="small">Preferred itinerary: <?= e((string) $inquiry['preferred_itinerary_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="account-inquiry-meta">
                            <span class="account-status"><?= e($status) ?></span>
                            <small><?= (int) ($inquiry['message_count'] ?? 0) ?> messages</small>
                            <small>Last activity: <?= e($lastActivity) ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="account-card">
                <h3>No linked inquiries yet</h3>
                <p class="text-secondary mb-3">If you submitted an inquiry with a different email address, it will not appear here. New inquiries submitted while signed in will link automatically.</p>
                <a href="<?= e(base_url('contact')) ?>" class="btn btn-success">Send an Inquiry</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
