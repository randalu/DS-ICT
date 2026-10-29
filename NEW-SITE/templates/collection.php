<?php
declare(strict_types=1);

require_once __DIR__ . '/page-components.php';

use function NewSite\{e, icon, url};
use function NewSite\Pages\{body_sections, breadcrumbs, filters, header, page_has_bleed_hero, pagination, related, result_card, source_notice, text};

/** @var array $page */
$family = text($page['family'] ?? 'guide');
$unavailable = ($page['mode'] ?? '') === 'unavailable';
$items = !$unavailable && is_array($page['items'] ?? null) ? array_values($page['items']) : [];
$record = is_array($page['record'] ?? null) ? $page['record'] : [];
$total = $unavailable ? null : max(0, (int) ($page['pagination']['total'] ?? count($items)));
$isJournal = $family === 'blog';
?>
<div class="core-page core-page--collection core-page--<?= e($family) ?>">
    <?php if (!page_has_bleed_hero($page)) { breadcrumbs($page); } header($page); ?>
    <div class="container page-collection-content">
        <?php source_notice($page); ?>
        <?php
        $discovery = [];
        if (($page['mode'] ?? '') === 'fixture' && $family === 'tour') {
            foreach (['honeymoon', 'family', 'adventure', 'wellness', 'luxury', 'wildlife'] as $theme) {
                $discovery['/' . $theme . '-tours'] = ucfirst($theme) . ' journeys';
            }
            $discovery['/itineraries'] = 'Suggested itineraries';
            $discovery['/day-tours'] = 'Day tours';
        } elseif (($page['mode'] ?? '') === 'fixture' && $isJournal) {
            $discovery = ['/seasonal' => 'When to visit', '/from' => 'Planning from abroad', '/faq' => 'Planning questions'];
        } elseif ($family === 'itinerary') {
            foreach (($page['filter_options']['days'] ?? []) as $days => $_label) {
                if (ctype_digit((string) $days) && (int) $days >= 1 && (int) $days <= 60) {
                    $discovery['/' . $days . '-day-itinerary'] = $days . '-day journeys';
                }
            }
        }
        ?>
        <?php if ($discovery !== []): ?><nav class="page-discovery" aria-label="Related planning collections"><p class="eyebrow"><?= ($page['mode'] ?? '') === 'fixture' ? 'Explore the linked layout samples' : 'Other ways to explore' ?></p><div><?php foreach ($discovery as $target => $label): ?><a href="<?= e(url($target)) ?>"><?= e($label) ?> <?= icon('arrow-up-right') ?></a><?php endforeach; ?></div></nav><?php endif; ?>
        <?php if (!$unavailable && !empty($record['body']) && is_array($record['body'])): ?><div class="page-collection-editorial"><?php body_sections($record['body'], 'collection'); ?></div><?php endif; ?>
        <?php filters($page); ?>
        <section aria-labelledby="results-title" id="results">
            <div class="page-results-heading"><h2 id="results-title"><?= $isJournal ? 'From the journal' : match ($family) { 'destination' => 'Find your change of scene', 'experience' => 'What draws you here?', 'itinerary' => 'Find a starting point', 'day-tour' => 'Make a day of it', 'tour' => 'Explore the possibilities', 'partner' => 'Explore the directory', default => 'A little direction for your journey' } ?></h2><p><?= $total === null ? 'Results unavailable' : e((string) $total) . ' ' . ($total === 1 ? 'result' : 'results') ?><?= !$unavailable && ($page['mode'] ?? '') === 'fixture' ? ' in this design preview' : '' ?></p></div>
            <?php if ($items !== []): ?>
                <div class="page-results page-results--<?= e($family) ?>">
                    <?php foreach ($items as $item): if (!is_array($item)) { continue; } result_card($item, $family, ($page['mode'] ?? '') === 'fixture'); endforeach; ?>
                </div>
                <?php pagination($page); ?>
            <?php else: ?>
                <div class="page-empty"><?= icon($unavailable ? 'shield' : 'compass') ?><h3><?= $unavailable ? 'The catalogue cannot be shown right now.' : 'No matches for this view.' ?></h3><p><?= $unavailable ? 'The approved content source is unavailable. No sample records have been substituted for published content.' : 'Try a different search or clear your filters to see the available content.' ?></p><?php if (!$unavailable): ?><a class="text-link" href="<?= e(url(text($page['route'] ?? '/'))) ?>">Reset this view <?= icon('arrow-right') ?></a><?php endif; ?></div>
            <?php endif; ?>
        </section>
        <?php if (in_array($family, ['itinerary', 'tour', 'day-tour'], true)): ?><aside class="page-custom-trip"><div><p class="eyebrow">Not quite what you had in mind?</p><h2>Begin with your own idea.</h2><p>Tell us which places and experiences interest you. An inquiry starts a planning conversation, not a confirmed booking.</p></div><a class="button button--dark" href="<?= e(url('/contact')) ?>">Plan your own journey <?= icon('arrow-up-right') ?></a></aside><?php endif; ?>
    </div>
    <?php related($page); ?>
</div>
