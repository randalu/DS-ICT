<?php
declare(strict_types=1);

$ctaTitle = $ctaTitle ?? 'Plan Your Private Sri Lanka Journey Today';
$ctaText = $ctaText ?? 'Tell us your travel dates, group size, and destinations, and we will prepare a personalized travel plan with the right vehicle and professional chauffeur for your trip.';
$ctaButtonText = $ctaButtonText ?? 'Request a Quote';
$ctaButtonUrl = $ctaButtonUrl ?? base_url('contact');
$ctaImage = $ctaImage ?? 'assets/images/home-hero/home-5.jpg';

$ctaWhatsappText = $ctaWhatsappText ?? site_default_whatsapp_text();
$whatsAppUrl = site_whatsapp_url($ctaWhatsappText);
?>
<section class="cta-section-custom" x-data>
    <div class="container">
        <div class="cta-card">
            <div class="cta-card-body">
                <h2 class="cta-heading"><?= e($ctaTitle) ?></h2>
                <p class="cta-text"><?= e($ctaText) ?></p>
                <ul class="cta-features">
                    <li>Private chauffeur-guided travel</li>
                    <li>Flexible itineraries built around you</li>
                    <li>Handpicked hotels & local experiences</li>
                </ul>
            </div>
            <div class="cta-card-actions">
                <a href="<?= e($ctaButtonUrl) ?>" class="cta-btn cta-btn-primary"><?= e($ctaButtonText) ?></a>
                <a href="<?= e($whatsAppUrl) ?>" target="_blank" rel="noopener" class="cta-btn cta-btn-whatsapp" data-track-event="whatsapp_click">
                    WhatsApp Us
                </a>
            </div>
        </div>
    </div>
</section>