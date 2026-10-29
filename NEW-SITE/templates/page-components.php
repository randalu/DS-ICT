<?php
declare(strict_types=1);

namespace NewSite\Pages;

use function NewSite\{e, icon, url};
use function NewSite\View\{card, image};

function family_name(string $family): string
{
    return match ($family) {
        'destination' => 'Places of Sri Lanka',
        'itinerary' => 'Suggested journeys',
        'tour' => 'Private tours',
        'day-tour' => 'A day to discover',
        'experience' => 'Island experiences',
        'blog' => 'Notes from the island',
        'partner' => 'Business partners',
        default => 'Your Sri Lanka field guide',
    };
}

function text(mixed $value): string
{
    return is_scalar($value) ? (string) $value : '';
}

function paragraphs(mixed $value): void
{
    $value = trim(text($value));
    if ($value === '') {
        return;
    }
    foreach (preg_split('/\R\s*\R/u', $value) ?: [$value] as $paragraph) {
        echo '<p>' . nl2br(e($paragraph), false) . '</p>';
    }
}

function breadcrumbs(array $page, bool $hero = false): void
{
    $crumbs = $page['breadcrumbs'] ?? [];
    if (!is_array($crumbs) || $crumbs === []) {
        return;
    }
    ?>
    <nav class="page-breadcrumbs<?= $hero ? ' page-breadcrumbs--hero' : ' container' ?>" aria-label="Breadcrumb"><ol>
        <?php foreach ($crumbs as $i => $crumb): if (!is_array($crumb)) { continue; } ?>
            <li><?php if (!empty($crumb['url']) && $i !== array_key_last($crumbs)): ?><a href="<?= e(url(text($crumb['url']))) ?>"><?= e(text($crumb['title'] ?? '')) ?></a><?php else: ?><span aria-current="page"><?= e(text($crumb['title'] ?? '')) ?></span><?php endif; ?></li>
        <?php endforeach; ?>
    </ol></nav>
    <?php
}

function source_notice(array $page): void
{
    $mode = text($page['mode'] ?? 'unavailable');
    if ($mode === 'database' && empty($page['source_message'])) {
        return;
    }
    ?>
    <aside class="page-source<?= $mode === 'unavailable' ? ' page-source--unavailable' : '' ?>" aria-label="Content source">
        <?= icon($mode === 'unavailable' ? 'shield' : 'compass') ?>
        <div><strong><?= $mode === 'fixture' ? 'Design preview. Sample content, not a published offer.' : ($mode === 'unavailable' ? 'Content source unavailable' : 'Read-only content preview') ?></strong>
            <?php if (!empty($page['source_message'])): ?><p><?= e(text($page['source_message'])) ?></p><?php endif; ?>
        </div>
    </aside>
    <?php
}

function hero_media_path(array $page): string
{
    $record = is_array($page['record'] ?? null) ? $page['record'] : [];
    $hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
    return text($hero['image'] ?? $record['image'] ?? '');
}

function page_has_bleed_hero(array $page): bool
{
    return hero_media_path($page) !== '' && text($page['mode'] ?? 'fixture') !== 'unavailable';
}

