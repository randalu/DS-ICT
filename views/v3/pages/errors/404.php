<?php
declare(strict_types=1);

/**
 * 404 — Daylight (v3) recovery page.
 *
 * @var \ICT\View\Page $page
 */

$message = (string) ($page->data['message'] ?? 'Page not found');
?>
<section class="ix-section">
    <div class="ix-wrap ix-wrap--narrow" style="text-align:center">
        <p class="ix-eyebrow">404</p>
        <h1 class="ix-h1">That page moved on.</h1>
        <p class="ix-lead" style="margin-inline:auto"><?= e($message) ?>. The link may be old, or the page is no longer published.</p>

        <div style="display:flex;justify-content:center;gap:.6rem;flex-wrap:wrap;margin-top:1.5rem">
            <a class="ix-btn" href="<?= e(base_url()) ?>">Back to home</a>
            <a class="ix-btn ix-btn--ghost" href="<?= e(base_url('destinations')) ?>">Browse destinations</a>
        </div>

        <div class="ix-journal-list" style="margin-top:3rem;text-align:left">
            <a class="ix-journal-row" href="<?= e(base_url('itineraries')) ?>">
                <span class="ix-journal-row__thumb" aria-hidden="true"></span>
                <h3>Signature itineraries</h3>
                <span aria-hidden="true">&rarr;</span>
            </a>
            <a class="ix-journal-row" href="<?= e(base_url('experiences')) ?>">
                <span class="ix-journal-row__thumb" aria-hidden="true"></span>
                <h3>Experiences</h3>
                <span aria-hidden="true">&rarr;</span>
            </a>
            <a class="ix-journal-row" href="<?= e(base_url('contact')) ?>">
                <span class="ix-journal-row__thumb" aria-hidden="true"></span>
                <h3>Plan a trip</h3>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </div>
    </div>
</section>
