<?php
declare(strict_types=1);

namespace NewSite;

$label = 'Design sample - not a published offer';
$card = static fn (string $id, string $title, string $summary, string $image, string $alt, string $route): array => [
    'id' => 'demo-' . $id, 'title' => $title, 'summary' => $summary,
    'image' => $image, 'image_alt' => $alt, 'url' => url($route), 'label' => $label,
];
$galle = 'assets/images/galle-lighthouse.w1200.webp';
$kandy = 'assets/images/kandy-temple-lk.w1200.webp';
$mirissa = 'assets/images/mirissa-beach-lk.w1200.webp';
$ella = 'assets/images/country-market/nine-arch-bridge.jpg';
$yala = 'assets/images/yala-national-park-leopard-safari-large.w800.webp';
$stop = static fn (string $title, string $route): array => ['title' => $title, 'url' => url($route)];

return [
    'hero' => ['image' => 'assets/images/home-hero/home-3.w1200.webp', 'alt' => 'Aerial view of a curving Sri Lankan coastline, turquoise sea and palm-covered headland', 'place' => "Sri Lanka's southern coast"],
    'destinations' => [
        $card('galle', 'Galle', 'Coastal streets, a lighthouse and the edge of the Indian Ocean.', $galle, 'Galle lighthouse beside the coast in Sri Lanka', '/destinations/galle'),
        $card('ella', 'Ella hills', 'Green hills and the distinctive arches of a railway bridge.', $ella, 'Nine Arch Bridge in the green hills near Ella', '/destinations/ella'),
        $card('kandy', 'Kandy', "A different perspective on Sri Lanka's central highlands.", $kandy, 'Temple architecture in Kandy, Sri Lanka', '/destinations/kandy'),
        $card('mirissa', 'Mirissa', 'A southern coastline made for a quieter chapter.', $mirissa, 'Beach and coastline at Mirissa, Sri Lanka', '/destinations/mirissa'),
    ],
    'journeys' => [
        $card('hills-coast', 'From green hills to the coast', 'An illustrative composition connecting Kandy, Ella and Mirissa. Not an itinerary or travel offer.', $ella, 'Nine Arch Bridge and surrounding hills near Ella', '/itineraries/demo-hills-coast') + [
            'route_label' => 'Illustrative stops - not a published itinerary',
            'route' => [$stop('Kandy', '/destinations/kandy'), $stop('Ella', '/destinations/ella'), $stop('Mirissa', '/destinations/mirissa')],
        ],
        $card('southern-perspectives', 'Southern perspectives', 'A design sample pairing Galle, Mirissa and Yala; no actual programme or availability is implied.', $galle, "Galle lighthouse on Sri Lanka's southern coast", '/itineraries/demo-southern-perspectives') + [
            'route_label' => 'Illustrative stops - not a published itinerary',
            'route' => [$stop('Galle', '/destinations/galle'), $stop('Mirissa', '/destinations/mirissa'), $stop('Yala', '/destinations/yala')],
        ],
        $card('island-contrasts', 'An island of contrasts', 'Illustrative places for exploring the homepage design, not an ordered travel recommendation.', $kandy, 'Temple in Kandy, Sri Lanka', '/itineraries/demo-island-contrasts') + [
            'route_label' => 'Illustrative stops - not a published itinerary',
            'route' => [$stop('Kandy', '/destinations/kandy'), $stop('Ella', '/destinations/ella'), $stop('Galle', '/destinations/galle')],
        ],
    ],
    'experiences' => [
        $card('coastal-streets', 'Coastal perspectives', 'An illustrative coastal discovery card; no guided activity is offered here.', $galle, 'Lighthouse and coastal setting in Galle', '/experiences/demo-coastal-streets'),
        $card('hill-country', 'Into the hill country', 'An illustrative highland discovery card, without an included activity or booking promise.', $ella, "Nine Arch Bridge in Sri Lanka's hill country", '/experiences/demo-hill-country'),
        $card('wildlife-landscapes', 'Wild landscapes', 'A wildlife-themed design sample. Sightings, access and availability are not promised.', $yala, 'Wildlife photography from Yala National Park, Sri Lanka', '/experiences/demo-wildlife-landscapes'),
    ],
    'articles' => [
        $card('journal-coast', 'A coastal chapter', 'Journal layout preview only. Sample educational content, not a published guide.', $mirissa, 'Mirissa coastline in Sri Lanka', '/blog/demo-coastal-chapter'),
        $card('journal-hills', 'A change of scenery', 'An illustrative journal card, not a published travel guide or seasonal recommendation.', $ella, 'Nine Arch Bridge among green hills near Ella', '/blog/demo-change-of-scenery'),
    ],
];
