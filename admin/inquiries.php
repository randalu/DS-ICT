<?php
declare(strict_types=1);

$adminTitle = 'Inquiries';
$adminPage = 'inquiries';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
require_once __DIR__ . '/../includes/customer_auth.php';
require_once __DIR__ . '/../includes/booking_confirmation.php';

ensure_contact_inquiries_extended_schema();
ensure_customer_accounts_schema();
$publishedPolicyDocuments = ict_policy_published_documents();
$policyAcceptanceAvailable = count($publishedPolicyDocuments) === count(ict_policy_required_keys());

if (!db_table_exists('contact_inquiries')) {
    require __DIR__ . '/_top.php';
    ?>
    <div class="card card-soft">
        <div class="card-body">
            <div class="text-secondary">No inquiries table is available in this database yet.</div>
        </div>
    </div>
    <?php
    require __DIR__ . '/_bottom.php';
    exit;
}

$page = max(1, (int) get_input('page', 1));
$perPage = 20;
$offset = paginate_offset($page, $perPage);
$search = trim((string) get_input('q', ''));
$statusFilter = trim((string) get_input('status', ''));

$hasPreferredItineraryId = db_column_exists('contact_inquiries', 'preferred_itinerary_id');
$hasPreferredItineraryName = db_column_exists('contact_inquiries', 'preferred_itinerary_name');
$hasWhatsapp = db_column_exists('contact_inquiries', 'whatsapp');
$hasAdults = db_column_exists('contact_inquiries', 'adults_count');
$hasChildren = db_column_exists('contact_inquiries', 'children_count');
$hasBudgetMin = db_column_exists('contact_inquiries', 'budget_min');
$hasBudgetMax = db_column_exists('contact_inquiries', 'budget_max');
$hasBudgetCurrency = db_column_exists('contact_inquiries', 'budget_currency');
$hasAccommodation = db_column_exists('contact_inquiries', 'accommodation_preference');
$hasStatus = db_column_exists('contact_inquiries', 'inquiry_status');
$hasAdminNotes = db_column_exists('contact_inquiries', 'admin_notes');
$hasFollowUpAt = db_column_exists('contact_inquiries', 'follow_up_at');
$hasSpam = db_column_exists('contact_inquiries', 'is_spam');
$hasSpamReason = db_column_exists('contact_inquiries', 'spam_reason');
$hasCustomerUserId = db_column_exists('contact_inquiries', 'customer_user_id');
$hasTripSupportType = db_column_exists('contact_inquiries', 'trip_support_type');
$canJoinItineraries = $hasPreferredItineraryId && db_table_exists('itineraries');

$joinSql = $canJoinItineraries ? 'LEFT JOIN itineraries i ON i.id = ci.preferred_itinerary_id' : '';
$preferredItineraryIdSelect = $hasPreferredItineraryId ? 'ci.preferred_itinerary_id AS preferred_itinerary_id' : 'NULL AS preferred_itinerary_id';
$preferredItineraryNameSelect = $hasPreferredItineraryName ? 'ci.preferred_itinerary_name AS preferred_itinerary_name' : 'NULL AS preferred_itinerary_name';
$preferredItineraryTitleSelect = $canJoinItineraries ? 'i.name AS preferred_itinerary_title' : 'NULL AS preferred_itinerary_title';

$whereParts = [];
$params = [];

if ($search !== '') {
    $searchParts = [
        'ci.name LIKE :search',
        'ci.email LIKE :search',
        'ci.phone LIKE :search',
        'ci.country LIKE :search',
        'ci.message LIKE :search',
    ];

    if ($hasWhatsapp) {
        $searchParts[] = 'ci.whatsapp LIKE :search';
    }
    if ($hasPreferredItineraryName) {
        $searchParts[] = 'ci.preferred_itinerary_name LIKE :search';
    }
    if ($canJoinItineraries) {
        $searchParts[] = 'i.name LIKE :search';
    }

    $whereParts[] = '(' . implode(' OR ', $searchParts) . ')';
    $params['search'] = '%' . $search . '%';
}

$statusOptions = ['new', 'follow_up', 'quoted', 'won', 'lost', 'spam'];
if ($statusFilter !== '' && in_array($statusFilter, $statusOptions, true) && $hasStatus) {
    $whereParts[] = 'ci.inquiry_status = :status_filter';
    $params['status_filter'] = $statusFilter;
}

$where = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

$total = (int) (fetch_one("SELECT COUNT(*) AS total FROM contact_inquiries ci {$joinSql} {$where}", $params)['total'] ?? 0);

