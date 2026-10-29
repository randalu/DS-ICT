<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer_auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/analytics_settings.php');
}

guard_post_size();
require_csrf();
ensure_analytics_events_table();
ensure_customer_accounts_schema();

$action = trim((string) post_input('action', ''));

if ($action === 'tag_manager') {
    $gtmId = strtoupper(trim((string) post_input('google_tag_manager_id', '')));
    $enabled = post_input('google_tag_manager_enabled') ? '1' : '0';

    if (mb_strlen($gtmId) > 40) {
        flash_set('error', 'Google Tag Manager ID is too long.');
        redirect('admin/analytics_settings.php');
    }

    if ($gtmId !== '' && preg_match('/^GTM-[A-Z0-9]+$/', $gtmId) !== 1) {
        flash_set('error', 'Google Tag Manager ID must look like GTM-XXXXXXX.');
        redirect('admin/analytics_settings.php');
    }

    analytics_setting_set('google_tag_manager_id', $gtmId);
    analytics_setting_set('google_tag_manager_enabled', $enabled);

    flash_set('success', $enabled === '1' && $gtmId !== '' ? 'Google Tag Manager enabled.' : 'Google Tag Manager settings saved.');
    redirect('admin/analytics_settings.php');
}

if ($action === 'customer_google_login') {
    $enabled = post_input('customer_google_login_enabled') ? '1' : '0';
    $clientId = trim((string) post_input('customer_google_client_id', ''));
    $newSecret = trim((string) post_input('customer_google_client_secret', ''));
    $existingSecret = trim((string) analytics_setting_get('customer_google_client_secret', ''));

    if (mb_strlen($clientId) > 255 || mb_strlen($newSecret) > 2000) {
        flash_set('error', 'Google OAuth client values are too long.');
        redirect('admin/analytics_settings.php');
    }

    if ($clientId !== '' && !str_ends_with($clientId, '.apps.googleusercontent.com')) {
        flash_set('error', 'Google Client ID should end with .apps.googleusercontent.com.');
        redirect('admin/analytics_settings.php');
    }

    if ($enabled === '1' && ($clientId === '' || ($newSecret === '' && $existingSecret === ''))) {
        flash_set('error', 'Add both Google Client ID and Client Secret before enabling customer login.');
        redirect('admin/analytics_settings.php');
    }

    analytics_setting_set('customer_google_login_enabled', $enabled);
    analytics_setting_set('customer_google_client_id', $clientId);
    if ($newSecret !== '') {
        analytics_setting_set('customer_google_client_secret', $newSecret);
    }

    flash_set('success', $enabled === '1' ? 'Customer Google login enabled.' : 'Customer Google login settings saved.');
    redirect('admin/analytics_settings.php');
}

if ($action === 'event_types') {
    $retentionDays = (int) post_input('retention_days', 365);
    $retentionDays = max(30, min(3650, $retentionDays));

    $rows = post_input('event_types', []);
    $eventUpdates = [];
    $errors = [];
    if (is_array($rows)) {
        foreach ($rows as $id => $row) {
            if (!is_array($row)) {
                continue;
            }

            $eventId = (int) $id;
            if ($eventId <= 0) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            $sortOrder = (int) ($row['sort_order'] ?? 0);
            $isActive = isset($row['is_active']) ? 1 : 0;

            if ($label === '') {
                continue;
            }

            if (mb_strlen($label) > 120 || mb_strlen($description) > 255) {
                $errors[] = 'Analytics event labels must be 120 characters or fewer, and descriptions 255 characters or fewer.';
                continue;
            }

            $eventUpdates[] = [
                'label' => $label,
                'description' => $description !== '' ? $description : null,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'id' => $eventId,
            ];
        }
    }

    $newEventKey = strtolower(trim((string) post_input('new_event_key', '')));
    $newEventLabel = trim((string) post_input('new_event_label', ''));
    $newEventDescription = trim((string) post_input('new_event_description', ''));
    $newEventSortOrder = (int) post_input('new_event_sort_order', 100);
    $newEvent = null;

    if ($newEventKey !== '' || $newEventLabel !== '') {
        $newEventKey = preg_replace('/[^a-z0-9_]+/', '_', $newEventKey) ?? '';
        $newEventKey = trim($newEventKey, '_');

        if ($newEventKey === '' || $newEventLabel === '') {
            $errors[] = 'New analytics event key and label are both required.';
        } elseif (mb_strlen($newEventKey) > 80 || mb_strlen($newEventLabel) > 120 || mb_strlen($newEventDescription) > 255) {
            $errors[] = 'New analytics event key, label, or description is too long.';
        } else {
            $newEvent = [
                'event_key' => $newEventKey,
                'label' => $newEventLabel,
                'description' => $newEventDescription !== '' ? $newEventDescription : null,
                'sort_order' => $newEventSortOrder,
            ];
        }
    }

    if ($errors) {
        flash_set('error', implode(' ', array_unique($errors)));
        redirect('admin/analytics_settings.php');
    }

    try {
        db()->beginTransaction();
        analytics_setting_set('events_retention_days', (string) $retentionDays);

        foreach ($eventUpdates as $eventUpdate) {
            execute_query(
                "UPDATE analytics_event_types
                 SET label = :label,
                     description = :description,
                     sort_order = :sort_order,
                     is_active = :is_active
                 WHERE id = :id",
                $eventUpdate
            );
        }

        if ($newEvent !== null) {
            execute_query(
                "INSERT INTO analytics_event_types (event_key, label, description, is_active, sort_order)
                 VALUES (:event_key, :label, :description, 1, :sort_order)
                 ON DUPLICATE KEY UPDATE
                    label = VALUES(label),
                    description = VALUES(description),
                    sort_order = VALUES(sort_order)",
                $newEvent
            );
        }

        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash_set('error', 'Failed to save analytics event settings.');
        redirect('admin/analytics_settings.php');
    }

    flash_set('success', 'Analytics event settings saved.');
    redirect('admin/analytics_settings.php');
}

