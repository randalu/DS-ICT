<?php
declare(strict_types=1);

/**
 * Footer for the v2 public layout.
 *
 * Closes <main>, then renders brand/contact, three link columns, the legal
 * row, the consent preferences dialog and the WhatsApp float. Every URL here
 * already exists in .htaccess, so the footer adds no new routes.
 *
 * @var \ICT\View\Page $page
 */

$phone = function_exists('site_primary_phone') ? site_primary_phone() : '';
$email = function_exists('site_contact_email') ? site_contact_email() : '';
$whatsapp = function_exists('site_whatsapp_url') ? site_whatsapp_url() : '';
$whatsappNumber = function_exists('site_whatsapp_display_number') ? site_whatsapp_display_number() : '';
$partnersEnabled = function_exists('business_partners_public_enabled') && business_partners_public_enabled();

$columns = [
    [
        'title' => 'Explore',
        'links' => [
            ['label' => 'Destinations', 'url' => base_url('destinations')],
            ['label' => 'Experiences', 'url' => base_url('experiences')],
            ['label' => 'Activities', 'url' => base_url('activities')],
            ['label' => 'Signature itineraries', 'url' => base_url('itineraries')],
            ['label' => 'Private tours', 'url' => base_url('tours')],
            ['label' => 'Day tours', 'url' => base_url('day-tours')],
        ],
    ],
    [
        'title' => 'Travel guide',
        'links' => [
            ['label' => 'When to visit', 'url' => base_url('seasonal')],
            ['label' => 'Travel from abroad', 'url' => base_url('from')],
            ['label' => 'Journal', 'url' => base_url('blog')],
            ['label' => 'Trip planner', 'url' => base_url('plan')],
            ['label' => 'FAQ', 'url' => base_url('faq')],
            ['label' => 'Reviews', 'url' => base_url('reviews')],
        ],
    ],
    [
        'title' => 'Company',
        'links' => array_values(array_filter([
            ['label' => 'Our story', 'url' => base_url('about-us')],
            ['label' => 'Why travel with us', 'url' => base_url('why-choose-us')],
            ['label' => 'Contact', 'url' => base_url('contact')],
            $partnersEnabled ? ['label' => 'Business partners', 'url' => base_url('business-partners')] : null,
            ['label' => 'Account', 'url' => base_url('account')],
        ])),
    ],
];

$legalLinks = [
    ['label' => 'Privacy policy', 'url' => base_url('privacy-policy')],
    ['label' => 'Terms & conditions', 'url' => base_url('booking-terms-and-conditions')],
    ['label' => 'Cancellation & refunds', 'url' => base_url('cancellation-refund-policy')],
    ['label' => 'Payment & deposit', 'url' => base_url('payment-deposit-policy')],
    ['label' => 'Book with confidence', 'url' => base_url('book-with-confidence')],
];
?>
</main>

<footer class="ic-footer">
    <div class="ic-wrap ic-footer__grid">
        <div class="ic-footer__brand">
            <a class="ic-brand ic-brand--footer" href="<?= e(base_url()) ?>">
                <img class="ic-brand__mark" src="<?= e(base_url('assets/images/island_culture_travels_logo.png')) ?>" alt="" width="42" height="42" loading="lazy" decoding="async">
                <span class="ic-brand__text">
                    <span class="ic-brand__name">Island Culture</span>
                    <span class="ic-brand__sub">Private Sri Lanka travel</span>
                </span>
            </a>
            <p class="ic-footer__pitch">Private, driver-guided journeys across Sri Lanka — planned around your dates, your pace and the places you actually want to see.</p>

            <ul class="ic-footer__contact">
                <?php if ($phone !== ''): ?>
                    <li><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a></li>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <li><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
                <?php endif; ?>
                <?php if ($whatsapp !== ''): ?>
                    <li><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp<?= $whatsappNumber !== '' ? ' ' . e($whatsappNumber) : '' ?></a></li>
                <?php endif; ?>
            </ul>

            <form class="ic-news" method="post" action="<?= e(base_url('subscribe.php')) ?>">
                <?= csrf_field() ?>
                <label class="ic-news__label" for="ic-news-email">Occasional route notes, no noise</label>
                <div class="ic-news__row">
                    <input class="ic-news__input" id="ic-news-email" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
                    <button class="ic-btn ic-btn--sun ic-btn--sm" type="submit">Subscribe</button>
                </div>
            </form>
        </div>

        <?php foreach ($columns as $column): ?>
            <nav class="ic-footer__col" aria-label="<?= e($column['title']) ?>">
                <h2 class="ic-footer__title"><?= e($column['title']) ?></h2>
                <ul>
                    <?php foreach ($column['links'] as $link): ?>
                        <li><a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endforeach; ?>
    </div>

    <div class="ic-wrap ic-footer__bottom">
        <p class="ic-footer__copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        <nav class="ic-footer__legal" aria-label="Legal">
            <?php foreach ($legalLinks as $link): ?>
                <a href="<?= e($link['url']) ?>"><?= e($link['label']) ?></a>
            <?php endforeach; ?>
            <a href="<?= e(ict_privacy_url('privacy_choices.php')) ?>" data-analytics-open data-no-track="1">Privacy choices</a>
        </nav>
        <p class="ic-footer__credit">Website by <a href="https://randalu.lk" target="_blank" rel="noopener">Randalu Web Solutions</a></p>
    </div>
</footer>

<?php require APP_ROOT . '/includes/privacy_preferences.php'; ?>

<?php if ($whatsapp !== ''): ?>
    <a class="ic-wa" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="Chat with us on WhatsApp">
        <svg viewBox="0 0 24 24" fill="currentColor" width="26" height="26" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.8-.9-2-.9-.3-.1-.5-.2-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6.2-.2.3-.4.5-.5.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.3M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.4A10 10 0 1 0 12 2Z"/></svg>
    </a>
<?php endif; ?>
