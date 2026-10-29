<?php
declare(strict_types=1);

use function NewSite\e;
use function NewSite\icon;
use function NewSite\url;
use function NewSite\media;
use function NewSite\View\image;
use function NewSite\View\card;

/** @var array $page */
$journeys = $page['journeys'];
$active = max(0, min((int) $page['active_journey'], max(0, count($journeys) - 1)));
$isSample = $page['mode'] !== 'database';
?>
<section class="hero" aria-labelledby="hero-title">
    <?php image((string) $page['hero']['image'], (string) $page['hero']['alt'], 'hero__image', '100vw', true); ?>
    <div class="hero__shade" aria-hidden="true"></div>
    <div class="hero__inner container">
        <div class="hero__aside" aria-hidden="true"><?= icon('compass') ?><span>An island.<br>A world of possibility.</span></div>
        <div class="hero__copy">
            <p class="eyebrow hero__eyebrow">Private journeys &middot; Local perspectives</p>
            <h1 id="hero-title">Sri Lanka,<br><span>at your pace.</span></h1>
            <p class="hero__description">From the first idea to the road less hurried. Discover Sri Lanka with a private chauffeur and a journey shaped around you.</p>
            <div class="hero__actions"><a class="button button--sun" href="<?= e(url('/itineraries')) ?>">Find your inspiration <?= icon('arrow-up-right') ?></a><a class="hero__text-link" href="<?= e(url('/contact')) ?>">Let's plan your trip <span><?= icon('arrow-right') ?></span></a></div>
        </div>
        <div class="hero__bottom"><p><?= icon('pin') ?> <?= e((string) $page['hero']['place']) ?></p><a href="#journey-ribbon">Let the journey unfold <?= icon('arrow-down') ?></a></div>
    </div>
</section>

<section class="journey-ribbon container" id="journey-ribbon" aria-labelledby="ribbon-title">
    <div class="journey-ribbon__heading"><div><p class="eyebrow">A journey, not a checklist</p><h2 id="ribbon-title">Make room for the moments.</h2></div>
        <?php if ($journeys !== []): ?><form method="get" action="<?= e(url('/')) ?>#journey-ribbon" class="journey-picker"><label for="journey-choice"><?= $isSample ? 'Explore a layout sample' : 'Explore a suggested journey' ?></label><div class="journey-picker__controls"><select name="journey" id="journey-choice" data-journey-select><?php foreach ($journeys as $i => $journey): ?><option value="<?= $i ?>"<?= $i === $active ? ' selected' : '' ?>><?= e((string) $journey['title']) ?></option><?php endforeach; ?></select><button type="submit" aria-label="Show selected journey"><?= icon('arrow-right') ?></button></div></form><?php endif; ?>
    </div>
    <?php foreach ($journeys as $i => $journey): ?>
        <div class="journey-panel" data-journey-panel="<?= $i ?>"<?= $i !== $active ? ' hidden' : '' ?>>
            <div class="journey-panel__visual"><?php image((string) $journey['image'], (string) $journey['image_alt'], '', '(max-width: 640px) 80vw, 180px'); ?></div>
            <div class="journey-panel__route"><p class="journey-panel__label"><?= $isSample ? 'Illustrative sequence only, not a published itinerary' : e((string) ($journey['label'] ?? 'Suggested journey')) ?></p><ol class="route-stops"><?php foreach (($journey['route'] ?? []) as $stop): $stopTitle = is_array($stop) ? (string) ($stop['title'] ?? '') : (string) $stop; $stopUrl = is_array($stop) ? (string) ($stop['url'] ?? $journey['url']) : (string) $journey['url']; ?><li><span class="route-stops__dot" aria-hidden="true"></span><a href="<?= e(url($stopUrl)) ?>"><?= e($stopTitle) ?></a></li><?php endforeach; ?></ol><?php if (empty($journey['route'])): ?><p><?= e((string) $journey['summary']) ?></p><?php endif; ?></div>
            <a class="circle-link" href="<?= e(url((string) $journey['url'])) ?>" aria-label="Explore <?= e((string) $journey['title']) ?>"><?= icon('arrow-up-right') ?></a>
        </div>
    <?php endforeach; ?>
    <?php if ($journeys === []): ?><p class="empty-state">Journey information is not available from the approved content source yet. No sample offers have been substituted.</p><?php endif; ?>
    <p class="sr-only" data-journey-status aria-live="polite"></p>
