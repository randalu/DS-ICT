<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
header('Cache-Control: private, no-store');
header('Vary: Cookie', false);
if (get_input('format') === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(ict_privacy_browser_config(), JSON_THROW_ON_ERROR);
    exit;
}
$privacySettings = ict_privacy_settings();
$privacyState = ict_privacy_state($privacySettings);
$seoTitle = 'Privacy Choices | ' . APP_NAME;
$seoDescription = 'Choose optional analytics, review widgets, translation and available chat services.';
$seoCanonical = base_url('privacy_choices.php');
$seoRobots = 'noindex,nofollow';
$recordPageView = false;
require __DIR__ . '/includes/header.php';
?>
<section class="analytics-choice-page">
    <h1>Privacy choices</h1>
    <p>Choose optional services separately. Planning a trip and contacting us do not require them.</p>
    <?php if ($error = flash_get('error')): ?><p class="analytics-choice-error" role="alert"><?= e($error) ?></p><?php endif; ?>
    <?php if ($success = flash_get('success')): ?><p role="status"><?= e($success) ?></p><?php endif; ?>
    <form method="post" action="<?= e(ict_privacy_url('privacy_choices_save.php')) ?>" data-analytics-choice-form>
        <?= csrf_field() ?>
        <input type="hidden" name="purpose_revision" value="<?= (int) $privacySettings['purpose_revision'] ?>">
        <input type="hidden" name="return_path" value="privacy_choices.php">
        <?php require __DIR__ . '/includes/privacy_choice_fields.php'; ?>
        <p class="analytics-choice-detail">We remember this choice for up to <?= (int) $privacySettings['preference_days'] ?> days. You can change it from the footer.</p>
        <div class="analytics-choice-actions"><button class="analytics-choice-button" name="choice_action" value="custom">Save choices</button><button class="analytics-choice-button" name="choice_action" value="reject">Reject optional</button></div>
        <p class="analytics-choice-error" role="alert" data-analytics-error hidden></p>
    </form>
    <p><a href="<?= e(base_url('contact')) ?>">Contact us to plan your trip</a></p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
