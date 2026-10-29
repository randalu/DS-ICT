<?php
declare(strict_types=1);

$adminTitle = 'Analytics Settings';
$adminPage = 'analytics_settings';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
require_once __DIR__ . '/../includes/customer_auth.php';

ensure_analytics_events_table();
ensure_customer_accounts_schema();

$eventTypes = fetch_analytics_event_types(false);
$targetRules = fetch_all('
    SELECT id, rule_name, match_type, match_value, group_label, is_active, sort_order
    FROM analytics_target_rules
    ORDER BY sort_order ASC, id ASC
');
$retentionDays = (int) (analytics_setting_get('events_retention_days', '365') ?? '365');
$retentionDays = max(30, min(3650, $retentionDays));
$gtmId = trim((string) analytics_setting_get('google_tag_manager_id', ''));
$gtmEnabled = analytics_setting_get('google_tag_manager_enabled', '0') === '1';
$customerGoogleEnabled = analytics_setting_get('customer_google_login_enabled', '0') === '1';
$customerGoogleClientId = trim((string) analytics_setting_get('customer_google_client_id', ''));
$customerGoogleClientSecretSet = trim((string) analytics_setting_get('customer_google_client_secret', '')) !== '';
$customerGoogleRedirectUri = customer_google_redirect_uri();

require __DIR__ . '/_top.php';
?>

<div class="row g-3">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Google Tag Manager</h2>
                <p class="text-secondary small mb-3">Paste your GTM container ID here to install Google Tag Manager sitewide. The script is added in the page head and the noscript fallback is added immediately after the opening body tag.</p>

                <form method="post" action="<?= e(base_url('admin/analytics_settings_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="tag_manager">

                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">GTM Container ID</label>
                            <input type="text" name="google_tag_manager_id" class="form-control text-uppercase" value="<?= e($gtmId) ?>" placeholder="GTM-XXXXXXX">
                            <div class="form-text">Use the ID from Google Tag Manager, not a GA4 Measurement ID.</div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="google_tag_manager_enabled" value="1" id="google_tag_manager_enabled" <?= $gtmEnabled ? 'checked' : '' ?>>
                                <label for="google_tag_manager_enabled" class="form-check-label">Enable sitewide GTM</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-success">Save Tag Manager</button>
                        </div>
                    </div>

                    <div class="alert alert-light border small mt-3 mb-0">
                        Recommended: after saving, test the live site with Google Tag Assistant or GTM Preview mode.
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Customer Google Login</h2>
                <p class="text-secondary small mb-3">Enable Google sign-in for customer accounts. Customers can track inquiries only after Google confirms their email is verified.</p>

                <form method="post" action="<?= e(base_url('admin/analytics_settings_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="customer_google_login">

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Google Client ID</label>
                            <input type="text" name="customer_google_client_id" class="form-control" value="<?= e($customerGoogleClientId) ?>" placeholder="xxxxxxxx.apps.googleusercontent.com">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Google Client Secret</label>
                            <input type="password" name="customer_google_client_secret" class="form-control" placeholder="<?= $customerGoogleClientSecretSet ? 'Leave blank to keep current secret' : 'Paste client secret' ?>">
                            <div class="form-text"><?= $customerGoogleClientSecretSet ? 'A secret is already saved.' : 'No secret is saved yet.' ?></div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label d-none d-md-block">&nbsp;</label>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="customer_google_login_enabled" value="1" id="customer_google_login_enabled" <?= $customerGoogleEnabled ? 'checked' : '' ?>>
                                <label for="customer_google_login_enabled" class="form-check-label">Enable</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Authorized Redirect URI</label>
                            <input type="text" class="form-control" value="<?= e($customerGoogleRedirectUri) ?>" readonly>
                            <div class="form-text">Add this in your Google Cloud OAuth client settings. Authorized JavaScript origin: <code><?= e(rtrim(APP_URL, '/')) ?></code>.</div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success mt-3">Save Customer Login</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">What Clicks Should Be Tracked</h2>
                <p class="text-secondary small mb-3">Turn click types on or off here. These are the labels used in Web Stats for WhatsApp clicks, phone taps, email clicks, quote buttons, and important external links.</p>

                <form method="post" action="<?= e(base_url('admin/analytics_settings_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="event_types">

                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Keep Click Data For</label>
                            <input type="number" min="30" max="3650" name="retention_days" class="form-control" value="<?= (int) $retentionDays ?>">
                            <div class="form-text">Number of days before old click events can be cleaned up.</div>
                        </div>
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Tracking Key</th>
                                    <th>Admin Label</th>
                                    <th>What It Means</th>
                                    <th>Sort</th>
                                    <th>Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eventTypes as $event): ?>
                                    <tr>
                                        <td><code><?= e((string) ($event['event_key'] ?? '')) ?></code></td>
                                        <td>
                                            <input type="text" name="event_types[<?= (int) ($event['id'] ?? 0) ?>][label]" class="form-control form-control-sm" value="<?= e((string) ($event['label'] ?? '')) ?>">
                                        </td>
                                        <td>
                                            <input type="text" name="event_types[<?= (int) ($event['id'] ?? 0) ?>][description]" class="form-control form-control-sm" value="<?= e((string) ($event['description'] ?? '')) ?>">
                                        </td>
                                        <td style="width:100px;">
                                            <input type="number" name="event_types[<?= (int) ($event['id'] ?? 0) ?>][sort_order]" class="form-control form-control-sm" value="<?= (int) ($event['sort_order'] ?? 0) ?>">
                                        </td>
                                        <td style="width:90px;">
                                            <input type="checkbox" class="form-check-input" name="event_types[<?= (int) ($event['id'] ?? 0) ?>][is_active]" value="1" <?= (int) ($event['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="h6 mb-2">Add a New Click Type</h3>
                    <p class="text-secondary small mb-2">Use this only when you add a new link/button type that needs its own report line. Example key: <code>brochure_download</code>.</p>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <input type="text" name="new_event_key" class="form-control" placeholder="tracking_key">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="new_event_label" class="form-control" placeholder="Report label">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="new_event_description" class="form-control" placeholder="What this click means">
                        </div>
                        <div class="col-md-2">
                            <input type="number" name="new_event_sort_order" class="form-control" placeholder="Sort" value="100">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success">Save Click Tracking Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h5 mb-2">Group Similar Clicked Links Together</h2>
                <p class="text-secondary small mb-3">These rules turn messy raw URLs into clean report names. Example: every WhatsApp URL can be grouped as “WhatsApp Chat.” Rules higher in the list run first.</p>

                <form method="post" action="<?= e(base_url('admin/analytics_settings_save.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="target_rules">

                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Rule Name</th>
                                    <th>How to Match</th>
                                    <th>Text or URL to Match</th>
                                    <th>Report As</th>
                                    <th>Sort</th>
                                    <th>Active</th>
                                    <th>Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($targetRules as $rule): ?>
                                    <?php $ruleId = (int) ($rule['id'] ?? 0); ?>
                                    <tr>
                                        <td><input type="text" name="rules[<?= $ruleId ?>][rule_name]" class="form-control form-control-sm" value="<?= e((string) ($rule['rule_name'] ?? '')) ?>"></td>
                                        <td>
                                            <?php $selectedType = strtolower((string) ($rule['match_type'] ?? 'contains')); ?>
                                            <select name="rules[<?= $ruleId ?>][match_type]" class="form-select form-select-sm">
                                                <?php foreach (['contains', 'starts_with', 'exact', 'domain', 'path_prefix'] as $type): ?>
                                                    <option value="<?= e($type) ?>" <?= $selectedType === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td><input type="text" name="rules[<?= $ruleId ?>][match_value]" class="form-control form-control-sm" value="<?= e((string) ($rule['match_value'] ?? '')) ?>"></td>
                                        <td><input type="text" name="rules[<?= $ruleId ?>][group_label]" class="form-control form-control-sm" value="<?= e((string) ($rule['group_label'] ?? '')) ?>"></td>
                                        <td style="width:90px;"><input type="number" name="rules[<?= $ruleId ?>][sort_order]" class="form-control form-control-sm" value="<?= (int) ($rule['sort_order'] ?? 0) ?>"></td>
                                        <td style="width:80px;"><input type="checkbox" class="form-check-input" name="rules[<?= $ruleId ?>][is_active]" value="1" <?= (int) ($rule['is_active'] ?? 0) === 1 ? 'checked' : '' ?>></td>
                                        <td style="width:80px;"><input type="checkbox" class="form-check-input" name="delete_rule_ids[]" value="<?= $ruleId ?>"></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3 class="h6 mb-2">Add a Link Grouping Rule</h3>
                    <div class="row g-2 mb-3">
                        <div class="col-md-2"><input type="text" name="new_rule_name" class="form-control" placeholder="Rule name"></div>
                        <div class="col-md-2">
                            <select name="new_match_type" class="form-select">
                                <?php foreach (['contains', 'starts_with', 'exact', 'domain', 'path_prefix'] as $type): ?>
                                    <option value="<?= e($type) ?>"><?= e($type) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="text" name="new_match_value" class="form-control" placeholder="e.g. wa.me or /contact"></div>
                        <div class="col-md-3"><input type="text" name="new_group_label" class="form-control" placeholder="e.g. WhatsApp Chat"></div>
                        <div class="col-md-2"><input type="number" name="new_rule_sort_order" class="form-control" value="100"></div>
                    </div>

                    <button type="submit" class="btn btn-success">Save Link Grouping Rules</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
