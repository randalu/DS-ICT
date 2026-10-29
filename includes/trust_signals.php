<?php
declare(strict_types=1);

$trustSummary = fetch_public_review_summary();
$trustCount = (int) ($trustSummary['count'] ?? 0);
$trustAverage = (float) ($trustSummary['average'] ?? 0.0);
$trustAudienceLabel = trim((string) ($trustAudienceLabel ?? 'Private and Family Tours'));
$trustYears = max(1, (int) ($trustYears ?? site_experience_years()));
$trustExtraLabel = trim((string) ($trustExtraLabel ?? 'Chauffeur-guided journeys'));
?>
<div class="hero-trust-signals">
    <?php if ($trustCount > 0 && $trustAverage > 0): ?>
        <span>★ <?= e(number_format($trustAverage, 1, '.', '')) ?> (<?= e((string) $trustCount) ?> reviews)</span>
    <?php else: ?>
        <span>Trusted by private travelers</span>
    <?php endif; ?>
    <span><?= e($trustAudienceLabel) ?></span>
    <span><?= e((string) $trustYears) ?>+ years local experience</span>
    <span><?= e($trustExtraLabel) ?></span>
</div>
