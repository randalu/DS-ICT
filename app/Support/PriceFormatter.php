<?php
declare(strict_types=1);

namespace ICT\Support;

/**
 * Formats the "from" pricing rows used by itineraries, tours and day tours.
 *
 * Records store price_from / price_to / price_currency / price_unit. A missing
 * price is a content gap, not a zero-price offer, so it returns an empty string
 * and callers hide the price block entirely.
 */
final class PriceFormatter
{
    public static function from(mixed $amount, string $currency = 'USD'): string
    {
        $value = self::toFloat($amount);

        if ($value === null || $value <= 0) {
            return '';
        }

        return self::format($value, $currency);
    }

    /**
     * @param array<string,mixed> $row expects price_from, price_currency, price_unit
     */
    public static function forRow(array $row): string
    {
        return self::from(
            $row['price_from'] ?? null,
            (string) ($row['price_currency'] ?? 'USD'),
        );
    }

    public static function format(float $amount, string $currency = 'USD'): string
    {
        $symbol = match (strtoupper($currency)) {
            'USD', '' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'LKR' => 'Rs ',
            default => strtoupper($currency) . ' ',
        };

        $decimals = fmod($amount, 1.0) === 0.0 ? 0 : 2;

        return $symbol . number_format($amount, $decimals, '.', ',');
    }

    /** Human label for a duration in hours, e.g. "6 hours" / "1 day". */
    public static function duration(mixed $hours): string
    {
        $value = self::toFloat($hours);

        if ($value === null || $value <= 0) {
            return '';
        }

        if ($value >= 24 && fmod($value, 24.0) === 0.0) {
            $days = (int) ($value / 24);

            return $days === 1 ? '1 day' : $days . ' days';
        }

        if ($value < 1) {
            return max(1, (int) round($value * 60)) . ' minutes';
        }

        $rounded = fmod($value, 1.0) === 0.0 ? (string) (int) $value : number_format($value, 1);

        return $rounded . ' ' . ((float) $value === 1.0 ? 'hour' : 'hours');
    }

    private static function toFloat(mixed $amount): ?float
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        if (is_string($amount)) {
            $amount = str_replace([',', ' '], '', $amount);
        }

        return is_numeric($amount) ? (float) $amount : null;
    }
}
