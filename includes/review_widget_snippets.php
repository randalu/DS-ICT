<?php
declare(strict_types=1);

/**
 * Preserved third-party review widget snippets.
 *
 * Raw source is retained for inspection. Public helpers gate and keep it inert;
 * private preview pages show source without automatically contacting vendors.
 */
function review_widget_preserved_snippets(): array
{
    return [
        'trustindex' => [
            [
                'label' => 'TrustIndex.io Widget',
                'notes' => 'Previously tested on homepage and reviews page. Kept for future use, not public now.',
                'html' => <<<'HTML'
<script defer async src='https://cdn.trustindex.io/loader.js?94d2ecc70864862c0a76e91fb11'></script>
HTML,
            ],
        ],
        'trustpilot' => [
            [
                'label' => 'Trustpilot Head Loader',
                'notes' => 'Use only on pages that render a Trustpilot TrustBox.',
                'html' => <<<'HTML'
<!-- TrustBox script -->
<script type="text/javascript" src="//widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js" async></script>
<!-- End TrustBox script -->
HTML,
            ],
            [
                'label' => 'Trustpilot Review Collector',
                'notes' => 'Review collection widget. Best suited near the bottom of /reviews or post-tour thank-you flows.',
                'html' => <<<'HTML'
<!-- TrustBox widget - Review Collector -->
<div class="trustpilot-widget" data-locale="en-US" data-template-id="56278e9abfbbba0bdcd568bc" data-businessunit-id="69f0e87e25fe3531660ce70c" data-style-height="52px" data-style-width="100%" data-token="c8af15d7-eb86-40b3-8b79-ecfbe5beb6eb">
<a href="https://www.trustpilot.com/review/islandculturetravels.com" target="_blank" rel="noopener">Trustpilot</a>
</div>
<!-- End TrustBox widget -->
HTML,
            ],
        ],
    ];
}

