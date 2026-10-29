<?php
declare(strict_types=1);

namespace NewSite\Support;

use function NewSite\e;
use function NewSite\icon;
use function NewSite\url;

function heading(string $eyebrow, string $title, string $description): void
{
    ?>
    <div class="support-heading"><p class="eyebrow"><?= e($eyebrow) ?></p><h1><?= e(str_replace('\\n', "\n", $title)) ?></h1><p class="support-lead"><?= e($description) ?></p></div>
    <?php
}

function demo_form(string $kind = 'inquiry'): void
{
    $message = match ($kind) {
        'message' => 'Your sample reply is prepared on this screen only. It has not been saved or sent.',
        'request' => 'Your sample change request is prepared on this screen only. No itinerary or booking has changed.',
        'partner' => 'Your sample application is prepared on this screen only. No business record or upload has been created.',
        default => 'Your sample inquiry is prepared on this screen only. Nothing has been saved or sent, and no booking is confirmed.',
    };
    ?>
    <form class="demo-form" method="post" action="<?= e(url('/contact')) ?>" data-demo-form data-demo-message="<?= e($message) ?>" autocomplete="off">
        <p class="demo-form__notice"><?= icon('shield') ?><span><strong>Preview only.</strong> Use sample details. Nothing is saved or sent.</span></p>
        <fieldset disabled data-demo-fields>
            <?php if ($kind === 'inquiry' || $kind === 'partner'): ?>
                <div class="form-grid"><div><label for="demo-name"><?= $kind === 'partner' ? 'Representative name' : 'Your name' ?></label><input id="demo-name" name="demo_name" value="Alex Traveller" maxlength="100" autocomplete="off" required></div><div><label for="demo-email">Email address</label><input id="demo-email" name="demo_email" type="email" value="traveller@example.test" maxlength="160" autocomplete="off" required></div></div>
            <?php endif; ?>
            <?php if ($kind === 'inquiry'): ?>
                <div class="form-grid"><div><label for="demo-duration">Time to explore</label><select name="demo_duration" id="demo-duration"><option>Still deciding</option><option>About a week</option><option>About two weeks</option><option>A little longer</option></select></div><div><label for="demo-party">Who is travelling?</label><select id="demo-party" name="demo_party"><option>Two travellers</option><option>Solo traveller</option><option>Family</option><option>Friends or a small group</option></select></div></div>
                <div><label for="demo-interests">What draws you to Sri Lanka?</label><textarea name="demo_message" id="demo-interests" rows="5" maxlength="2500" placeholder="For example: cultural places, time in the hills, and a few unhurried days by the coast." required></textarea></div>
            <?php elseif ($kind === 'partner'): ?>
                <div><label for="demo-business">Business name</label><input id="demo-business" name="demo_business" value="Example Travel Partner" maxlength="160" required></div><div><label for="demo-interests">Tell us about your business</label><textarea id="demo-interests" name="demo_message" rows="5" maxlength="2500" required placeholder="Sample description only. Do not add private business information."></textarea></div><p class="field-help">Photo uploads are not enabled in this preview.</p>
            <?php else: ?>
                <div><label for="demo-interests"><?= $kind === 'request' ? 'What would you like to adjust?' : 'Your message' ?></label><textarea id="demo-interests" name="demo_message" rows="5" maxlength="2500" required placeholder="Use an illustrative message, not personal or booking information."></textarea></div>
            <?php endif; ?>
            <button class="button button--dark" type="submit"><?= $kind === 'inquiry' ? 'Preview your inquiry' : ($kind === 'partner' ? 'Preview your application' : 'Preview your message') ?> <?= icon('arrow-right') ?></button>
        </fieldset>
        <noscript><p class="form-status">Demo inputs are disabled without JavaScript. No fallback submission or external request is made.</p></noscript>
        <p class="form-status" role="status" data-demo-status hidden></p>
    </form>
    <?php
}

function notice(string $title, string $text): void
{
    ?><div class="support-notice"><?= icon('shield') ?><div><h2><?= e($title) ?></h2><p><?= e($text) ?></p></div></div><?php
}
