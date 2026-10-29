<?php
declare(strict_types=1);

/**
 * Home page template.
 *
 * Sections follow the Phase 1 blueprint: hero, route finder, editorial intro,
 * signature journeys, destinations, experiences rail, day tours, how it works,
 * a verifiable trust strip (reviews render only when real ones exist), journal
 * and a closing call to action.
 *
 * @var \ICT\View\Page $page
 */

$data = $page->data;
$hero = $data['hero'];
$cta = $data['cta'];
$card = static function (array $card, string $variant = 'default', bool $eager = false): void {
    require APP_ROOT . '/views/partials/card.php';
};
?>
<section class="ic-hero" aria-labelledby="ic-hero-title">
    <div class="ic-hero__media">
        <?php if (($hero['video'] ?? '') !== ''): ?>
            <video class="ic-hero__video" autoplay muted loop playsinline preload="metadata"
                   poster="<?= e($hero['image']) ?>" aria-hidden="true" tabindex="-1">
                <source src="<?= e($hero['video']) ?>" type="video/mp4">
            </video>
        <?php else: ?>
            <img class="ic-hero__img" src="<?= e($hero['image']) ?>" alt="" fetchpriority="high" decoding="async" width="1920" height="1080">
        <?php endif; ?>
    </div>

    <div class="ic-hero__scrim" aria-hidden="true"></div>

    <div class="ic-wrap ic-hero__inner">
        <p class="ic-eyebrow ic-eyebrow--light"><?= e($hero['eyebrow']) ?></p>
        <h1 class="ic-hero__title" id="ic-hero-title"><?= e($hero['title']) ?></h1>
        <p class="ic-hero__support"><?= e($hero['supporting']) ?></p>

        <div class="ic-hero__actions">
            <a class="ic-btn ic-btn--sun" href="<?= e($hero['secondary_url']) ?>"><?= e($hero['secondary_label']) ?></a>
            <a class="ic-btn ic-btn--ghost" href="<?= e($hero['primary_url']) ?>"><?= e($hero['primary_label']) ?></a>
        </div>

        <ul class="ic-hero__trust">
            <?php foreach ($data['trust'] as $trust): ?>
                <li>
                    <strong><?= e($trust['title']) ?></strong>
                    <span><?= e($trust['text']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="ic-finder" aria-labelledby="ic-finder-title">
    <div class="ic-wrap">
        <form class="ic-finder__form" method="get" action="<?= e(base_url('plan')) ?>">
            <h2 class="ic-finder__title" id="ic-finder-title">Where would you like to go?</h2>
            <div class="ic-finder__fields">
                <label class="ic-field">
                    <span class="ic-field__label">Destination</span>
                    <select class="ic-field__control" name="destination">
                        <option value="">Anywhere in Sri Lanka</option>
                        <?php foreach ($data['finder']['destinations'] as $destinationName): ?>
                            <option value="<?= e($destinationName) ?>"><?= e($destinationName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="ic-field">
                    <span class="ic-field__label">Trip length</span>
                    <select class="ic-field__control" name="days">
                        <option value="">Not sure yet</option>
                        <option value="3-5">3â€“5 days</option>
                        <option value="6-9">6â€“9 days</option>
                        <option value="10-14">10â€“14 days</option>
                        <option value="15+">15+ days</option>
                    </select>
                </label>
                <label class="ic-field">
                    <span class="ic-field__label">Interest</span>
                    <select class="ic-field__control" name="interest">
                        <option value="">A bit of everything</option>
                        <?php foreach ($data['finder']['interests'] as $interest): ?>
                            <option value="<?= e($interest) ?>"><?= e($interest) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button class="ic-btn ic-btn--lagoon" type="submit">Find my route</button>
            </div>
        </form>
    </div>
</section>

<section class="ic-section" aria-labelledby="ic-intro-title">
    <div class="ic-wrap ic-intro">
        <div class="ic-intro__lead">
            <p class="ic-eyebrow"><?= e($data['intro']['eyebrow']) ?></p>
            <h2 class="ic-display" id="ic-intro-title"><?= e($data['intro']['title']) ?></h2>
        </div>
        <div class="ic-intro__body">
            <p><?= e($data['intro']['body']) ?></p>
            <a class="ic-textlink" href="<?= e(base_url('about-us')) ?>">How we work <span aria-hidden="true">&rarr;</span></a>
        </div>
    </div>
</section>

<?php if ($data['journeys'] !== []): ?>
<section class="ic-section ic-section--sand" aria-labelledby="ic-journeys-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">Signature journeys</p>
                <h2 class="ic-h2" id="ic-journeys-title">Routes we would send a friend on</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('itineraries')) ?>">All itineraries <span aria-hidden="true">&rarr;</span></a>
        </header>

        <div class="ic-grid ic-grid--3" data-ic-reveal>
            <?php foreach ($data['journeys'] as $journey): ?>
                <?php $card($journey, 'journey'); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($data['destinations'] !== []): ?>
<section class="ic-section" aria-labelledby="ic-destinations-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">Where you can go</p>
                <h2 class="ic-h2" id="ic-destinations-title">Destinations worth the drive</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('destinations')) ?>">All destinations <span aria-hidden="true">&rarr;</span></a>
        </header>

        <?php if ($data['regions'] !== []): ?>
            <ul class="ic-chips" aria-label="Regions">
                <?php foreach (array_slice($data['regions'], 0, 6) as $region): ?>
                    <li><a class="ic-chip ic-chip--link" href="<?= e(base_url('destinations')) ?>"><?= e($region) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <div class="ic-grid ic-grid--destinations" data-ic-reveal>
            <?php foreach ($data['destinations'] as $index => $destination): ?>
                <?php $card($destination, $index === 0 ? 'destination ic-card--wide' : 'destination', $index === 0); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($data['experiences'] !== []): ?>
