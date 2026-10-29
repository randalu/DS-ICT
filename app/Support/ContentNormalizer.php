<?php
declare(strict_types=1);

namespace ICT\Support;

/**
 * Maps database rows to the stable card shapes the views consume.
 *
 * Templates must not know column names: every card exposes the same keys
 * (title, url, image, alt, excerpt, meta, price, kicker) so partials stay
 * reusable across destinations, journeys, experiences and articles.
 */
final class ContentNormalizer
{
    /**
     * @param array<string,mixed> $row
     * @return array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string}
     */
    public static function destination(array $row): array
    {
        $name = self::text($row['name'] ?? '', 'Destination');
        $slug = self::text($row['slug'] ?? '');
        $media = MediaResolver::forRow($row, $name);

        $meta = [];
        if (($stay = self::text($row['stay_duration'] ?? '')) !== '') {
            $meta[] = $stay;
        }
        if (($type = self::text($row['destination_type'] ?? '')) !== '') {
            $meta[] = $type;
        }

        return [
            'title' => $name,
            'url' => $slug !== '' ? base_url('destinations/' . $slug) : base_url('destinations'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => self::excerpt($row['excerpt'] ?? $row['best_known_for'] ?? ''),
            'meta' => $meta,
            'price' => '',
            'kicker' => self::text($row['region_label'] ?? '') ?: self::text($row['province'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string}
     */
    public static function journey(array $row): array
    {
        $name = self::text($row['name'] ?? '', 'Journey');
        $slug = self::text($row['slug'] ?? '');
        $media = MediaResolver::forRow($row, $name);

        $meta = [];
        $days = (int) ($row['days'] ?? 0);
        if ($days > 0) {
            $meta[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }
        if (($type = self::text($row['traveller_types'] ?? '')) !== '') {
            $meta[] = $type;
        }

        return [
            'title' => $name,
            'url' => $slug !== '' ? base_url('itineraries/' . $slug) : base_url('itineraries'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => self::excerpt($row['excerpt'] ?? ''),
            'meta' => $meta,
            'price' => PriceFormatter::forRow($row),
            'kicker' => 'Signature journey',
            // Numeric length, used by the journey matcher island.
            'days' => $days,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string}
     */
    public static function experience(array $row): array
    {
        $title = self::text($row['title'] ?? '', 'Experience');
        $slug = self::text($row['slug'] ?? '');
        $media = MediaResolver::forRow($row, $title);

        return [
            'title' => $title,
            'url' => $slug !== '' ? base_url('experiences/' . $slug) : base_url('experiences'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => self::excerpt($row['excerpt'] ?? ''),
            'meta' => [],
            'price' => '',
            // best_time is a full sentence in this dataset, so it belongs in the
            // detail page body rather than a card kicker.
            'kicker' => 'Experience',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string}
     */
    public static function dayTour(array $row): array
    {
        $name = self::text($row['name'] ?? '', 'Day tour');
        $slug = self::text($row['slug'] ?? '');
        $media = MediaResolver::forRow($row, $name);

        $meta = [];
        if (($duration = PriceFormatter::duration($row['duration_hours'] ?? null)) !== '') {
            $meta[] = $duration;
        }
        $group = (int) ($row['max_group_size'] ?? 0);
        if ($group > 0) {
            $meta[] = 'Up to ' . $group;
        }

        return [
            'title' => $name,
            'url' => $slug !== '' ? base_url('day-tours/' . $slug) : base_url('day-tours'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => self::excerpt($row['excerpt'] ?? ''),
            'meta' => $meta,
            'price' => PriceFormatter::forRow($row),
            'kicker' => 'Day tour',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string}
     */
    public static function article(array $row): array
    {
        $title = self::text($row['title'] ?? '', 'Journal entry');
        $slug = self::text($row['slug'] ?? '');
        $media = MediaResolver::forRow($row, $title);

        $meta = [];
        $published = self::text($row['published_at'] ?? '');
        if ($published !== '') {
            $timestamp = strtotime($published);
            if ($timestamp !== false) {
                $meta[] = date('M Y', $timestamp);
            }
        }

        return [
            'title' => $title,
            'url' => $slug !== '' ? base_url('blog/' . $slug) : base_url('blog'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => self::excerpt($row['excerpt'] ?? ''),
            'meta' => $meta,
            'price' => '',
            'kicker' => 'Journal',
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @return array{name:string,country:string,title:string,text:string,rating:int,date:string,type:string,source:string}
     */
    public static function review(array $row): array
    {
        $date = self::text($row['tour_done_date'] ?? '');

        if ($date === '') {
            $month = self::text($row['tour_done_month'] ?? '');
            if ($month !== '') {
                $parsed = date_create_from_format('Y-m', $month);
                $date = $parsed !== false ? $parsed->format('F Y') : $month;
            }
        } elseif (($timestamp = strtotime($date)) !== false) {
            $date = date('F Y', $timestamp);
        }

        return [
            'name' => self::text($row['customer_name'] ?? '', 'Guest'),
            'country' => self::text($row['country'] ?? ''),
            'title' => self::text($row['review_title'] ?? ''),
            'text' => self::excerpt($row['review_text'] ?? '', 320),
            'rating' => max(1, min(5, (int) ($row['rating'] ?? 5))),
            'date' => $date,
            'type' => self::text($row['trip_type'] ?? ''),
            'source' => self::text($row['review_source'] ?? ''),
        ];
    }

    private static function text(mixed $value, string $default = ''): string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : $default;
    }

    private static function excerpt(mixed $value, int $length = 140): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');

        if ($text === '') {
            return '';
        }

        if (function_exists('mb_strimwidth')) {
            return rtrim((string) mb_strimwidth($text, 0, $length, '…'));
        }

        return strlen($text) > $length ? rtrim(substr($text, 0, $length - 3)) . '…' : $text;
    }
}