function tripadvisor_widget_preview_snippets(): array
{
    return [
        [
            'label' => 'Write Review Small',
            'recommendation' => 'Possible post-tour or reviews page prompt. Not ideal as a homepage trust badge.',
            'html' => <<<'HTML'
<div id="TA_cdswritereviewnew667" class="TA_cdswritereviewnew"><ul id="Ci68sztbWL" class="TA_links 2auQPzP7E"><li id="LSkq2HlWS3j" class="OEnc7SEZXt"><a target="_blank" href="https://www.tripadvisor.com/"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=cdswritereviewnew&amp;uniq=667&amp;locationId=27525299&amp;lang=en_US&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Write Review Large',
            'recommendation' => 'Use only where asking past guests to review is the goal.',
            'html' => <<<'HTML'
<div id="TA_cdswritereviewlg813" class="TA_cdswritereviewlg"><ul id="iAR6xrI" class="TA_links C4CWTcvVcX"><li id="0rL3UdwvX" class="YmZpyrtUEjt"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=cdswritereviewlg&amp;uniq=813&amp;locationId=27525299&amp;lang=en_US&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Social Button / Small Icon',
            'recommendation' => 'Best candidate for a small trust icon in footer, hero trust strip, or review source row.',
            'html' => <<<'HTML'
<div id="TA_socialButtonBubbles507" class="TA_socialButtonBubbles"><ul id="Tfgwy9R5sve" class="TA_links DLDC97O3A7"><li id="fhVtAo9zBLiN" class="JJq8IfxN6k"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_logomark.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=socialButtonBubbles&amp;uniq=507&amp;locationId=27525299&amp;color=green&amp;size=rect&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Self Serve Wide',
            'recommendation' => 'Strong review-page candidate if visual size and load speed are acceptable.',
            'html' => <<<'HTML'
<div id="TA_selfserveprop305" class="TA_selfserveprop"><ul id="W01wsPBzAFL" class="TA_links HelsNgJEe"><li id="tHjrgh" class="SzCqMEyw"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://www.tripadvisor.com/img/cdsi/img2/branding/v2/Tripadvisor_lockup_horizontal_secondary_registered-11900-2.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=selfserveprop&amp;uniq=305&amp;locationId=27525299&amp;lang=en_US&amp;rating=true&amp;nreviews=4&amp;writereviewlink=true&amp;popIdx=true&amp;iswide=true&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Self Serve Narrow',
            'recommendation' => 'Good sidebar/mobile candidate if it renders cleanly.',
            'html' => <<<'HTML'
<div id="TA_selfserveprop322" class="TA_selfserveprop"><ul id="aJuEi223jm" class="TA_links uMauRJ"><li id="uGhMUHJk" class="CEpSSdq"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://www.tripadvisor.com/img/cdsi/img2/branding/v2/Tripadvisor_lockup_horizontal_secondary_registered-11900-2.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=selfserveprop&amp;uniq=322&amp;locationId=27525299&amp;lang=en_US&amp;rating=true&amp;nreviews=5&amp;writereviewlink=true&amp;popIdx=true&amp;iswide=false&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Linking Widget',
            'recommendation' => 'Simple profile link widget. Lower impact than rating/review widgets.',
            'html' => <<<'HTML'
<div id="TA_linkingWidgetRedesign935" class="TA_linkingWidgetRedesign"><ul id="JiOBEU" class="TA_links BxoUlrYP9"><li id="TufB0p0o2" class="4r5pZIfoEXwp"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_horizontal_secondary_registered.svg" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=linkingWidgetRedesign&amp;uniq=935&amp;locationId=27525299&amp;lang=en_US&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Scrolling Reviews Wide',
            'recommendation' => 'Potential reviews-page feature only. Avoid homepage until performance is checked.',
            'html' => <<<'HTML'
<div id="TA_cdsscrollingravewide730" class="TA_cdsscrollingravewide"><ul id="sDWQzICXIeA" class="TA_links Eeo9KkYlPq"><li id="BLpCAW2NjlYt" class="HkTzmf9mofNJ"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_vertical.svg" alt="TripAdvisor" class="widEXCIMG" id="CDSWIDEXCLOGO"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=cdsscrollingravewide&amp;uniq=730&amp;locationId=27525299&amp;lang=en_US&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Scrolling Reviews Narrow',
            'recommendation' => 'Potential narrow/sidebar review widget. Check mobile rendering before public use.',
            'html' => <<<'HTML'
<div id="TA_cdsscrollingravenarrow388" class="TA_cdsscrollingravenarrow"><ul id="utAbq78hf3e" class="TA_links 1AFkwC"><li id="OWbzrwPlnk1N" class="qC3fkafvWAWK"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://static.tacdn.com/img2/brand_refresh/Tripadvisor_lockup_vertical.svg" alt="TripAdvisor" class="widEXCIMG" id="CDSWIDEXCLOGO"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=cdsscrollingravenarrow&amp;uniq=388&amp;locationId=27525299&amp;lang=en_US&amp;border=true&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
        [
            'label' => 'Rated Badge',
            'recommendation' => 'Good small trust badge candidate if the badge design fits the site.',
            'html' => <<<'HTML'
<div id="TA_rated772" class="TA_rated"><ul id="rAtsVV" class="TA_links gu9QDgR"><li id="xIugfyl" class="6RqB7BGWUL"><a target="_blank" href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html"><img src="https://www.tripadvisor.com/img/cdsi/img2/badges/ollie-11424-2.gif" alt="TripAdvisor"/></a></li></ul></div><script async src="https://www.jscache.com/wejs?wtype=rated&amp;uniq=772&amp;locationId=27525299&amp;lang=en_US&amp;display_version=2" data-loadtrk onload="this.loadtrk=true"></script>
HTML,
        ],
    ];
}

function tripadvisor_widget_snippet(string $label): string
{
    foreach (tripadvisor_widget_preview_snippets() as $widget) {
        if (($widget['label'] ?? '') === $label) {
            return (string) ($widget['html'] ?? '');
        }
    }

    return '';
}

function tripadvisor_footer_trust_widget(): string
{
    $fallback = '<a href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html" target="_blank" rel="noopener">Read reviews on TripAdvisor</a>';
    return $fallback . (ict_optional_allowed('external_reviews') ? ict_privacy_optional_template('external_reviews', 'tripadvisor-footer', tripadvisor_widget_snippet('Social Button / Small Icon')) : '');
}

function tripadvisor_reviews_panel_widget(): string
{
    $fallback = '<a href="https://www.tripadvisor.com/Attraction_Review-g293962-d27525299-Reviews-Island_culture_travels_and_tours-Colombo_Western_Province.html" target="_blank" rel="noopener">Read traveler feedback on TripAdvisor</a>';
    if (!ict_optional_allowed('external_reviews') && ict_privacy_available_categories()['external_reviews']) {
        $fallback .= ' <a href="' . e(ict_privacy_url('privacy_choices.php')) . '" data-analytics-open data-no-track="1">Review widget choices</a>';
    }
    return $fallback . (ict_optional_allowed('external_reviews') ? ict_privacy_optional_template('external_reviews', 'tripadvisor-panel', tripadvisor_widget_snippet('Self Serve Narrow')) : '');
}
