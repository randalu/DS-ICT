<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

ensure_customer_accounts_schema();

$itineraryId = (int) get_input('itinerary_id', 0);
$itinerary = $itineraryId > 0 ? fetch_one(
    "SELECT id, name, slug, excerpt, hero_image, og_image, days
     FROM itineraries
     WHERE id = :id
       AND is_published = 1
     LIMIT 1",
    ['id' => $itineraryId]
) : null;

if (!$itinerary) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$tripTypes = customer_trip_support_types();
$customer = customer_user();

$startPlan = static function (array $customer, int $itineraryId, string $tripSupportType): void {
    $customerUserId = (int) ($customer['id'] ?? 0);
    $existingPlan = $customerUserId > 0 ? find_customer_plan_for_itinerary($customerUserId, $itineraryId) : null;
    try {
        $plan = customer_start_itinerary_customization($customer, $itineraryId, $tripSupportType);
    } catch (Throwable $exception) {
        unset($_SESSION[CUSTOMER_CUSTOMIZER_PENDING_KEY]);
        $amendmentMessage = 'Please contact us to request an amendment to this booking. Your agreed trip support has not changed.';
        flash_set('error', $exception->getMessage() === $amendmentMessage
            ? $amendmentMessage
            : 'We could not update this itinerary plan. Please try again or contact us.');
        redirect($existingPlan ? 'account/plans/' . (int) $existingPlan['id'] : 'account/customize-itinerary?itinerary_id=' . $itineraryId);
    }
    if (!$plan) {
        flash_set('error', 'We could not start this custom itinerary plan. Please contact us and we will set it up manually.');
        redirect('account/customize-itinerary?itinerary_id=' . $itineraryId);
    }

    unset($_SESSION[CUSTOMER_CUSTOMIZER_PENDING_KEY]);
    if (!$existingPlan) {
        google_tag_event_queue('generate_lead', [
            'lead_type' => 'itinerary_customizer',
            'source_page' => 'account_customizer',
            'logged_in' => true,
            'preferred_itinerary_selected' => true,
        ]);
    }
    google_tag_event_queue($existingPlan ? 'itinerary_plan_opened' : 'itinerary_plan_started', [
        'source_page' => 'account_customizer',
        'trip_support_type' => $tripSupportType,
        'itinerary_id' => $itineraryId,
    ]);
    $supportChanged = $existingPlan && (string) ($existingPlan['trip_support_type'] ?? '') !== (string) ($plan['trip_support_type'] ?? '');
    flash_set('success', $supportChanged
        ? 'Your trip support has changed. Our team will review the plan before requesting fresh policy acceptance.'
        : 'Your private itinerary workspace is ready.');
    redirect('account/plans/' . (int) $plan['id']);
};

if ($customer && is_get()) {
    $pending = $_SESSION[CUSTOMER_CUSTOMIZER_PENDING_KEY] ?? null;
    if (is_array($pending) && (int) ($pending['itinerary_id'] ?? 0) === $itineraryId) {
        $startPlan($customer, $itineraryId, (string) ($pending['trip_support_type'] ?? 'fully_arranged_trip'));
    }
}

if (is_post()) {
    guard_post_size();
    require_csrf();

    $tripSupportType = trim((string) post_input('trip_support_type', ''));
    if (!array_key_exists($tripSupportType, $tripTypes)) {
        flash_set('error', 'Please choose the type of trip support you want.');
        redirect('account/customize-itinerary?itinerary_id=' . $itineraryId);
    }

    google_tag_event_queue('trip_type_selected', [
        'source_page' => 'account_customizer',
        'trip_support_type' => $tripSupportType,
        'itinerary_id' => $itineraryId,
        'login_required' => !$customer,
    ]);

    if (!$customer) {
        $_SESSION[CUSTOMER_CUSTOMIZER_PENDING_KEY] = [
            'itinerary_id' => $itineraryId,
            'trip_support_type' => $tripSupportType,
        ];
        redirect('account/login?return=' . rawurlencode('/account/customize-itinerary?itinerary_id=' . $itineraryId));
    }

    $startPlan($customer, $itineraryId, $tripSupportType);
}

