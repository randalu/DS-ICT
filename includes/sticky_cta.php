<?php
declare(strict_types=1);

$stickyWhatsAppUrl = site_whatsapp_url(site_default_whatsapp_text());
$stickyPhone = site_primary_phone();
$stickyPhoneHref = 'tel:' . preg_replace('/\\s+/', '', $stickyPhone);
$stickyQuoteUrl = $stickyQuoteUrl ?? base_url('contact');
$stickyQuoteLabel = trim((string) ($stickyQuoteLabel ?? 'Get Quote'));
$stickyCtaNote = trim((string) ($stickyCtaNote ?? ''));
?>

<div class="sticky-cta d-lg-none" data-lightbox-ignore>
    <div class="container">
        <?php if ($stickyCtaNote !== ''): ?>
            <div class="sticky-cta-note"><?= e($stickyCtaNote) ?></div>
        <?php endif; ?>
        <div class="sticky-cta-inner">
            <a href="<?= e($stickyWhatsAppUrl) ?>" target="_blank" rel="noopener" class="sticky-cta-btn sticky-cta-btn-wa" data-track-event="sticky_cta_click">
                WhatsApp
            </a>
            <a href="<?= e($stickyPhoneHref) ?>" class="sticky-cta-btn sticky-cta-btn-call" data-track-event="sticky_cta_click">
                Call
            </a>
            <a href="<?= e($stickyQuoteUrl) ?>" class="sticky-cta-btn sticky-cta-btn-quote" data-track-event="sticky_cta_click">
                <?= e($stickyQuoteLabel !== '' ? $stickyQuoteLabel : 'Get Quote') ?>
            </a>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<button class="back-to-top" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <polyline points="18 15 12 9 6 15" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
</button>

<!-- Floating Plan My Trip Button -->
<div class="floating-plan-trigger" id="floatingPlanTrigger" x-data="{ expanded: false }">
    <button class="floating-plan-btn" @click="expanded = !expanded" :class="{ 'is-expanded': expanded }" aria-label="Plan My Trip">
        <span x-show="!expanded" class="floating-plan-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 5v14M5 12h14"/>
            </svg>
        </span>
        <span x-show="expanded" class="floating-plan-icon" style="display:none;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </span>
    </button>
    <div class="floating-plan-menu" x-show="expanded" x-transition.duration.200ms x-cloak>
        <a href="<?= e(base_url('contact')) ?>" class="floating-plan-menu-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Plan My Trip
        </a>
        <a href="<?= e(site_whatsapp_url(site_default_whatsapp_text())) ?>" target="_blank" rel="noopener" class="floating-plan-menu-item floating-plan-menu-item-wa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/></svg>
            WhatsApp
        </a>
    </div>
</div>