if ($action === 'target_rules') {
    $rows = post_input('rules', []);
    $ruleUpdates = [];
    $errors = [];
    if (is_array($rows)) {
        foreach ($rows as $id => $row) {
            if (!is_array($row)) {
                continue;
            }

            $ruleId = (int) $id;
            if ($ruleId <= 0) {
                continue;
            }

            $ruleName = trim((string) ($row['rule_name'] ?? ''));
            $matchType = strtolower(trim((string) ($row['match_type'] ?? 'contains')));
            $matchValue = trim((string) ($row['match_value'] ?? ''));
            $groupLabel = trim((string) ($row['group_label'] ?? ''));
            $sortOrder = (int) ($row['sort_order'] ?? 0);
            $isActive = isset($row['is_active']) ? 1 : 0;

            if ($ruleName === '' || $matchValue === '' || $groupLabel === '') {
                continue;
            }

            if (!in_array($matchType, ['contains', 'starts_with', 'exact', 'domain', 'path_prefix'], true)) {
                $matchType = 'contains';
            }

            if (mb_strlen($ruleName) > 120 || mb_strlen($matchValue) > 255 || mb_strlen($groupLabel) > 120) {
                $errors[] = 'Analytics target rule names and groups must be 120 characters or fewer; match values must be 255 characters or fewer.';
                continue;
            }

            $ruleUpdates[] = [
                'rule_name' => $ruleName,
                'match_type' => $matchType,
                'match_value' => $matchValue,
                'group_label' => $groupLabel,
                'sort_order' => $sortOrder,
                'is_active' => $isActive,
                'id' => $ruleId,
            ];
        }
    }

    $deleteRuleIds = [];
    $deleteIds = post_input('delete_rule_ids', []);
    if (is_array($deleteIds)) {
        foreach ($deleteIds as $deleteId) {
            $ruleId = (int) $deleteId;
            if ($ruleId > 0) {
                $deleteRuleIds[] = $ruleId;
            }
        }
    }

    $newRuleName = trim((string) post_input('new_rule_name', ''));
    $newMatchType = strtolower(trim((string) post_input('new_match_type', 'contains')));
    $newMatchValue = trim((string) post_input('new_match_value', ''));
    $newGroupLabel = trim((string) post_input('new_group_label', ''));
    $newRuleSort = (int) post_input('new_rule_sort_order', 100);
    $newRule = null;

    if ($newRuleName !== '' || $newMatchValue !== '' || $newGroupLabel !== '') {
        if (!in_array($newMatchType, ['contains', 'starts_with', 'exact', 'domain', 'path_prefix'], true)) {
            $newMatchType = 'contains';
        }

        if ($newRuleName === '' || $newMatchValue === '' || $newGroupLabel === '') {
            $errors[] = 'New target rule name, match value, and group label are all required.';
        } elseif (mb_strlen($newRuleName) > 120 || mb_strlen($newMatchValue) > 255 || mb_strlen($newGroupLabel) > 120) {
            $errors[] = 'New target rule name, match value, or group label is too long.';
        } else {
            $newRule = [
                'rule_name' => $newRuleName,
                'match_type' => $newMatchType,
                'match_value' => $newMatchValue,
                'group_label' => $newGroupLabel,
                'sort_order' => $newRuleSort,
            ];
        }
    }

    if ($errors) {
        flash_set('error', implode(' ', array_unique($errors)));
        redirect('admin/analytics_settings.php');
    }

    try {
        db()->beginTransaction();

        foreach ($ruleUpdates as $ruleUpdate) {
            execute_query(
                "UPDATE analytics_target_rules
                 SET rule_name = :rule_name,
                     match_type = :match_type,
                     match_value = :match_value,
                     group_label = :group_label,
                     sort_order = :sort_order,
                     is_active = :is_active
                 WHERE id = :id",
                $ruleUpdate
            );
        }

        foreach (array_values(array_unique($deleteRuleIds)) as $ruleId) {
            execute_query('DELETE FROM analytics_target_rules WHERE id = :id', ['id' => $ruleId]);
        }

        if ($newRule !== null) {
            execute_query(
                "INSERT INTO analytics_target_rules
                (rule_name, match_type, match_value, group_label, is_active, sort_order)
                VALUES
                (:rule_name, :match_type, :match_value, :group_label, 1, :sort_order)",
                $newRule
            );
        }

        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash_set('error', 'Failed to save target grouping rules.');
        redirect('admin/analytics_settings.php');
    }

    flash_set('success', 'Target grouping rules saved.');
    redirect('admin/analytics_settings.php');
}

flash_set('error', 'Unknown analytics action.');
redirect('admin/analytics_settings.php');
