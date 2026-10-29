<?php
declare(strict_types=1);

require_once __DIR__ . '/page-components.php';

use function NewSite\{e, icon};
use function NewSite\Pages\{body_sections, breadcrumbs, day_sequence, facts, faq, gallery, header, inclusions, inquiry, page_has_bleed_hero, related, route_ribbon, source_notice, text};

/** @var array $page */
$family = text($page['family'] ?? 'guide');
$unavailable = ($page['mode'] ?? '') === 'unavailable';
$record = !$unavailable && is_array($page['record'] ?? null) ? $page['record'] : [];
$days = is_array($record['days'] ?? null) ? array_values($record['days']) : [];
$body = is_array($record['body'] ?? null) ? $record['body'] : [];
$hasSidebar = in_array($family, ['itinerary', 'tour', 'day-tour'], true);
$earlyProgramme = in_array($family, ['tour', 'day-tour'], true);
?>
<div class="core-page core-page--detail core-page--<?= e($family) ?>">
    <?php if (!page_has_bleed_hero($page)) { breadcrumbs($page); } header($page, true); ?>
    <div class="container page-detail-content">
        <?php source_notice($page); ?>
        <?php if ($unavailable || $record === []): ?>
            <section class="page-empty" aria-labelledby="detail-unavailable-title"><?= icon('shield') ?><h2 id="detail-unavailable-title">This content is unavailable.</h2><p>The approved source could not supply this record. No sample programme, facts or commercial details have been substituted.</p></section>
        <?php else: ?>
            <?php if (!empty($record['facts']) && is_array($record['facts'])): facts($record['facts'], $family === 'experience' ? 'Before you go' : ($family === 'destination' ? 'A little local perspective' : 'At a glance')); endif; ?>
            <?php if (!empty($record['route']) && is_array($record['route'])): route_ribbon($record['route'], ($page['mode'] ?? '') === 'fixture' ? 'Illustrative route - design sample only' : 'The journey, at a glance'); endif; ?>
            <?php if ($body !== [] || $days !== [] || !empty($record['inclusions']) || !empty($record['faq'])): ?>
                <nav class="page-section-nav" aria-label="On this page">
                    <?php if ($body !== []): ?><a href="#overview">Overview</a><?php endif; ?>
                    <?php if ($days !== []): ?><a href="#programme"><?= $family === 'itinerary' ? 'Day by day' : 'Programme' ?></a><?php endif; ?>
                    <?php if (!empty($record['inclusions']) || !empty($record['exclusions'])): ?><a href="#included">Inclusions &amp; exclusions</a><?php endif; ?>
                    <?php if (!empty($record['faq'])): ?><a href="#questions">Useful to know</a><?php endif; ?>
                </nav>
            <?php endif; ?>
            <div class="page-detail-grid<?= $hasSidebar ? ' page-detail-grid--sidebar' : '' ?>">
                <div class="page-detail-main">
                    <?php if ($earlyProgramme): day_sequence($days, $family); endif; ?>
                    <?php if ($body !== []): ?><div id="overview"><?php body_sections($body); ?></div><?php endif; ?>
                    <?php if (!$earlyProgramme): day_sequence($days, $family); endif; ?>
                    <?php inclusions($record); ?>
                    <?php if (!empty($record['faq']) && is_array($record['faq'])): faq($record['faq']); endif; ?>
                    <?php if (!empty($record['gallery']) && is_array($record['gallery'])): gallery($record['gallery']); endif; ?>
                </div>
                <?php if ($hasSidebar): ?><div class="page-detail-sidebar"><?php inquiry($page); ?></div><?php endif; ?>
            </div>
            <?php if (!$hasSidebar): inquiry($page, true); endif; ?>
        <?php endif; ?>
    </div>
    <?php related($page); ?>
</div>