$items = fetch_all("
    SELECT
        ci.id,
        " . ($hasCustomerUserId ? 'ci.customer_user_id' : 'NULL AS customer_user_id') . ",
        ci.name,
        ci.email,
        ci.phone,
        " . ($hasWhatsapp ? 'ci.whatsapp' : 'NULL AS whatsapp') . ",
        ci.country,
        ci.travel_dates,
        ci.group_size,
        " . ($hasAdults ? 'ci.adults_count' : 'NULL AS adults_count') . ",
        " . ($hasChildren ? 'ci.children_count' : 'NULL AS children_count') . ",
        " . ($hasBudgetMin ? 'ci.budget_min' : 'NULL AS budget_min') . ",
        " . ($hasBudgetMax ? 'ci.budget_max' : 'NULL AS budget_max') . ",
        " . ($hasBudgetCurrency ? 'ci.budget_currency' : "'USD' AS budget_currency") . ",
        " . ($hasAccommodation ? 'ci.accommodation_preference' : 'NULL AS accommodation_preference') . ",
        ci.interests,
        ci.message,
        ci.source_page,
        ci.created_at,
        {$preferredItineraryIdSelect},
        {$preferredItineraryNameSelect},
        {$preferredItineraryTitleSelect},
        " . ($hasStatus ? 'ci.inquiry_status' : "'new' AS inquiry_status") . ",
        " . ($hasAdminNotes ? 'ci.admin_notes' : 'NULL AS admin_notes') . ",
        " . ($hasFollowUpAt ? 'ci.follow_up_at' : 'NULL AS follow_up_at') . ",
        " . ($hasSpam ? 'ci.is_spam' : '0 AS is_spam') . ",
        " . ($hasSpamReason ? 'ci.spam_reason' : 'NULL AS spam_reason') . ",
        " . ($hasTripSupportType ? 'ci.trip_support_type' : 'NULL AS trip_support_type') . "
    FROM contact_inquiries ci
    {$joinSql}
    {$where}
    ORDER BY ci.id DESC
    LIMIT {$perPage} OFFSET {$offset}
", $params);

$pages = max(1, (int) ceil($total / max(1, $perPage)));

$itemIds = array_map(static fn(array $item): int => (int) ($item['id'] ?? 0), $items);
$activitiesByInquiry = [];
$messagesByInquiry = [];
$plansByInquiry = [];
$changeRequestsByPlan = [];
$policyAcceptancesByPlan = [];
$adminIds = [];

if ($itemIds && db_table_exists('contact_inquiry_activity')) {
    $inClause = implode(',', array_map('intval', $itemIds));
    $activityRows = fetch_all("SELECT inquiry_id, action_type, old_value, new_value, note, created_at, admin_user_id FROM contact_inquiry_activity WHERE inquiry_id IN ({$inClause}) ORDER BY id DESC");
    foreach ($activityRows as $activity) {
        $inquiryId = (int) ($activity['inquiry_id'] ?? 0);
        if ($inquiryId <= 0) {
            continue;
        }
        $adminId = (int) ($activity['admin_user_id'] ?? 0);
        if ($adminId > 0) {
            $adminIds[$adminId] = true;
        }
        if (!isset($activitiesByInquiry[$inquiryId])) {
            $activitiesByInquiry[$inquiryId] = [];
        }
        if (count($activitiesByInquiry[$inquiryId]) < 5) {
            $activitiesByInquiry[$inquiryId][] = $activity;
        }
    }
}

if ($itemIds && db_table_exists('contact_inquiry_messages')) {
    $inClause = implode(',', array_map('intval', $itemIds));
    $messageRows = fetch_all("SELECT inquiry_id, sender_type, message_body, created_at, admin_user_id FROM contact_inquiry_messages WHERE inquiry_id IN ({$inClause}) AND is_visible_to_customer = 1 ORDER BY id DESC");
    foreach ($messageRows as $message) {
        $inquiryId = (int) ($message['inquiry_id'] ?? 0);
        if ($inquiryId <= 0) {
            continue;
        }
        $adminId = (int) ($message['admin_user_id'] ?? 0);
        if ($adminId > 0) {
            $adminIds[$adminId] = true;
        }
        if (!isset($messagesByInquiry[$inquiryId])) {
            $messagesByInquiry[$inquiryId] = [];
        }
        if (count($messagesByInquiry[$inquiryId]) < 5) {
            $messagesByInquiry[$inquiryId][] = $message;
        }
    }
}

$adminsById = [];
if (!empty($adminIds)) {
    $adminIdList = implode(',', array_keys($adminIds));
    $adminRows = fetch_all("SELECT id, name, username FROM admins WHERE id IN ({$adminIdList})");
    foreach ($adminRows as $adminRow) {
        $adminsById[(int) $adminRow['id']] = $adminRow;
    }
}

if ($itemIds && db_table_exists('customer_itinerary_plans')) {
    $plansByInquiry = fetch_customer_plans_for_inquiries($itemIds);
    $planIds = array_map(static fn(array $plan): int => (int) ($plan['id'] ?? 0), array_values($plansByInquiry));
    $changeRequestsByPlan = fetch_customer_change_requests_for_plans($planIds);
    if ($planIds && db_table_exists('customer_policy_acceptances')) {
        $planIdList = implode(',', array_map('intval', $planIds));
        foreach (fetch_all(
            "SELECT id, plan_id, booking_terms_version, cancellation_policy_version, payment_policy_version,
                    privacy_policy_version, marketing_consent, policy_snapshot_hash, accepted_at
             FROM customer_policy_acceptances
             WHERE plan_id IN ({$planIdList})
             ORDER BY id DESC"
        ) as $acceptance) {
            $policyAcceptancesByPlan[(int) ($acceptance['plan_id'] ?? 0)][] = $acceptance;
        }
    }
}

$statusLabels = [
    'new' => 'New Inquiry',
    'follow_up' => 'Needs Follow-up',
    'quoted' => 'Quote Sent',
    'won' => 'Confirmed / Won',
    'lost' => 'Closed / Lost',
    'spam' => 'Spam / Hidden',
];
$statusHelp = [
    'new' => 'Fresh inquiry. Needs review or first reply.',
    'follow_up' => 'You need to follow up or wait for customer details.',
    'quoted' => 'A quote or proposed plan has been sent.',
    'won' => 'Customer confirmed the trip or booking.',
    'lost' => 'Closed without booking. Keep notes for future reference.',
    'spam' => 'Hidden from normal workflow.',
];
$statusBadgeMap = [
    'new' => 'bg-primary',
    'follow_up' => 'bg-warning text-dark',
    'quoted' => 'bg-info text-dark',
    'won' => 'bg-success',
    'lost' => 'bg-secondary',
    'spam' => 'bg-danger',
];

$statusCounts = array_fill_keys($statusOptions, 0);
$allInquiryCount = (int) (fetch_one("SELECT COUNT(*) AS total FROM contact_inquiries")['total'] ?? 0);
if ($hasStatus) {
    foreach (fetch_all("SELECT inquiry_status, COUNT(*) AS total FROM contact_inquiries GROUP BY inquiry_status") as $row) {
        $key = strtolower(trim((string) ($row['inquiry_status'] ?? 'new')));
        if (isset($statusCounts[$key])) {
            $statusCounts[$key] = (int) ($row['total'] ?? 0);
        }
    }
} else {
    $statusCounts['new'] = $allInquiryCount;
}
$openPipelineCount = (int) ($statusCounts['new'] + $statusCounts['follow_up'] + $statusCounts['quoted']);

require __DIR__ . '/_top.php';
?>

<div class="card card-soft">
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-4 col-xl-2">
                <a href="<?= e(base_url('admin/inquiries.php')) ?>" class="card card-soft text-decoration-none h-100 <?= $statusFilter === '' ? 'border-primary' : '' ?>">
                    <div class="card-body py-3">
                        <div class="small text-secondary">All Inquiries</div>
                        <div class="h4 mb-0 text-dark"><?= number_format($allInquiryCount) ?></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-xl-2">
                <div class="card card-soft h-100">
                    <div class="card-body py-3">
                        <div class="small text-secondary">Open Pipeline</div>
                        <div class="h4 mb-0 text-dark"><?= number_format($openPipelineCount) ?></div>
                    </div>
                </div>
            </div>
            <?php foreach ($statusOptions as $option): ?>
                <div class="col-md-4 col-xl-2">
                    <a href="<?= e(base_url('admin/inquiries.php?status=' . urlencode($option))) ?>" class="card card-soft text-decoration-none h-100 <?= $statusFilter === $option ? 'border-primary' : '' ?>">
                        <div class="card-body py-3">
                            <div class="small text-secondary"><?= e($statusLabels[$option] ?? ucfirst($option)) ?></div>
                            <div class="d-flex align-items-center justify-content-between gap-2">
                                <div class="h4 mb-0 text-dark"><?= number_format((int) ($statusCounts[$option] ?? 0)) ?></div>
                                <span class="badge <?= e($statusBadgeMap[$option] ?? 'bg-secondary') ?>">&nbsp;</span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <form class="row g-2 align-items-center" method="get">
                <div class="col-auto">
                    <input type="text" name="q" class="form-control" placeholder="Search inquiries..." value="<?= e($search) ?>">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select">
                        <option value="">All statuses</option>
                        <?php foreach ($statusOptions as $option): ?>
                            <option value="<?= e($option) ?>" <?= $statusFilter === $option ? 'selected' : '' ?>><?= e($statusLabels[$option] ?? ucfirst($option)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-dark">Filter</button>
                </div>
                <?php if ($search !== '' || $statusFilter !== ''): ?>
                    <div class="col-auto">
                        <a class="btn btn-outline-secondary" href="<?= e(base_url('admin/inquiries.php')) ?>">Clear</a>
                    </div>
                <?php endif; ?>
            </form>
            <div class="small text-secondary">Total: <?= number_format($total) ?></div>
        </div>

        <?php if ($items): ?>
            <div class="table-responsive">
                <table class="table admin-inquiry-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Country</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td data-label="Name"><?= e((string) ($item['name'] ?? '')) ?></td>
                            <td data-label="Email"><?= e((string) ($item['email'] ?? '')) ?></td>
                            <td data-label="Country"><?= e((string) ($item['country'] ?? '')) ?></td>
                            <td data-label="Date"><?= e((string) ($item['created_at'] ?? '')) ?></td>
                            <td data-label="Status"><?= e((string) ($item['inquiry_status'] ?? 'new')) ?></td>
                            <td data-label="Actions"><a href="#inquiry-<?= (int) ($item['id'] ?? 0) ?>" class="btn btn-sm btn-outline-dark">View</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="vstack gap-3">
                <?php foreach ($items as $item): ?>
                    <?php
                    $inquiryId = (int) ($item['id'] ?? 0);
                    $status = strtolower(trim((string) ($item['inquiry_status'] ?? 'new')));
                    if (!isset($statusLabels[$status])) {
                        $status = 'new';
                    }
                    $badgeClass = $statusBadgeMap[$status] ?? 'bg-secondary';

                    $preferredItineraryTitle = trim((string) ($item['preferred_itinerary_title'] ?? ''));
                    $preferredItineraryName = trim((string) ($item['preferred_itinerary_name'] ?? ''));
                    $preferredItineraryId = (int) ($item['preferred_itinerary_id'] ?? 0);
                    $preferredItineraryLabel = $preferredItineraryTitle !== ''
                        ? $preferredItineraryTitle
                        : ($preferredItineraryName !== '' ? $preferredItineraryName : ($preferredItineraryId > 0 ? 'Itinerary #' . $preferredItineraryId : ''));

                    $budgetLabel = '';
                    $budgetMin = isset($item['budget_min']) ? (float) $item['budget_min'] : 0.0;
                    $budgetMax = isset($item['budget_max']) ? (float) $item['budget_max'] : 0.0;
                    $budgetCurrency = strtoupper(trim((string) ($item['budget_currency'] ?? 'USD')));
                    if ($budgetMin > 0 && $budgetMax > 0 && $budgetMax >= $budgetMin) {
                        $budgetLabel = $budgetCurrency . ' ' . format_price_amount($budgetMin) . ' - ' . $budgetCurrency . ' ' . format_price_amount($budgetMax);
                    } elseif ($budgetMin > 0) {
                        $budgetLabel = 'From ' . $budgetCurrency . ' ' . format_price_amount($budgetMin);
                    } elseif ($budgetMax > 0) {
                        $budgetLabel = 'Up to ' . $budgetCurrency . ' ' . format_price_amount($budgetMax);
                    }
                    $customerPlan = $plansByInquiry[$inquiryId] ?? null;
                    $messageItems = $messagesByInquiry[$inquiryId] ?? [];
                    $activityItems = $activitiesByInquiry[$inquiryId] ?? [];
                    $tripSupportType = trim((string) ($item['trip_support_type'] ?? ($customerPlan['trip_support_type'] ?? '')));
                    $tripSupportLabel = $tripSupportType !== '' ? customer_trip_support_type_label($tripSupportType) : '';
                    ?>
                    <div class="border rounded-4 p-4 bg-white" id="inquiry-<?= $inquiryId ?>">
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <div class="fw-semibold">#<?= $inquiryId ?> - <?= e((string) ($item['name'] ?? '')) ?></div>
                                <div class="small text-secondary">
                                    <?= e((string) ($item['email'] ?? '')) ?>
                                    <?= (int) ($item['customer_user_id'] ?? 0) > 0 ? ' | Customer account linked' : '' ?>
                                    <?= !empty($item['phone']) ? ' | ' . e((string) $item['phone']) : '' ?>
                                    <?= !empty($item['whatsapp']) ? ' | WhatsApp: ' . e((string) $item['whatsapp']) : '' ?>
                                    <?= !empty($item['country']) ? ' | ' . e((string) $item['country']) : '' ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= e($badgeClass) ?>"><?= e($statusLabels[$status] ?? ucfirst($status)) ?></span>
                                <div class="small text-secondary mt-1"><?= e($statusHelp[$status] ?? '') ?></div>
                                <div class="small text-secondary mt-1"><?= e((string) ($item['created_at'] ?? '')) ?></div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if ((int) ($item['customer_user_id'] ?? 0) > 0): ?>
                                <span class="badge text-bg-success">Account linked</span>
                            <?php else: ?>
                                <span class="badge text-bg-light text-dark border">No account yet</span>
                            <?php endif; ?>
                            <?php if ($messageItems): ?>
                                <span class="badge text-bg-info"><?= count($messageItems) ?> recent visible messages</span>
                            <?php endif; ?>
                            <?php if ($customerPlan): ?>
                                <span class="badge text-bg-primary">Plan workspace active</span>
                            <?php endif; ?>
                            <?php if ($tripSupportLabel !== ''): ?>
                                <span class="badge text-bg-warning text-dark"><?= e($tripSupportLabel) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['follow_up_at'])): ?>
                                <span class="badge text-bg-light text-dark border">Follow-up: <?= e((string) $item['follow_up_at']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach (['follow_up', 'quoted', 'won', 'lost'] as $quickStatus): ?>
                                <?php if ($status === $quickStatus) continue; ?>
                                <form method="post" action="<?= e(base_url('admin/inquiry_update.php')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $inquiryId ?>">
                                    <input type="hidden" name="inquiry_status" value="<?= e($quickStatus) ?>">
                                    <input type="hidden" name="return_to" value="<?= e('admin/inquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '') . '#inquiry-' . $inquiryId) ?>">
                                    <button class="btn btn-sm btn-outline-dark"><?= e($statusLabels[$quickStatus] ?? ucfirst($quickStatus)) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>

                        <div class="small mb-2">
                            <?php if (!empty($item['travel_dates'])): ?>
                                <div><strong>Travel Dates:</strong> <?= e((string) $item['travel_dates']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['group_size'])): ?>
                                <div><strong>Group Notes:</strong> <?= e((string) $item['group_size']) ?></div>
                            <?php endif; ?>
                            <?php if ((int) ($item['adults_count'] ?? 0) > 0 || (int) ($item['children_count'] ?? 0) > 0): ?>
                                <div><strong>Adults/Children:</strong> <?= (int) ($item['adults_count'] ?? 0) ?> / <?= (int) ($item['children_count'] ?? 0) ?></div>
                            <?php endif; ?>
                            <?php if ($preferredItineraryLabel !== ''): ?>
                                <div><strong>Preferred Itinerary:</strong> <?= e($preferredItineraryLabel) ?></div>
                            <?php endif; ?>
                            <?php if ($tripSupportLabel !== ''): ?>
                                <div><strong>Trip Support Type:</strong> <?= e($tripSupportLabel) ?></div>
                            <?php endif; ?>
                            <?php if ($budgetLabel !== ''): ?>
                                <div><strong>Budget:</strong> <?= e($budgetLabel) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['accommodation_preference'])): ?>
                                <div><strong>Accommodation:</strong> <?= e((string) $item['accommodation_preference']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['interests'])): ?>
                                <div><strong>Interests:</strong> <?= e((string) $item['interests']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($item['source_page'])): ?>
                                <div><strong>Source Page:</strong> <?= e((string) $item['source_page']) ?></div>
                            <?php endif; ?>
                            <?php if ((int) ($item['is_spam'] ?? 0) === 1 && !empty($item['spam_reason'])): ?>
                                <div class="text-danger"><strong>Spam Flag:</strong> <?= e((string) $item['spam_reason']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="text-secondary mb-3"><?= nl2br(e((string) ($item['message'] ?? ''))) ?></div>

                        <form method="post" action="<?= e(base_url('admin/inquiry_update.php')) ?>" class="border-top pt-3">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $inquiryId ?>">
                            <input type="hidden" name="return_to" value="<?= e('admin/inquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '') . '#inquiry-' . $inquiryId) ?>">
                            <div class="row g-2 align-items-start">
                                <div class="col-md-2">
                                    <label class="form-label small">Status</label>
                                    <select name="inquiry_status" class="form-select form-select-sm">
                                        <?php foreach ($statusOptions as $option): ?>
                                            <option value="<?= e($option) ?>" <?= $status === $option ? 'selected' : '' ?>><?= e($statusLabels[$option] ?? ucfirst($option)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small">Follow Up</label>
                                    <?php
                                    $followUpValue = '';
                                    if (!empty($item['follow_up_at'])) {
                                        $ts = strtotime((string) $item['follow_up_at']);
                                        $followUpValue = $ts ? date('Y-m-d\TH:i', $ts) : '';
                                    }
                                    ?>
                                    <input type="datetime-local" name="follow_up_at" class="form-control form-control-sm" value="<?= e($followUpValue) ?>">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small">Private Admin Notes <span class="text-secondary">(not visible to customer)</span></label>
                                    <textarea name="admin_notes" rows="2" class="form-control form-control-sm"><?= e((string) ($item['admin_notes'] ?? '')) ?></textarea>
                                </div>
                                <div class="col-md-2 d-grid">
                                    <label class="form-label small d-none d-md-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-sm btn-success">Save</button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-3 pt-3 border-top">
                            <div class="small fw-semibold mb-1">Customer-visible Messages</div>
                            <div class="small text-secondary mb-2">These replies show in the customer account after Google login. Private admin notes above are never shown to the customer.</div>
                            <?php if ($messageItems): ?>
                                <div class="small text-secondary mb-3">
                                    <?php foreach ($messageItems as $message): ?>
                                        <?php
                                        $senderName = (string) ($message['sender_type'] ?? '') === 'admin' ? 'Admin' : 'Customer';
                                        if ($senderName === 'Admin' && ($adminId = (int) ($message['admin_user_id'] ?? 0)) > 0) {
                                            $adminUser = $adminsById[$adminId] ?? null;
                                            if ($adminUser) {
                                                $senderName = trim((string) ($adminUser['name'] ?? $adminUser['username'] ?? 'Admin'));
                                            }
                                        }
                                        ?>
                                        <div class="mb-2">
                                            <strong><?= e($senderName) ?></strong>
                                            <span><?= e((string) ($message['created_at'] ?? '')) ?></span>
                                            <div><?= nl2br(e((string) ($message['message_body'] ?? ''))) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="small text-secondary mb-3">No customer-visible messages yet.</div>
                            <?php endif; ?>

                            <form method="post" action="<?= e(base_url('admin/inquiry_message_save.php')) ?>">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $inquiryId ?>">
                                <div class="row g-2 align-items-start">
                                    <div class="col-md-10">
                                        <label class="form-label small">Reply to customer account</label>
                                        <textarea name="message_body" rows="2" maxlength="<?= CUSTOMER_MESSAGE_MAX_LENGTH ?>" class="form-control form-control-sm" placeholder="Visible to the customer. Use private admin notes above for internal notes."></textarea>
                                    </div>
                                    <div class="col-md-2 d-grid">
                                        <label class="form-label small d-none d-md-block">&nbsp;</label>
                                        <input type="hidden" name="return_to" value="<?= e('admin/inquiries.php' . (!empty($_SERVER['QUERY_STRING']) ? '?' . (string) $_SERVER['QUERY_STRING'] : '') . '#inquiry-' . $inquiryId) ?>">
                                        <button class="btn btn-sm btn-outline-success">Send Customer Reply</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <?php if ($customerPlan): ?>
                            <?php
                            $customerPlanId = (int) ($customerPlan['id'] ?? 0);
                            $planRequests = $changeRequestsByPlan[$customerPlanId] ?? [];
                            $bookingStatus = (string) ($customerPlan['booking_status'] ?? 'planning');
                            $policyState = ict_policy_plan_acceptance_state($customerPlan, $publishedPolicyDocuments);
                            ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                                    <div>
                                        <div class="small fw-semibold">Customer Plan</div>
                                        <a class="small" href="<?= e(base_url('admin/quotations.php?inquiry_id=' . $inquiryId . '&q=' . $inquiryId)) ?>">Quotations / create from this inquiry</a>
                                        <div class="small text-secondary">
                                            <?= e((string) ($customerPlan['plan_title'] ?? 'Private itinerary plan')) ?>
                                            | Plan review: <?= e(customer_plan_status_label((string) ($customerPlan['plan_status'] ?? 'planning'))) ?>
                                            | Booking: <?= e(ict_booking_status_label($bookingStatus)) ?>
                                            | <?= (int) ($customerPlan['day_count'] ?? 0) ?> days
                                            | <?= (int) ($customerPlan['pending_request_count'] ?? 0) ?> pending
                                            <?php if (!empty($customerPlan['trip_support_type'])): ?>
                                                | <?= e(customer_trip_support_type_label((string) $customerPlan['trip_support_type'])) ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($customerPlan['customer_user_id'])): ?><a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('account/plans/' . $customerPlanId)) ?>" target="_blank" rel="noopener">View Customer Page</a><?php endif; ?>
                                </div>

                                <div class="border rounded-3 p-3 mb-3 bg-white">
                                    <div class="small fw-semibold mb-1">Booking confirmation gate</div>
                                    <?php if (empty($customerPlan['customer_user_id'])): ?>
                                        <?php $guestBinding = db_table_exists('quotation_bookings') ? fetch_one('SELECT revision_id FROM quotation_bookings WHERE plan_id=:id', ['id' => $customerPlanId]) : null; ?>
                                        <p class="small">Guest booking · acceptance is recorded by an administrator from customer evidence.</p>
                                        <?php if ($guestBinding): ?><a class="btn btn-outline-success" href="<?= e(base_url('admin/quotation_booking.php?id=' . $guestBinding['revision_id'])) ?>">Manage guest acceptance and confirmation</a><?php endif; ?>
                                    <?php elseif ($bookingStatus === 'planning' && $policyAcceptanceAvailable && (int) ($customerPlan['pending_request_count'] ?? 0) === 0): ?>
                                        <p class="small text-secondary">Request acceptance only after reviewing the final services, route, price, payment instructions, and supplier availability.</p>
                                        <form method="post" action="<?= e(base_url('admin/customer_plan_booking_status_save.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="plan_id" value="<?= $customerPlanId ?>">
                                            <label class="form-check small mb-2">
                                                <input class="form-check-input" type="checkbox" name="final_plan_reviewed" value="1" required>
                                                <span class="form-check-label">I reviewed the final itinerary and booking details for customer acceptance.</span>
                                            </label>
                                            <button class="btn btn-sm btn-outline-success" name="action" value="request_acceptance">Request Customer Acceptance</button>
                                        </form>
                                    <?php elseif ($bookingStatus === 'planning' && !$policyAcceptanceAvailable): ?>
                                        <p class="small text-warning mb-0">Acceptance is disabled until all four required policy pages are reviewed and published.</p>
                                    <?php elseif ($bookingStatus === 'awaiting_acceptance'): ?>
                                        <p class="small text-secondary mb-0">Waiting for the customer to accept the displayed policy versions. This is not yet a confirmed booking.</p>
                                    <?php elseif ($bookingStatus === 'accepted' && !$policyState['ready_to_confirm'] && $policyAcceptanceAvailable && (int) ($customerPlan['pending_request_count'] ?? 0) === 0): ?>
                                        <p class="small text-warning">Published policy content changed after the previous acceptance. Request acceptance of the current versions again before confirmation.</p>
                                        <form method="post" action="<?= e(base_url('admin/customer_plan_booking_status_save.php')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="plan_id" value="<?= $customerPlanId ?>">
                                            <label class="form-check small mb-2">
                                                <input class="form-check-input" type="checkbox" name="final_plan_reviewed" value="1" required>
                                                <span class="form-check-label">I reviewed the final itinerary and booking details for customer acceptance.</span>
                                            </label>
                                            <button class="btn btn-sm btn-outline-warning" name="action" value="request_acceptance">Request Updated Acceptance</button>
                                        </form>
                                    <?php elseif ($bookingStatus === 'accepted' && $policyState['ready_to_confirm'] && (int) ($customerPlan['pending_request_count'] ?? 0) === 0): ?>
                                        <p class="small text-secondary">Acceptance recorded at <?= e($policyState['accepted_at']) ?>. Confirmation also requires documented payment and supplier checks.</p>
                                        <?php $confirmationPlanId = $customerPlanId; $confirmationAcceptance = $policyAcceptancesByPlan[$customerPlanId][0] ?? []; require __DIR__ . '/_booking_confirmation_form.php'; ?>
                                    <?php elseif ($bookingStatus === 'accepted'): ?>
                                        <p class="small text-warning mb-0">Acceptance is recorded, but the current policy versions or itinerary state must be reviewed before confirmation.</p>
                                    <?php elseif ($bookingStatus === 'confirmed'): ?>
                                        <p class="small text-success mb-0">Written confirmation issued<?= !empty($customerPlan['booking_confirmed_at']) ? ' at ' . e((string) $customerPlan['booking_confirmed_at']) : '' ?>.</p>
                                    <?php else: ?>
                                        <p class="small text-secondary mb-0">No customer acceptance has been requested.</p>
                                    <?php endif; ?>
                                </div>

                                <?php $acceptanceHistory = $policyAcceptancesByPlan[$customerPlanId] ?? []; ?>
                                <?php if ($acceptanceHistory): ?>
                                    <details class="small mb-3">
                                        <summary class="fw-semibold">Policy acceptance history (<?= count($acceptanceHistory) ?>)</summary>
                                        <div class="vstack gap-2 mt-2">
                                            <?php foreach ($acceptanceHistory as $acceptance): ?>
                                                <div class="border rounded-2 p-2">
                                                    <div>Accepted <?= e((string) ($acceptance['accepted_at'] ?? '')) ?></div>
                                                    <div class="text-secondary">Booking <?= e((string) ($acceptance['booking_terms_version'] ?? '')) ?>; cancellation <?= e((string) ($acceptance['cancellation_policy_version'] ?? '')) ?></div>
                                                    <div class="text-secondary">Payment <?= e((string) ($acceptance['payment_policy_version'] ?? '')) ?>; privacy <?= e((string) ($acceptance['privacy_policy_version'] ?? '')) ?></div>
                                                    <div class="text-secondary">Optional marketing consent: <?= (int) ($acceptance['marketing_consent'] ?? 0) === 1 ? 'yes' : 'no' ?>; snapshot SHA-256: <code><?= e((string) ($acceptance['policy_snapshot_hash'] ?? '')) ?></code></div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>

                                <?php if ($planRequests): ?>
                                    <div class="vstack gap-3">
                                        <?php foreach ($planRequests as $request): ?>
                                            <?php
                                            $requestId = (int) ($request['id'] ?? 0);
                                            $isPending = (string) ($request['request_status'] ?? '') === 'pending';
                                            $finalTitle = trim((string) ($request['requested_title'] ?? '')) ?: (string) ($request['current_title'] ?? '');
                                            $finalSummary = trim((string) ($request['requested_summary'] ?? '')) ?: (string) ($request['current_summary'] ?? '');
                                            $finalOvernight = trim((string) ($request['requested_overnight_location'] ?? '')) ?: (string) ($request['current_overnight_location'] ?? '');
                                            $finalTravelTime = trim((string) ($request['requested_travel_time'] ?? '')) ?: (string) ($request['current_travel_time'] ?? '');
                                            ?>
                                            <div class="border rounded-3 p-3 bg-light">
                                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                                    <strong class="small">
                                                        <?= e(day_range_label((int) $request['day_number'], isset($request['day_end']) ? (int) $request['day_end'] : null)) ?>
                                                        | <?= e(customer_change_request_status_label((string) ($request['request_status'] ?? 'pending'))) ?>
                                                    </strong>
                                                    <span class="small text-secondary"><?= e((string) ($request['created_at'] ?? '')) ?></span>
                                                </div>
                                                <?php if (!empty($request['request_reason'])): ?>
                                                    <div class="small mb-2"><strong>Customer reason:</strong> <?= nl2br(e((string) $request['request_reason'])) ?></div>
                                                <?php endif; ?>

                                                <?php if ($isPending): ?>
                                                    <form method="post" action="<?= e(base_url('admin/customer_plan_request_save.php')) ?>">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="request_id" value="<?= $requestId ?>">
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <label class="form-label small">Final Title</label>
                                                                <input type="text" name="final_title" class="form-control form-control-sm" value="<?= e($finalTitle) ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small">Overnight</label>
                                                                <input type="text" name="final_overnight_location" class="form-control form-control-sm" value="<?= e($finalOvernight) ?>">
                                                            </div>
                                                            <div class="col-md-3">
                                                                <label class="form-label small">Travel Time</label>
                                                                <input type="text" name="final_travel_time" class="form-control form-control-sm" value="<?= e($finalTravelTime) ?>">
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label small">Final Summary</label>
                                                                <textarea name="final_summary" rows="3" class="form-control form-control-sm"><?= e($finalSummary) ?></textarea>
                                                            </div>
                                                            <div class="col-12">
                                                                <label class="form-label small">Customer-visible admin response</label>
                                                                <textarea name="admin_response" rows="2" class="form-control form-control-sm" placeholder="Optional note shown to the customer."></textarea>
                                                            </div>
                                                            <div class="col-12 d-flex flex-wrap gap-2">
                                                                <button class="btn btn-sm btn-success" name="action" value="apply">Apply to Customer Plan</button>
                                                                <button class="btn btn-sm btn-outline-danger" name="action" value="reject">Reject Request</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <?php if (!empty($request['admin_response'])): ?>
                                                        <div class="small text-secondary">Admin response: <?= nl2br(e((string) $request['admin_response'])) ?></div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-secondary">No day-by-day change requests yet.</div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($preferredItineraryId > 0 && (int) ($item['customer_user_id'] ?? 0) > 0): ?>
                            <div class="mt-3 pt-3 border-top">
                                <div class="small fw-semibold mb-1">Customer Plan</div>
                                <div class="small text-secondary">A private plan will be created automatically the next time the customer opens their account.</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($activityItems): ?>
                            <div class="mt-3 pt-2 border-top">
                                <div class="small fw-semibold mb-2">Recent Activity</div>
                                <div class="small text-secondary">
                                    <?php foreach ($activityItems as $activity): ?>
                                        <?php
                                        $adminInfo = '';
                                        if (($adminId = (int) ($activity['admin_user_id'] ?? 0)) > 0) {
                                            $adminUser = $adminsById[$adminId] ?? null;
                                            if ($adminUser) {
                                                $adminInfo = ' by ' . trim((string) ($adminUser['name'] ?? $adminUser['username'] ?? 'Admin'));
                                            }
                                        }
                                        ?>
                                        <div class="mb-1">
                                            <?= e((string) ($activity['created_at'] ?? '')) ?> | <?= e((string) ($activity['action_type'] ?? 'update')) ?><?= e($adminInfo) ?>
                                            <?php if (!empty($activity['old_value']) || !empty($activity['new_value'])): ?>
                                                (<?= e((string) ($activity['old_value'] ?? '-')) ?> -> <?= e((string) ($activity['new_value'] ?? '-')) ?>)
                                            <?php endif; ?>
                                            <?php if (($activity['action_type'] ?? '') === ICT_CONFIRMATION_EVIDENCE_ACTION): ?>
                                                <?php $confirmationDetails = json_decode((string) ($activity['note'] ?? ''), true); require __DIR__ . '/_booking_confirmation_evidence.php'; ?>
                                            <?php elseif (!empty($activity['note'])): ?>
                                                - <?= e((string) $activity['note']) ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination mb-0">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item<?= $i === $page ? ' active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($statusFilter) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-secondary">No inquiries found.</div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/_bottom.php'; ?>