function header(array $page, bool $detail = false): void
{
    $family = text($page['family'] ?? 'guide');
    $record = is_array($page['record'] ?? null) ? $page['record'] : [];
    $hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
    $title = $detail ? text($record['title'] ?? $page['title'] ?? '') : text($page['title'] ?? '');
    $title = preg_replace('/ \| Island Culture Travels$/', '', $title) ?? $title;
    $summary = $detail ? text($record['summary'] ?? $page['description'] ?? '') : text($page['description'] ?? '');
    $path = hero_media_path($page);
    $alt = text($hero['alt'] ?? $hero['image_alt'] ?? $record['image_alt'] ?? '');
    $bleed = page_has_bleed_hero($page);
    ?>
    <header class="page-opening page-opening--<?= e($family) ?><?= $detail ? ' page-opening--detail' : '' ?><?= $bleed ? ' page-opening--bleed' : ' page-opening--text-only' ?>">
        <?php if ($bleed): ?><div class="page-opening__media"><?php image($path, $alt, '', '100vw', true); ?><?php if (!empty($hero['place'])): ?><p><?= icon('pin') ?> <?= e(text($hero['place'])) ?></p><?php endif; ?></div><?php endif; ?>
        <div class="page-opening__inner container">
            <?php if ($bleed): breadcrumbs($page, true); endif; ?>
            <div class="page-opening__copy">
                <p class="eyebrow"><?= e(family_name($family)) ?></p>
                <h1><?= e($title) ?></h1>
                <?php if ($detail && (!empty($record['region']) || !empty($record['theme']))): ?><p class="page-opening__taxonomy"><?php if (!empty($record['region'])): ?><span>Region: <?= e(text($record['region'])) ?></span><?php endif; ?><?php if (!empty($record['theme'])): ?><span>Theme: <?= e(text($record['theme'])) ?></span><?php endif; ?></p><?php endif; ?>
                <?php if ($summary !== ''): ?><p class="page-opening__description"><?= e($summary) ?></p><?php endif; ?>
                <?php if ($detail && !empty($record['label'])): ?><p class="page-sample-label"><?= e(text($record['label'])) ?></p><?php endif; ?>
                <?php if (!$detail && in_array($family, ['itinerary', 'tour', 'day-tour'], true)): ?><a class="text-link" href="<?= e(url('/contact')) ?>">Start with your own ideas <?= icon('arrow-up-right') ?></a><?php endif; ?>
            </div>
        </div>
    </header>
    <?php
}

function filters(array $page): void
{
    $family = text($page['family'] ?? '');
    $names = match ($family) {
        'itinerary' => ['days' => 'Journey length', 'theme' => 'Your interests', 'traveller' => 'Travelling as', 'start' => 'Starting point'],
        'day-tour' => ['region' => 'Region', 'difficulty' => 'Activity level', 'price' => 'Price range', 'q' => 'Search day tours'],
        'experience' => ['mood' => 'Your interests'],
        'destination' => ['region' => 'Region', 'q' => 'Search places'],
        'blog' => ['category' => 'Category', 'q' => 'Search the journal'],
        default => [],
    };
    $options = is_array($page['filter_options'] ?? null) ? $page['filter_options'] : [];
    $values = is_array($page['filters'] ?? null) ? $page['filters'] : [];
    $controls = array_filter($names, static fn ($label, $name) => $name === 'q' || (!empty($options[$name]) && is_array($options[$name])), ARRAY_FILTER_USE_BOTH);
    if ($controls === []) {
        return;
    }
    ?>
    <form class="page-filters" action="<?= e(url(text($page['route'] ?? '/'))) ?>" method="get" aria-label="Filter <?= e(family_name($family)) ?>">
        <?php foreach ($controls as $name => $label): $current = text($values[$name] ?? ''); ?>
            <div class="page-filter<?= $name === 'q' ? ' page-filter--search' : '' ?>"><label for="filter-<?= e($name) ?>"><?= e($label) ?></label>
                <?php if ($name === 'q'): ?><input type="search" name="q" id="filter-q" value="<?= e($current) ?>" placeholder="Search by name or keyword">
                <?php else: ?><select id="filter-<?= e($name) ?>" name="<?= e($name) ?>"><option value=""><?= e(match ($name) { 'days' => 'Any journey length', 'theme', 'mood' => 'All interests', 'traveller' => 'Any traveller type', 'start' => 'Any starting point', 'region' => 'All regions', 'difficulty' => 'Any activity level', 'price' => 'Any price range', 'category' => 'All categories', default => 'All options' }) ?></option>
                    <?php foreach ($options[$name] as $value => $option): $optionValue = is_array($option) ? text($option['value'] ?? '') : (string) $value; $optionLabel = is_array($option) ? text($option['label'] ?? $optionValue) : text($option); ?>
                        <option value="<?= e($optionValue) ?>"<?= $current === $optionValue ? ' selected' : '' ?>><?= e($optionLabel) ?></option>
                    <?php endforeach; ?>
                </select><?php endif; ?>
            </div>
        <?php endforeach; ?>
        <button class="button button--dark" type="submit">Show results <?= icon('arrow-right') ?></button>
        <a class="page-filter-reset" href="<?= e(url(text($page['route'] ?? '/'))) ?>">Reset filters</a>
    </form>
    <?php
}

