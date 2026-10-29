<div class="card card-soft"><div class="card-body">
    <h1 class="h4">Privacy &amp; Tracking Controls</h1>
    <p class="text-secondary">Control optional analytics, review widgets, translation, configured website chat and public AI search. Review the analytics retention hold using the link below.</p>
    <?php if ($privacyConfigured): ?>
        <div class="alert alert-info" role="status">A valid saved privacy configuration is in use. Visitors must still give permission for allowed optional services. Form changes take effect only after a successful save.</div>
    <?php else: ?>
        <div class="alert alert-info" role="status">No valid saved privacy configuration is available. All optional services are currently disabled. The initial values below reflect this disabled state and remain unsaved until a successful save.</div>
    <?php endif; ?>
    <?php if ($privacyHasOldInput): ?>
        <div class="alert alert-warning" role="status">The form shows unsaved values from your previous submission. They are not the current configuration. Review them before saving; the configuration in use has not changed.</div>
    <?php endif; ?>
    <form method="post" action="<?= e(base_url('admin/privacy_settings_save.php')) ?>">
        <?= csrf_field() ?><input type="hidden" name="privacy_settings[schema_version]" value="3">
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="privacyDuration">Remember choices (days)</label><input class="form-control" id="privacyDuration" name="privacy_settings[preference_days]" type="number" min="1" max="365" required value="<?= e($privacySettings['preference_days']) ?>"><div class="form-text">180 days is the owner-approved local starting value. Shortening the duration invalidates longer saved choices.</div></div>
            <div class="col-md-6"><label class="form-label" for="privacyRevision">Purpose revision</label><input class="form-control" id="privacyRevision" name="privacy_settings[purpose_revision]" type="number" min="1" max="10000" required value="<?= e($privacySettings['purpose_revision']) ?>"><div class="form-text">Increase when purposes change. Older choices then allow none of these optional services; revisions cannot decrease.</div></div>
        </div>
        <input type="hidden" name="privacy_settings[first_party_enabled]" value="1">
        <p class="alert alert-info mt-3">First-party website statistics are active independently of these optional-service switches and visitor choices. Google and other external services remain separately controlled.</p>
        <input type="hidden" name="privacy_settings[gtm_allowed]" value="0">
        <label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="privacy_settings[gtm_allowed]" value="1" <?= in_array($privacySettings['gtm_allowed'], [true, 1, '1'], true) ? 'checked' : '' ?>><span class="form-check-label">Allow configured GTM after the visitor permits analytics</span></label>
        <p class="small text-secondary">This additional switch starts off. A configured container or environment override cannot bypass it or the visitor's choice. Advertising consent remains denied.</p>
        <p class="small text-secondary">The GTM noscript iframe stays disabled. First-party reports use public URL paths without queries, random session visitor/visit keys, country when available, referrer domains and coarse device/browser/OS categories. IP addresses and full browser strings are not stored. Visits end after 30 minutes of inactivity. Missing historical data cannot be backfilled.</p>
        <label class="form-check"><input class="form-check-input" type="checkbox" name="gtm_review_ack" value="1"><span class="form-check-label">If allowing GTM, I have reviewed its published tags, consent behavior and account routing.</span></label>
        <p class="small text-secondary">This operator acknowledgement is not independent evidence of the account review.</p>
        <?php foreach (['external_reviews_allowed' => 'Allow external review widgets after the visitor permits reviews', 'translation_allowed' => 'Allow translation after the visitor permits it', 'chat_allowed' => 'Allow website chat after the visitor permits it (existing Tawk configuration also required)', 'review_invitations_allowed' => 'Allow Trustpilot invitations after reviews consent and a separate operator review'] as $privacySwitch => $privacySwitchLabel): ?>
            <input type="hidden" name="privacy_settings[<?= e($privacySwitch) ?>]" value="0">
            <label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="privacy_settings[<?= e($privacySwitch) ?>]" value="1" <?= in_array($privacySettings[$privacySwitch], [true, 1, '1'], true) ? 'checked' : '' ?>><span class="form-check-label"><?= e($privacySwitchLabel) ?></span></label>
        <?php endforeach; ?>
        <label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="review_invitation_ack" value="1"><span class="form-check-label">If allowing invitations, I have reviewed the invitation purpose, account behavior and published privacy wording.</span></label>
        <p class="small text-secondary">Invitations start disabled. Visitor reviews consent never enables them by itself. Unconfigured chat is hidden from visitor choices. This acknowledgement is not independent provider evidence.</p>
        <input type="hidden" name="privacy_settings[public_ai_search_enabled]" value="0">
        <label class="form-check mt-3"><input class="form-check-input" type="checkbox" name="privacy_settings[public_ai_search_enabled]" value="1" <?= in_array($privacySettings['public_ai_search_enabled'], [true, 1, '1'], true) ? 'checked' : '' ?>><span class="form-check-label">Enable public AI search after provider/account and privacy review</span></label>
        <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="public_ai_review_ack" value="1"><span class="form-check-label">If enabling public AI, I have reviewed account ownership, model/provider routes, logging/training, retention, processing location, limits and the visitor disclosure.</span></label>
        <p class="small text-secondary">Public AI starts off. A key or an analytics/widget choice cannot enable it. Enabled searches require a disclosed visitor POST action, up to 1,000 characters and five requests per 15 minutes per session and IP. Quotas are not a billing cap. This acknowledgement is not independent provider evidence. Admin generation and CLI indexing are separate.</p>
        <div class="alert alert-light border mt-3">Analytics disposal is paused. Click collection and public AI never run cleanup. <a href="<?= e(base_url('admin/analytics_retention.php')) ?>">Review the hold and dry run</a>. Actual disposal requires a separate scope.</div>
        <button class="btn btn-success" type="submit">Save Privacy Controls</button>
    </form>
</div></div>