$heroImage = !empty($itinerary['hero_image'])
    ? stored_media_url((string) $itinerary['hero_image'])
    : (!empty($itinerary['og_image']) ? stored_media_url((string) $itinerary['og_image']) : DEFAULT_OG_IMAGE);
$itineraryUrl = base_url('itineraries/' . (string) $itinerary['slug']);

$seoTitle = 'Customize ' . (string) $itinerary['name'] . ' | ' . APP_NAME;
$seoDescription = 'Choose how you want Island Culture Travels to help customize this private Sri Lanka itinerary.';
$seoCanonical = base_url('account/customize-itinerary?itinerary_id=' . $itineraryId);
$seoImage = $heroImage;
$seoType = 'website';
$seoRobots = 'noindex,nofollow';
$bodyClass = 'page-account-customizer';

require __DIR__ . '/includes/header.php';
?>

<section class="account-customizer-hero">
    <div class="container">
        <div class="account-customizer-layout">
            <div class="account-customizer-visual">
                <?= responsive_image([
                    'src' => $heroImage,
                    'alt' => (string) $itinerary['name'],
                    'widths' => [400, 800, 1200],
                    'sizes' => '(min-width: 992px) 46vw, 100vw',
                    'width' => 1200,
                    'height' => 900,
                    'class' => 'account-customizer-image',
                    'loading' => 'eager',
                ]) ?>
                <div class="account-customizer-visual-copy">
                    <span class="section-kicker">Private planning workspace</span>
                    <h1>Customize this Sri Lanka itinerary day by day.</h1>
                    <p>Log in with Google, request changes, chat with our team, and track what is waiting for approval or already confirmed.</p>
                    <div class="account-customizer-mini-grid">
                        <span>Day-by-day edits</span>
                        <span>Agent messages</span>
                        <span>Approval tracking</span>
                    </div>
                </div>
            </div>

            <div class="account-customizer-panel">
                <?php if ($error = flash_get('error')): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>

                <a href="<?= e($itineraryUrl) ?>" class="account-customizer-back">&larr; Back to itinerary</a>
                <span class="section-kicker">Choose Your Support</span>
                <h2>What kind of trip are you interested in?</h2>
                <p class="text-secondary">Starting from: <strong><?= e((string) $itinerary['name']) ?></strong><?php if (!empty($itinerary['days'])): ?>, <?= (int) $itinerary['days'] ?> days<?php endif; ?>.</p>

                <div class="account-trip-type-grid">
                    <?php foreach ($tripTypes as $key => $type): ?>
                        <form method="post" action="<?= e(base_url('account/customize-itinerary?itinerary_id=' . $itineraryId)) ?>" class="account-trip-type-card">
                            <?= csrf_field() ?>
                            <input type="hidden" name="trip_support_type" value="<?= e($key) ?>">
                            <div class="account-trip-type-icons" aria-hidden="true">
                                <span>Map</span>
                                <?php if ($key !== 'self_booked_adventure'): ?><span>Stay</span><?php endif; ?>
                                <?php if ($key === 'private_guide_trip'): ?><span>Guide</span><?php endif; ?>
                            </div>
                            <h3><?= e((string) $type['label']) ?></h3>
                            <p><?= e((string) $type['description']) ?></p>
                            <div class="account-trip-type-list">
                                <strong>We take care of:</strong>
                                <?php foreach ($type['included'] as $item): ?>
                                    <span class="is-included">Yes: <?= e((string) $item) ?></span>
                                <?php endforeach; ?>
                                <?php foreach ($type['excluded'] as $item): ?>
                                    <span class="is-excluded">No: <?= e((string) $item) ?></span>
                                <?php endforeach; ?>
                            </div>
                            <button class="btn btn-success w-100 mt-3">
                                <?= $customer ? 'Start Customizing' : 'Continue with Google' ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
