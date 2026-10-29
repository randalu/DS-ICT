<?php
declare(strict_types=1);

/**
 * Footer for the v3 layout. Closes <main>.
 *
 * @var \ICT\View\Page $page
 */

$phone = function_exists('site_primary_phone') ? site_primary_phone() : '';
$email = function_exists('site_contact_email') ? site_contact_email() : '';
$whatsapp = function_exists('site_whatsapp_url') ? site_whatsapp_url() : '';
$partners = function_exists('business_partners_public_enabled') && business_partners_public_enabled();

$columns = [
    [
        'title' => 'Explore',
        'links' => [
            ['label' => 'Destinations', 'url' => base_url('destinations')],
            ['label' => 'Experiences', 'url' => base_url('experiences')],
            ['label' => 'Activities', 'url' => base_url('activities')],
            ['label' => 'Itineraries', 'url' => base_url('itineraries')],
            ['label' => 'Private tours', 'url' => base_url('tours')],
            ['label' => 'Day tours', 'url' => base_url('day-tours')],
        ],
    ],
    [
        'title' => 'Plan',
        'links' => [
            ['label' => 'When to visit', 'url' => base_url('seasonal')],
            ['label' => 'Travel from abroad', 'url' => base_url('from')],
            ['label' => 'Trip planner', 'url' => base_url('plan')],
            ['label' => 'Contact', 'url' => base_url('contact')],
            ['label' => 'FAQ', 'url' => base_url('faq')],
        ],
    ],
    [
        'title' => 'Company',
        'links' => array_values(array_filter([
            ['label' => 'Our story', 'url' => base_url('about-us')],
            ['label' => 'Why travel with us', 'url' => base_url('why-choose-us')],
            ['label' => 'Reviews', 'url' => base_url('reviews')],
            ['label' => 'Journal', 'url' => base_url('blog')],
            $partners ? ['label' => 'Business partners', 'url' => base_url('business-partners')] : null,
            ['label' => 'Account', 'url' => base_url('account')],
        ])),
    ],
];

$legal = [
    ['label' => 'Privacy policy', 'url' => base_url('privacy-policy')],
    ['label' => 'Terms', 'url' => base_url('booking-terms-and-conditions')],
    ['label' => 'Cancellation', 'url' => base_url('cancellation-refund-policy')],
    ['label' => 'Payment', 'url' => base_url('payment-deposit-policy')],
    ['label' => 'Book with confidence', 'url' => base_url('book-with-confidence')],
];
?>
</main>

<footer class="ix-footer">
    <div class="ix-wrap">
        <div class="ix-footer__top">
            <div>
                <a class="ix-brand" href="<?= e(base_url()) ?>">
                    <img class="ix-brand__mark" src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
                    <span>
                        <span class="ix-brand__name">Island Culture</span>
                        <span class="ix-brand__sub">Private Sri Lanka</span>
                    </span>
                </a>
                <p class="ix-footer__pitch">Private driver-guided journeys across Sri Lanka. Your dates, your pace.</p>
                <ul class="ix-footer__contact">
                    <?php if ($phone !== ''): ?><li><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
                    <?php if ($email !== ''): ?><li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li><?php endif; ?>
                    <?php if ($whatsapp !== ''): ?><li><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a></li><?php endif; ?>
                </ul>
                <form class="ix-news" method="post" action="<?= e(base_url('subscribe.php')) ?>">
                    <?= csrf_field() ?>
                    <label class="ix-news__label" for="ix-news-email">Occasional route notes, no noise</label>
                    <div class="ix-news__row">
                        <input class="ix-news__input" id="ix-news-email" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
                        <button class="ix-btn ix-btn--sm ix-btn--sand" type="submit">Join</button>
                    </div>
                </form>
            </div>

            <?php foreach ($columns as $column): ?>
                <nav aria-label="<?= e($column['title']) ?>">
                    <h2 class="ix-footer__title"><?= e($column['title']) ?></h2>
                    <ul class="ix-footer__list">
                        <?php foreach ($column['links'] as $link): ?>
                            <li><a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endforeach; ?>
        </div>

        <div class="ix-footer__bottom">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
            <nav class="ix-footer__legal" aria-label="Legal">
                <?php foreach ($legal as $link): ?>
                    <a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
                <?php endforeach; ?>
                <a href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Privacy choices</a>
            </nav>
            <p>Website by <a href="https://randalu.lk" target="_blank" rel="noopener">Randalu Web Solutions</a></p>
        </div>
    </div>
</footer>

<?php require APP_ROOT . '/includes/privacy_preferences.php'; ?>

<?php if ($whatsapp !== ''): ?>
    <a class="ix-wa" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.8-.9-2-.9-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.2-.2.3-.4.5-.5.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.4A10 10 0 1 0 12 2Z"/></svg>
    </a>
<?php endif; ?>