function result_card(array $item, string $family, bool $sample = false): void
{
    $item = array_merge(['title' => '', 'summary' => '', 'image' => '', 'image_alt' => '', 'url' => '/', 'label' => ''], $item);
    if ($sample && empty($item['label'])) {
        $item['label'] = 'Design sample - not a published offer';
    }
    if (in_array($family, ['itinerary', 'tour', 'day-tour'], true)) {
        // Detailed day arrays are not the scalar duration expected by the shared card.
        if (isset($item['days']) && !is_scalar($item['days'])) {
            unset($item['days']);
        }
        if (empty($item['days']) && !empty($item['duration_days']) && is_scalar($item['duration_days'])) {
            $item['days'] = $item['duration_days'];
        }
        card($item, $family);
        return;
    }
    $editorial = in_array($family, ['blog', 'guide'], true);
    ?>
    <article class="page-card page-card--<?= e($family) ?>">
        <a class="page-card__link" href="<?= e(url(text($item['url']))) ?>">
            <div class="page-card__image"><?php image(text($item['image']), text($item['image_alt']), '', '(max-width: 640px) 90vw, (max-width: 1024px) 46vw, 32vw'); ?><span class="image-action" aria-hidden="true"><?= icon('arrow-up-right') ?></span></div>
            <div class="page-card__copy">
                <?php if (!empty($item['region']) || !empty($item['theme']) || $editorial): ?><p class="eyebrow"><?= e(text($item['region'] ?? $item['theme'] ?? ($family === 'blog' ? 'Travel journal' : 'Planning perspective'))) ?><?php if (!empty($item['date'])): ?> &middot; <?= e(text($item['date'])) ?><?php endif; ?></p><?php endif; ?>
                <h3><?= e(text($item['title'])) ?></h3>
                <?php if (!empty($item['summary'])): ?><p class="page-card__summary"><?= e(text($item['summary'])) ?></p><?php endif; ?>
                <?php if (!empty($item['facts']) && is_array($item['facts'])): ?><dl class="page-card__facts"><?php foreach ($item['facts'] as $fact): if (!is_array($fact)) { continue; } ?><div><dt><?= e(text($fact['label'] ?? '')) ?></dt><dd><?= e(text($fact['value'] ?? '')) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
                <?php if ($editorial): ?><span class="text-link">Read <?= $family === 'blog' ? 'the story' : 'the guide' ?> <?= icon('arrow-up-right') ?></span><?php endif; ?>
                <?php if (!empty($item['label'])): ?><p class="page-sample-label"><?= e(text($item['label'])) ?></p><?php endif; ?>
            </div>
        </a>
    </article>
    <?php
}

function pagination(array $page): void
{
    $pagination = $page['pagination'] ?? [];
    $pages = max(1, (int) ($pagination['pages'] ?? 1));
    $current = max(1, min($pages, (int) ($pagination['page'] ?? 1)));
    if ($pages < 2) {
        return;
    }
    $query = array_filter(is_array($page['filters'] ?? null) ? $page['filters'] : [], static fn ($value) => is_scalar($value) && (string) $value !== '');
    $route = text($page['route'] ?? '/');
    $numbers = array_values(array_unique(array_merge([1], range(max(1, $current - 2), min($pages, $current + 2)), [$pages])));
    ?>
    <nav class="page-pagination" aria-label="Results pages">
        <?php if ($current > 1): ?><a class="page-pagination__direction" rel="prev" href="<?= e(url($route, array_merge($query, ['page' => $current - 1]))) ?>"><?= icon('arrow-left') ?> Previous</a><?php endif; ?>
        <ol><?php $previous = 0; foreach ($numbers as $number): ?><?php if ($previous > 0 && $number > $previous + 1): ?><li><span aria-hidden="true">&hellip;</span></li><?php endif; ?><li><?php if ($number === $current): ?><span aria-current="page" aria-label="Page <?= $number ?>"><?= $number ?></span><?php else: ?><a href="<?= e(url($route, array_merge($query, ['page' => $number]))) ?>" aria-label="Page <?= $number ?>"><?= $number ?></a><?php endif; ?></li><?php $previous = $number; endforeach; ?></ol>
        <?php if ($current < $pages): ?><a class="page-pagination__direction" rel="next" href="<?= e(url($route, array_merge($query, ['page' => $current + 1]))) ?>">Next <?= icon('arrow-right') ?></a><?php endif; ?>
    </nav>
    <?php
}

