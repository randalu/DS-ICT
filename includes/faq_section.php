<?php
declare(strict_types=1);

if (!isset($faqItems) || !is_array($faqItems) || !$faqItems) {
    return;
}

$faqHeading = trim((string) ($faqHeading ?? 'Frequently Asked Questions'));
$faqAccordionId = trim((string) ($faqAccordionId ?? 'faqAccordion'));
$faqPrefix = trim((string) ($faqPrefix ?? 'faq'));
$faqCtaTitle = trim((string) ($faqCtaTitle ?? 'Need a Tailored Plan?'));
$faqCtaText = trim((string) ($faqCtaText ?? 'Tell us your dates and priorities. We will suggest the best route, travel pace, and private vehicle setup.'));
$faqCtaLabel = trim((string) ($faqCtaLabel ?? 'Plan My Tour'));
$faqCtaHref = trim((string) ($faqCtaHref ?? base_url('contact')));

$cleanFaq = [];
foreach ($faqItems as $item) {
    $question = trim((string) ($item['question'] ?? ''));
    $answer = trim((string) ($item['answer'] ?? ''));

    if ($question === '' || $answer === '') {
        continue;
    }

    $cleanFaq[] = [
        'question' => $question,
        'answer' => $answer,
    ];
}

if (!$cleanFaq) {
    return;
}

$cleanLinks = [];
if (isset($faqRelatedLinks) && is_array($faqRelatedLinks)) {
    foreach ($faqRelatedLinks as $link) {
        if (!is_array($link)) {
            continue;
        }
        $label = trim((string) ($link['label'] ?? $link['name'] ?? ''));
        $url = trim((string) ($link['url'] ?? ''));
        if ($label === '' || $url === '') {
            continue;
        }
        $cleanLinks[] = ['label' => $label, 'url' => $url];
    }
}

$totalFaq = count($cleanFaq);
?>
<section class="faq-conversion-section">
    <h2><?= e($faqHeading) ?></h2>
    <p class="faq-conversion-intro text-secondary">Quick, direct answers for planning. If you need a personalized route, use the plan button any time.</p>

    <div class="accordion" id="<?= e($faqAccordionId) ?>">
        <?php foreach ($cleanFaq as $index => $faq): ?>
            <?php
            $itemId = $faqPrefix . '-' . $index;
            $answerLinks = array_slice($cleanLinks, 0, 3);
            ?>
            <div class="accordion-item">
                <h3 class="accordion-header" id="heading-<?= e($itemId) ?>">
                    <button class="accordion-button<?= $index === 0 ? '' : ' collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= e($itemId) ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse-<?= e($itemId) ?>">
                        <?= e($faq['question']) ?>
                    </button>
                </h3>
                <div id="collapse-<?= e($itemId) ?>" class="accordion-collapse collapse<?= $index === 0 ? ' show' : '' ?>" aria-labelledby="heading-<?= e($itemId) ?>" data-bs-parent="#<?= e($faqAccordionId) ?>">
                    <div class="accordion-body text-secondary">
                        <?= render_public_content($faq['answer']) ?>
                        <?php if ($answerLinks): ?>
                            <div class="faq-answer-links">
                                <span>Related:</span>
                                <?php foreach ($answerLinks as $link): ?>
                                    <a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ((($index + 1) % 3) === 0 && ($index + 1) < $totalFaq): ?>
                <div class="faq-inline-cta">
                    <div>
                        <strong><?= e($faqCtaTitle) ?></strong>
                        <p class="mb-0 text-secondary"><?= e($faqCtaText) ?></p>
                    </div>
                    <a href="<?= e($faqCtaHref) ?>" class="btn btn-success btn-sm"><?= e($faqCtaLabel) ?></a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
