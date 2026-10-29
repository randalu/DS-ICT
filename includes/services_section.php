<?php
declare(strict_types=1);

$services = fetch_active_services();
if (!$services) {
    return;
}
?>
<section class="services-section services-section-compact">
    <div class="container">
        <div class="mb-4">
            <span class="section-kicker">What We Offer</span>
            <h2 class="section-heading" style="color:#fff;">Everything <em>You Need</em></h2>
        </div>
        <div class="services-grid">
            <?php foreach ($services as $svc): ?>
            <div class="service-card">
                <span class="service-card-icon"><?= e($svc['icon']) ?></span>
                <div class="service-card-title"><?= e($svc['title']) ?></div>
                <p class="service-card-text"><?= e($svc['description']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
