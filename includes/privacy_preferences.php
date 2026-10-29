<?php
declare(strict_types=1);
$privacySettings = ict_privacy_settings();
$privacyState = ict_privacy_state($privacySettings);
$privacyPublicContext = ict_optional_public_context();
?>
<?php if (!$privacyState['chosen'] && $privacyPublicContext && in_array(true, ict_privacy_available_categories($privacySettings), true)): ?>
<aside class="analytics-notice" id="analyticsNotice" aria-labelledby="analyticsNoticeTitle">
    <strong id="analyticsNoticeTitle">Privacy choices</strong>
    <p>Choose which optional services may load. You can plan a trip and contact us without allowing them.</p>
    <form method="post" action="<?= e(ict_privacy_url('privacy_choices_save.php')) ?>" data-analytics-choice-form>
        <?= csrf_field() ?>
        <input type="hidden" name="purpose_revision" value="<?= (int) $privacySettings['purpose_revision'] ?>">
        <input type="hidden" name="return_path" value="<?= e(request_path()) ?>">
        <div class="analytics-choice-actions">
            <button class="analytics-choice-button" name="choice_action" value="accept">Allow optional</button>
            <button class="analytics-choice-button" name="choice_action" value="reject">Reject optional</button>
            <a class="analytics-choice-button" href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open>Manage choices</a>
        </div>
        <p class="analytics-choice-error" role="alert" data-analytics-error hidden></p>
    </form>
</aside>
<?php endif; ?>
<dialog class="analytics-choice-dialog" id="analyticsChoiceDialog" aria-labelledby="analyticsChoiceTitle">
    <div class="analytics-choice-heading"><h2 id="analyticsChoiceTitle">Privacy choices</h2><button type="button" class="analytics-choice-close" data-analytics-close aria-label="Close privacy choices">&times;</button></div>
    <p>Choose optional services separately. No options are selected until you choose.</p>
    <form method="post" action="<?= e(ict_privacy_url('privacy_choices_save.php')) ?>" data-analytics-choice-form>
        <?= csrf_field() ?>
        <input type="hidden" name="purpose_revision" value="<?= (int) $privacySettings['purpose_revision'] ?>">
        <input type="hidden" name="return_path" value="<?= e(request_path()) ?>">
        <?php require __DIR__ . '/privacy_choice_fields.php'; ?>
        <p class="analytics-choice-detail">We remember this choice for up to <?= (int) $privacySettings['preference_days'] ?> days. You can change it from the footer.</p>
        <div class="analytics-choice-actions"><button class="analytics-choice-button" name="choice_action" value="custom">Save choices</button><button class="analytics-choice-button" name="choice_action" value="reject">Reject optional</button></div>
        <p class="analytics-choice-error" role="alert" data-analytics-error hidden></p>
    </form>
</dialog>