</section>

<section class="introduction container section-space" aria-labelledby="introduction-title">
    <div><p class="eyebrow">Less following. More finding.</p><h2 id="introduction-title">A small island.<br><span class="text-palm">An extraordinary<br>change of scene.</span></h2></div>
    <div class="introduction__copy"><p class="lead">Ancient places. Misty hills. The ocean, just beyond the palms.</p><p>There is more than one way to see Sri Lanka. Start with what draws you here, then bring the pieces together with private chauffeur travel and a personalised itinerary.</p><a class="text-link" href="<?= e(url('/about-us')) ?>">Get to know Island Culture Travels <?= icon('arrow-up-right') ?></a></div>
</section>

<section class="journeys-section section-space" id="journeys" aria-labelledby="journeys-title">
    <div class="container"><div class="section-heading"><div><p class="eyebrow">Your journey begins with an idea</p><h2 id="journeys-title">A starting point,<br>not a fixed plan.</h2></div><div class="section-heading__aside"><p>Find a little inspiration. Make it your own.</p><a class="text-link" href="<?= e(url('/itineraries')) ?>">Explore itineraries <?= icon('arrow-up-right') ?></a></div></div>
        <div class="travel-grid"><?php foreach (array_slice($journeys, 0, 3) as $journey): card($journey); endforeach; ?></div>
        <?php if ($journeys === []): ?><p class="empty-state">Published itineraries will appear here once the approved read-only source is connected.</p><?php endif; ?>
    </div>
</section>

<section class="destinations-section container section-space" id="destinations" aria-labelledby="destinations-title">
    <div class="section-heading"><div><p class="eyebrow">Follow your curiosity</p><h2 id="destinations-title">Where will you<br><span class="text-palm">find yourself?</span></h2></div><a class="text-link" href="<?= e(url('/destinations')) ?>">Discover the island <?= icon('arrow-up-right') ?></a></div>
    <div class="destination-grid"><?php foreach (array_slice($page['destinations'], 0, 4) as $i => $destination): ?><article class="destination-card destination-card--<?= $i ?>"><a href="<?= e(url((string) $destination['url'])) ?>"><div class="destination-card__image"><?php image((string) $destination['image'], (string) $destination['image_alt'], '', '(max-width: 640px) 90vw, (max-width: 1024px) 45vw, 40vw'); ?><span class="image-action" aria-hidden="true"><?= icon('arrow-up-right') ?></span></div><div class="destination-card__copy"><p class="eyebrow"><?= e((string) $destination['summary']) ?></p><h3><?= e((string) $destination['title']) ?></h3><?php if (!empty($destination['label'])): ?><p class="content-label"><?= e((string) $destination['label']) ?></p><?php endif; ?></div></a></article><?php endforeach; ?></div>
    <?php if ($page['destinations'] === []): ?><p class="empty-state">Destination content is unavailable. The original catalogue has not been altered.</p><?php endif; ?>
</section>

