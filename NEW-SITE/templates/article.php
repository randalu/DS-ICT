<?php
declare(strict_types=1);

require_once __DIR__ . '/page-components.php';

use function NewSite\{e, icon, url};
use function NewSite\View\image;
use function NewSite\Pages\{body_sections, breadcrumbs, faq, gallery, inquiry, paragraphs, related, source_notice, text};

/** @var array $page */
$unavailable = ($page['mode'] ?? '') === 'unavailable';
$record = !$unavailable && is_array($page['record'] ?? null) ? $page['record'] : [];
$sections = is_array($record['sections'] ?? null) ? array_values($record['sections']) : [];
$body = is_array($record['body'] ?? null) ? $record['body'] : [];
// Adapters may expose the same prose as both body and article sections.
$body = array_filter($body, static function ($block) use ($sections): bool {
    foreach ($sections as $section) {
        if (is_array($block) && is_array($section) && ($block['heading'] ?? '') === ($section['heading'] ?? '') && ($block['text'] ?? '') === ($section['text'] ?? '')) {
            return false;
        }
    }
    return true;
});
$hero = is_array($page['hero'] ?? null) ? $page['hero'] : [];
$imagePath = text($record['image'] ?? $hero['image'] ?? '');
$bleed = !$unavailable && $imagePath !== '';
$articleTitle = preg_replace('/ \| Island Culture Travels$/', '', text($record['title'] ?? $page['title'] ?? '')) ?? '';
$articleLead = text($record['summary'] ?? $page['description'] ?? '');
$sectionIds = [];
// A stable index suffix also keeps duplicate or missing source IDs unambiguous.
foreach ($sections as $i => $section) {
    $rawId = is_array($section) ? text($section['id'] ?? $section['heading'] ?? '') : '';
    $slug = trim((string) preg_replace('/[^a-z0-9-]+/i', '-', $rawId), '-');
    $sectionIds[$i] = 'article-' . ($slug !== '' ? strtolower($slug) . '-' : '') . $i;
}
?>
<article class="core-page core-page--article">
    <?php if ($bleed): ?>
    <header class="page-article-hero">
        <div class="page-article-hero__media"><?php image($imagePath, text($record['image_alt'] ?? $hero['alt'] ?? ''), '', '100vw', true); ?></div>
        <div class="container page-article-hero__inner">
            <?php breadcrumbs($page, true); ?>
            <div class="page-article-header page-article-header--hero">
                <p class="eyebrow"><?= ($page['family'] ?? '') === 'guide' ? 'Your Sri Lanka field guide' : 'Notes from the island' ?></p>
                <h1><?= e($articleTitle) ?></h1>
                <?php if ($articleLead !== ''): ?><p class="page-article-lead"><?= e($articleLead) ?></p><?php endif; ?>
                <?php if (!empty($record['author']) || !empty($record['date'])): ?><p class="page-article-byline"><?php if (!empty($record['author'])): ?><span>By <?= e(text($record['author'])) ?></span><?php endif; ?><?php if (!empty($record['date'])): ?><span><?= e(text($record['date'])) ?></span><?php endif; ?></p><?php endif; ?>
                <?php if (!empty($record['label'])): ?><p class="page-sample-label"><?= e(text($record['label'])) ?></p><?php endif; ?>
            </div>
            <?php if (!empty($record['image_caption'])): ?><p class="page-article-hero__caption"><?= e(text($record['image_caption'])) ?></p><?php endif; ?>
        </div>
    </header>
    <?php else: ?>
    <?php breadcrumbs($page); ?>
    <header class="page-article-header container"><p class="eyebrow"><?= ($page['family'] ?? '') === 'guide' ? 'Your Sri Lanka field guide' : 'Notes from the island' ?></p><h1><?= e($articleTitle) ?></h1>
        <?php if ($articleLead !== ''): ?><p class="page-article-lead"><?= e($articleLead) ?></p><?php endif; ?>
        <?php if (!empty($record['author']) || !empty($record['date'])): ?><p class="page-article-byline"><?php if (!empty($record['author'])): ?><span>By <?= e(text($record['author'])) ?></span><?php endif; ?><?php if (!empty($record['date'])): ?><span><?= e(text($record['date'])) ?></span><?php endif; ?></p><?php endif; ?>
        <?php if (!empty($record['label'])): ?><p class="page-sample-label"><?= e(text($record['label'])) ?></p><?php endif; ?>
    </header>
    <?php endif; ?>
    <div class="container page-article-content">
        <?php source_notice($page); ?>
        <?php if ($unavailable || $record === []): ?><section class="page-empty" aria-labelledby="article-unavailable-title"><?= icon('shield') ?><h2 id="article-unavailable-title">This story is unavailable.</h2><p>The full article could not be loaded from the approved source. No sample article has been substituted.</p></section>
        <?php else: ?>
            <div class="page-reading-layout">
                <?php if ($sections !== []): ?><nav class="page-contents" aria-label="Article contents"><p class="eyebrow">In this story</p><ol><?php foreach ($sections as $i => $section): if (!is_array($section) || empty($section['heading'])) { continue; } ?><li><a href="#<?= e($sectionIds[$i]) ?>"><?= e(text($section['heading'])) ?></a></li><?php endforeach; ?></ol></nav><?php endif; ?>
                <div class="page-reading-column">
                    <?php if ($body !== []): body_sections($body, 'article-intro'); endif; ?>
                    <?php foreach ($sections as $i => $section): if (!is_array($section)) { continue; } ?><section class="page-prose-section" id="<?= e($sectionIds[$i]) ?>"><?php if (!empty($section['heading'])): ?><h2><?= e(text($section['heading'])) ?></h2><?php endif; ?><div class="page-prose"><?php paragraphs($section['text'] ?? ''); ?></div><?php if (!empty($section['image'])): ?><figure class="page-article-figure"><?php image(text($section['image']), text($section['image_alt'] ?? ''), '', '(max-width: 760px) 90vw, 65ch'); ?><?php if (!empty($section['caption'])): ?><figcaption><?= e(text($section['caption'])) ?></figcaption><?php endif; ?></figure><?php endif; ?></section><?php endforeach; ?>
                    <?php if (!empty($record['context_links']) && is_array($record['context_links'])): ?><nav class="page-context-links" aria-label="Explore places in this story"><h2>From the page to your journey.</h2><ul><?php foreach ($record['context_links'] as $link): if (!is_array($link)) { continue; } ?><li><a href="<?= e(url(text($link['url'] ?? '/'))) ?>"><?= e(text($link['title'] ?? '')) ?> <?= icon('arrow-up-right') ?></a></li><?php endforeach; ?></ul></nav><?php endif; ?>
                    <?php if (!empty($record['faq']) && is_array($record['faq'])): faq($record['faq']); endif; ?>
                    <?php if (!empty($record['gallery']) && is_array($record['gallery'])): gallery($record['gallery']); endif; ?>
                    <?php inquiry($page, true); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php related($page); ?>
</article>