function facts(array $facts, string $heading = 'At a glance'): void
{
    if ($facts === []) {
        return;
    }
    ?>
    <section class="page-facts" aria-label="<?= e($heading) ?>"><p class="eyebrow"><?= e($heading) ?></p><dl><?php foreach ($facts as $fact): if (!is_array($fact)) { continue; } ?><div><dt><?= e(text($fact['label'] ?? '')) ?></dt><dd><?= e(text($fact['value'] ?? '')) ?></dd></div><?php endforeach; ?></dl></section>
    <?php
}

function body_sections(array $sections, string $prefix = 'overview'): void
{
    foreach ($sections as $i => $section) {
        if (!is_array($section)) {
            continue;
        }
        ?>
        <section class="page-prose-section" id="<?= e($prefix) ?>-<?= e((string) $i) ?>">
            <?php if (!empty($section['heading'])): ?><h2><?= e(text($section['heading'])) ?></h2><?php endif; ?>
            <div class="page-prose"><?php paragraphs($section['text'] ?? ''); ?></div>
        </section>
        <?php
    }
}

function route_ribbon(array $route, string $label = 'The journey, at a glance'): void
{
    if ($route === []) {
        return;
    }
    ?>
    <section class="page-route" aria-label="Journey route"><p class="eyebrow"><?= e($label) ?></p><ol><?php foreach ($route as $stop): $stopTitle = is_array($stop) ? text($stop['title'] ?? '') : text($stop); ?><li><?= icon('pin') ?><?php if (is_array($stop) && !empty($stop['url'])): ?><a href="<?= e(url(text($stop['url']))) ?>"><?= e($stopTitle) ?></a><?php else: ?><span><?= e($stopTitle) ?></span><?php endif; ?></li><?php endforeach; ?></ol></section>
    <?php
}

function day_sequence(array $days, string $family): void
{
    if ($days === []) {
        return;
    }
    $itinerary = $family === 'itinerary';
    $days = array_values($days);
    ?>
    <section class="page-programme" id="programme" aria-labelledby="programme-title"><p class="eyebrow"><?= $itinerary ? 'Follow the journey' : 'How the experience unfolds' ?></p><h2 id="programme-title"><?= $itinerary ? 'Day by day.' : 'The programme.' ?></h2>
        <?php if ($itinerary): ?><nav class="page-day-nav" aria-label="Jump to a day"><?php foreach ($days as $i => $day): $number = is_array($day) ? text($day['day_number'] ?? ($i + 1)) : (string) ($i + 1); $end = is_array($day) ? text($day['day_end'] ?? '') : ''; ?><a href="#programme-<?= e((string) $i) ?>">Day <?= e($number) ?><?= $end !== '' && $end !== $number ? '&ndash;' . e($end) : '' ?></a><?php endforeach; ?></nav><?php endif; ?>
        <ol class="page-day-list"><?php foreach (array_values($days) as $i => $day): if (!is_array($day)) { continue; } ?>
            <?php $number = text($day['day_number'] ?? ($i + 1)); $end = text($day['day_end'] ?? ''); ?>
            <li id="programme-<?= $i ?>"><span class="page-day-marker"><?= $itinerary ? 'Day ' : 'Stop ' ?><?= e($number) ?><?= $end !== '' && $end !== $number ? '&ndash;' . e($end) : '' ?></span><div><h3><?= e(text($day['title'] ?? '')) ?></h3><?php if (!empty($day['location'])): ?><p class="page-day-location"><?= icon('pin') ?> <?= e(text($day['location'])) ?></p><?php endif; ?><?php if (!empty($day['travel_time'])): ?><p class="page-day-location">Travel time: <?= e(text($day['travel_time'])) ?></p><?php endif; ?><div class="page-prose"><?php paragraphs($day['summary'] ?? ''); ?><?php if (!empty($day['highlights']) && is_array($day['highlights'])): ?><ul class="page-day-highlights"><?php foreach ($day['highlights'] as $highlight): ?><li><?= e(text($highlight)) ?></li><?php endforeach; ?></ul><?php endif; ?></div><?php if (!empty($day['image'])): ?><div class="page-day-image"><?php image(text($day['image']), text($day['image_alt'] ?? $day['title'] ?? ''), '', '(max-width: 760px) 75vw, 600px'); ?></div><?php endif; ?></div></li>
        <?php endforeach; ?></ol>
    </section>
    <?php
}