<?php if ($page['experiences'] !== []): $firstExperience = $page['experiences'][0]; ?>
<section class="experience-section" id="experiences" aria-labelledby="experiences-title">
    <div class="experience-section__image"><?php image((string) $firstExperience['image'], (string) $firstExperience['image_alt'], '', '(max-width: 800px) 100vw, 52vw'); ?><p><?= icon('pin') ?> <span data-experience-caption><?= e((string) $firstExperience['title']) ?></span></p></div>
    <div class="experience-section__content"><p class="eyebrow">The moments in between</p><h2 id="experiences-title">Leave a little<br>room for<br><span>the unexpected.</span></h2><p class="experience-intro">The places are only the beginning. Make time for the experiences that bring the island into focus.</p>
        <div class="experience-list"><?php foreach (array_slice($page['experiences'], 0, 3) as $i => $experience): ?><details class="experience-item" name="experience" data-experience-image="<?= e(media((string) $experience['image'])) ?>" data-experience-alt="<?= e((string) $experience['image_alt']) ?>" data-experience-title="<?= e((string) $experience['title']) ?>"<?= $i === 0 ? ' open' : '' ?>><summary><?= e((string) $experience['title']) ?><span aria-hidden="true"><?= icon('arrow-up-right') ?></span></summary><div><p><?= e((string) $experience['summary']) ?></p><a href="<?= e(url((string) $experience['url'])) ?>">Explore this experience <?= icon('arrow-right') ?></a><p class="content-label"><?= e((string) $experience['label']) ?></p></div></details><?php endforeach; ?></div>
        <a class="text-link text-link--light" href="<?= e(url('/experiences')) ?>">Find your kind of experience <?= icon('arrow-up-right') ?></a>
    </div>
</section>
<?php endif; ?>

<section class="planning-section container section-space" aria-labelledby="planning-title">
    <div class="section-heading"><div><p class="eyebrow">A journey shaped around you</p><h2 id="planning-title">Your ideas.<br>Our island perspective.</h2></div><p class="section-heading__aside">Private chauffeur travel connects the places.<br>Your interests give the journey its shape.</p></div>
    <ol class="planning-steps"><li><span class="step-number">01</span><h3>Share what draws you here.</h3><p>A place you have dreamed about. A slower pace. A few experiences you do not want to miss.</p></li><li><span class="step-number">02</span><h3>Bring the route together.</h3><p>Explore how your destinations and interests fit into a personalised Sri Lanka itinerary.</p></li><li><span class="step-number">03</span><h3>Make the details your own.</h3><p>Use the planning conversation to refine the journey before any separate booking confirmation.</p></li></ol>
    <a class="text-link" href="<?= e(url('/contact')) ?>">Start with a conversation <?= icon('arrow-up-right') ?></a>
</section>

<?php if ($page['articles'] !== []): ?>
<section class="journal-section section-space" aria-labelledby="journal-title"><div class="container"><div class="section-heading"><div><p class="eyebrow">Notes from the island</p><h2 id="journal-title">A little insight.<br>A little wanderlust.</h2></div><a class="text-link" href="<?= e(url('/blog')) ?>">Open the travel journal <?= icon('arrow-up-right') ?></a></div><div class="journal-grid<?= count($page['articles']) === 2 ? ' journal-grid--pair' : '' ?>">
    <?php foreach (array_slice($page['articles'], 0, 3) as $i => $article): ?><article class="journal-card<?= $i === 0 ? ' journal-card--featured' : '' ?>"><a href="<?= e(url((string) $article['url'])) ?>"><div class="journal-card__image"><?php image((string) $article['image'], (string) $article['image_alt'], '', '(max-width: 640px) 90vw, 50vw'); ?></div><div class="journal-card__copy"><p class="eyebrow">Travel journal<?php if (!empty($article['date'])): ?> &middot; <?= e((string) $article['date']) ?><?php endif; ?></p><h3><?= e((string) $article['title']) ?></h3><p><?= e((string) $article['summary']) ?></p><span class="text-link">Read the story <?= icon('arrow-up-right') ?></span><p class="content-label"><?= e((string) $article['label']) ?></p></div></a></article><?php endforeach; ?>
</div></div></section>
<?php endif; ?>

<section class="final-cta" aria-labelledby="final-title"><div class="container final-cta__inner"><div><p class="eyebrow">The next chapter starts with you</p><h2 id="final-title">Let's make it<br><span>your Sri Lanka.</span></h2></div><div><p>Bring your ideas, your questions,<br>and your sense of possibility.</p><a class="button button--sun" href="<?= e(url('/contact')) ?>">Plan your trip <?= icon('arrow-up-right') ?></a><p class="final-cta__note">Preview only. No inquiry will be sent.</p></div></div></section>