<section class="ic-section ic-section--mist" aria-labelledby="ic-experiences-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">What you can do</p>
                <h2 class="ic-h2" id="ic-experiences-title">Experiences, not checklists</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('experiences')) ?>">All experiences <span aria-hidden="true">&rarr;</span></a>
        </header>
    </div>

    <div class="ic-rail" role="region" aria-label="Experiences" tabindex="0">
        <ul class="ic-rail__track">
            <?php foreach ($data['experiences'] as $experience): ?>
                <li class="ic-rail__item">
                    <?php $card($experience, 'experience'); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php if ($data['dayTours'] !== []): ?>
<section class="ic-section" aria-labelledby="ic-daytours-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">Short and close</p>
                <h2 class="ic-h2" id="ic-daytours-title">Day tours from the coast or the capital</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('day-tours')) ?>">All day tours <span aria-hidden="true">&rarr;</span></a>
        </header>

        <div class="ic-grid ic-grid--3" data-ic-reveal>
            <?php foreach ($data['dayTours'] as $dayTour): ?>
                <?php $card($dayTour, 'daytour'); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="ic-section ic-section--sand" aria-labelledby="ic-steps-title">
    <div class="ic-wrap">
        <header class="ic-section__head ic-section__head--center">
            <div>
                <p class="ic-eyebrow">How it works</p>
                <h2 class="ic-h2" id="ic-steps-title">Three steps, one conversation</h2>
            </div>
        </header>

        <ol class="ic-steps" data-ic-reveal>
            <?php foreach ($data['steps'] as $step): ?>
                <li class="ic-steps__item">
                    <span class="ic-steps__number"><?= e($step['number']) ?></span>
                    <h3 class="ic-h3"><?= e($step['title']) ?></h3>
                    <p><?= e($step['text']) ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</section>

<?php if ($data['reviews'] !== []): ?>
<section class="ic-section" aria-labelledby="ic-reviews-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">Guest feedback</p>
                <h2 class="ic-h2" id="ic-reviews-title">What travellers said</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('reviews')) ?>">All reviews <span aria-hidden="true">&rarr;</span></a>
        </header>

        <ul class="ic-quotes" data-ic-reveal>
            <?php foreach ($data['reviews'] as $review): ?>
                <li class="ic-quote">
                    <p class="ic-quote__rating" aria-label="<?= (int) $review['rating'] ?> out of 5"><?= str_repeat('â˜…', (int) $review['rating']) ?><span class="ic-quote__muted"><?= str_repeat('â˜…', 5 - (int) $review['rating']) ?></span></p>
                    <?php if ($review['title'] !== ''): ?>
                        <h3 class="ic-h3"><?= e($review['title']) ?></h3>
                    <?php endif; ?>
                    <blockquote><p><?= e($review['text']) ?></p></blockquote>
                    <p class="ic-quote__meta">
                        <?= e($review['name']) ?><?= $review['country'] !== '' ? ', ' . e($review['country']) : '' ?>
                        <?= $review['date'] !== '' ? ' Â· ' . e($review['date']) : '' ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<?php if ($data['articles'] !== []): ?>
<section class="ic-section ic-section--mist" aria-labelledby="ic-journal-title">
    <div class="ic-wrap">
        <header class="ic-section__head">
            <div>
                <p class="ic-eyebrow">Journal</p>
                <h2 class="ic-h2" id="ic-journal-title">Reading before you book</h2>
            </div>
            <a class="ic-textlink" href="<?= e(base_url('blog')) ?>">All articles <span aria-hidden="true">&rarr;</span></a>
        </header>

        <div class="ic-grid ic-grid--3" data-ic-reveal>
            <?php foreach ($data['articles'] as $article): ?>
                <?php $card($article, 'article'); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="ic-cta" aria-labelledby="ic-cta-title">
    <div class="ic-wrap ic-cta__inner">
        <div>
            <p class="ic-eyebrow ic-eyebrow--light">Plan my trip</p>
            <h2 class="ic-h2 ic-cta__title" id="ic-cta-title"><?= e($cta['title']) ?></h2>
            <p class="ic-cta__body"><?= e($cta['body']) ?></p>
        </div>
        <div class="ic-cta__actions">
            <a class="ic-btn ic-btn--sun" href="<?= e($cta['url']) ?>"><?= e($cta['label']) ?></a>
            <?php $whatsapp = function_exists('site_whatsapp_url') ? site_whatsapp_url() : ''; ?>
            <?php if ($whatsapp !== ''): ?>
                <a class="ic-btn ic-btn--ghost" href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">Message on WhatsApp</a>
            <?php endif; ?>
        </div>
    </div>
</section>