function inclusions(array $record): void
{
    if (empty($record['inclusions']) && empty($record['exclusions'])) {
        return;
    }
    ?>
    <section class="page-inclusions" id="included" aria-label="Inclusions and exclusions"><?php foreach (['inclusions' => 'What is included', 'exclusions' => 'What is not included'] as $key => $label): if (empty($record[$key]) || !is_array($record[$key])) { continue; } ?><div><h2><?= e($label) ?></h2><ul><?php foreach ($record[$key] as $value): ?><li><?= icon($key === 'inclusions' ? 'check' : 'close') ?><span><?= e(text($value)) ?></span></li><?php endforeach; ?></ul></div><?php endforeach; ?></section>
    <?php
}

function faq(array $questions): void
{
    if ($questions === []) {
        return;
    }
    ?>
    <section class="page-faq" id="questions" aria-labelledby="questions-title"><p class="eyebrow">A little more clarity</p><h2 id="questions-title">Useful to know.</h2><?php foreach ($questions as $question): if (!is_array($question)) { continue; } ?><details><summary><?= e(text($question['question'] ?? '')) ?><?= icon('chevron-down') ?></summary><div class="page-prose"><?php paragraphs($question['answer'] ?? ''); ?></div></details><?php endforeach; ?></section>
    <?php
}

function gallery(array $images): void
{
    if ($images === []) {
        return;
    }
    ?>
    <section class="page-gallery" aria-label="Photo gallery"><?php foreach ($images as $item): if (!is_array($item)) { continue; } ?><figure><?php image(text($item['image'] ?? ''), text($item['image_alt'] ?? ''), '', '(max-width: 640px) 90vw, 45vw'); ?><?php if (!empty($item['caption'])): ?><figcaption><?= e(text($item['caption'])) ?></figcaption><?php endif; ?></figure><?php endforeach; ?></section>
    <?php
}

function inquiry(array $page, bool $quiet = false): void
{
    $record = is_array($page['record'] ?? null) ? $page['record'] : [];
    $family = text($page['family'] ?? 'guide');
    $title = text($record['title'] ?? $page['title'] ?? '');
    $query = ['context' => $title];
    if ($family === 'itinerary' && !empty($record['id'])) {
        $query['itinerary_id'] = text($record['id']);
    }
    ?>
    <aside class="page-inquiry<?= $quiet ? ' page-inquiry--quiet' : '' ?>" aria-label="Planning inquiry">
        <p class="eyebrow">Your journey, your pace</p><h2><?= $quiet ? 'Bring it into your journey.' : 'Make room for your ideas.' ?></h2>
        <p><?= $family === 'itinerary' ? 'Use this journey as a starting point for a conversation about your own trip.' : 'Ask how this could fit into your Sri Lanka travel plans.' ?></p>
        <a class="button <?= $quiet ? 'button--dark' : 'button--sun' ?>" href="<?= e(url('/contact', $query)) ?>">Ask about <?= $family === 'itinerary' ? 'this journey' : ($family === 'day-tour' || $family === 'tour' ? 'this tour' : 'your trip') ?> <?= icon('arrow-up-right') ?></a>
        <p class="page-inquiry__note">An inquiry is not a booking confirmation. This read-only preview does not send inquiries or take payments.</p>
    </aside>
    <?php
}

function related(array $page): void
{
    $items = is_array($page['related'] ?? null) ? $page['related'] : [];
    if ($items === [] || ($page['mode'] ?? '') === 'unavailable') {
        return;
    }
    ?>
    <section class="page-related container" aria-labelledby="related-title"><div class="section-heading"><div><p class="eyebrow">Keep exploring</p><h2 id="related-title">Connect the moments.</h2></div><p class="page-related__intro">More places, perspectives and possibilities for your journey.</p></div><div class="page-results page-results--related"><?php foreach ($items as $item): if (!is_array($item)) { continue; } result_card($item, text($item['family'] ?? $page['family'] ?? 'guide'), ($page['mode'] ?? '') === 'fixture'); endforeach; ?></div></section>
    <?php
}
