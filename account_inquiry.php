<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/schema.php';

$customer = customer_require_auth();
$inquiryId = (int) get_input('id', 0);
$inquiry = find_customer_inquiry($inquiryId, $customer);

if (!$inquiry) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$messages = fetch_contact_inquiry_messages($inquiryId, true);
$linkedPlan = fetch_one(
    "SELECT id, plan_title, plan_status FROM customer_itinerary_plans WHERE contact_inquiry_id = :inquiry_id AND customer_user_id = :customer_user_id LIMIT 1",
    ['inquiry_id' => $inquiryId, 'customer_user_id' => (int) $customer['id']]
);

$seoTitle = 'Inquiry #' . $inquiryId . ' | ' . APP_NAME;
$seoDescription = 'View your Island Culture Travels inquiry details and messages.';
$seoCanonical = base_url('account/inquiries/' . $inquiryId);
$seoImage = DEFAULT_OG_IMAGE;
$seoType = 'website';
$seoRobots = 'noindex,nofollow';
$bodyClass = 'page-account-inquiry';

require __DIR__ . '/includes/header.php';
?>

<section class="account-hero py-5">
    <div class="container">
        <div class="account-hero-card">
            <span class="section-kicker">Inquiry #<?= $inquiryId ?></span>
            <h1><?= e(inquiry_status_label((string) ($inquiry['inquiry_status'] ?? 'new'))) ?></h1>
            <p>Submitted by <?= e((string) ($inquiry['name'] ?? $customer['name'] ?? 'Customer')) ?><?= !empty($inquiry['travel_dates']) ? ' for ' . e((string) $inquiry['travel_dates']) : '' ?>.</p>
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

        <div class="mb-3">
            <a href="<?= e(base_url('account')) ?>" class="btn btn-outline-dark btn-sm">Back to Account</a>
            <?php if ($linkedPlan): ?>
                <a href="<?= e(base_url('account/plans/' . (int) $linkedPlan['id'])) ?>" class="btn btn-success btn-sm">Open Itinerary Plan</a>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="account-card">
                    <h2>Inquiry Details</h2>
                    <dl class="account-detail-list">
                        <dt>Status</dt>
                        <dd><?= e(inquiry_status_label((string) ($inquiry['inquiry_status'] ?? 'new'))) ?></dd>
                        <dt>Email</dt>
                        <dd><?= e((string) ($inquiry['email'] ?? '')) ?></dd>
                        <?php foreach ([
                            'phone' => 'Phone',
                            'whatsapp' => 'WhatsApp',
                            'country' => 'Country',
                            'travel_dates' => 'Travel Dates',
                            'group_size' => 'Group Notes',
                            'preferred_itinerary_name' => 'Preferred Itinerary',
                            'interests' => 'Interests',
                            'accommodation_preference' => 'Accommodation',
                        ] as $field => $label): ?>
                            <?php if (!empty($inquiry[$field])): ?>
                                <dt><?= e($label) ?></dt>
                                <dd><?= e((string) $inquiry[$field]) ?></dd>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </dl>
                    <h3 class="h6 mt-3">Original Message</h3>
                    <p class="text-secondary mb-0"><?= nl2br(e((string) ($inquiry['message'] ?? ''))) ?></p>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="account-card">
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
                        <p class="text-secondary">No messages yet. Send a reply below if you need to add details.</p>
                    <?php endif; ?>

                    <form method="post" action="<?= e(base_url('account/inquiries/' . $inquiryId . '/message')) ?>" class="mt-4">
                        <?= csrf_field() ?>
                        <label for="inquiry-message-body" class="form-label">Add a message</label>
                        <textarea id="inquiry-message-body" name="message_body" rows="5" maxlength="<?= CUSTOMER_MESSAGE_MAX_LENGTH ?>" class="form-control" required></textarea>
                        <div class="d-flex justify-content-between align-items-center gap-2 mt-2">
                            <div class="form-text">Text only. Maximum <?= CUSTOMER_MESSAGE_MAX_LENGTH ?> characters.</div>
                            <button class="btn btn-success">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
