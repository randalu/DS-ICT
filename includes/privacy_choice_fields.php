<?php
declare(strict_types=1);
$privacyCategories = ict_privacy_available_categories($privacySettings);
echo '<p class="analytics-choice-detail">First-party website statistics operate for all public page visits, independently of these optional choices. We count pageviews, browser sessions, visits and enquiry-link clicks, with country when available, coarse device/browser categories and referrer domains. We do not store IP addresses or full browser strings.</p>';
$privacyLabels = ['analytics' => ['Allow optional Google analytics', 'Allows configured Google Tag Manager and its analytics tags. This choice does not switch off first-party website statistics.'],
    'external_reviews' => ['Load external review widgets', 'Loads Trustpilot and TripAdvisor widgets. Their services receive browser request information.'],
    'translation' => ['Enable translation', 'Loads GTranslate and allows its translation service to process page content.'],
    'chat' => ['Enable website chat', 'Loads Tawk.to. Messages you send in chat are handled by that service.']];
?>
<?php foreach ($privacyCategories as $privacyCategory => $privacyAvailable): ?>
    <?php if ($privacyAvailable): ?>
        <input type="hidden" name="<?= e($privacyCategory) ?>" value="0">
        <label class="analytics-choice-check"><input type="checkbox" name="<?= e($privacyCategory) ?>" value="1" <?= $privacyState[$privacyCategory] ? 'checked' : '' ?>> <?= e($privacyLabels[$privacyCategory][0]) ?></label>
        <p class="analytics-choice-detail"><?= e($privacyLabels[$privacyCategory][1]) ?></p>
    <?php endif; ?>
<?php endforeach; ?>
<?php if (!in_array(true, $privacyCategories, true)): ?><p>Optional services are currently unavailable.</p><?php endif; ?>
