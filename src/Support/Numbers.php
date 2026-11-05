<?php

namespace Javaabu\Stats\Support;

use Illuminate\Support\Number;
use Javaabu\Stats\Charts\Chart;
use NumberFormatter;

/**
 * Formats chart values like Onu's `valueFormat`: number, integer, compact,
 * percent (0.256 → 25.6%) or currency (MVR)
 */
class Numbers
{
    public static function format(int|float|null $value, string $format = Chart::FORMAT_NUMBER, ?string $locale = null): string
    {
        if ($value === null) {
            return __('No data');
        }

        $locale = static::numberLocale($locale);

        return match ($format) {
            Chart::FORMAT_INTEGER => static::number(round($value), 0, $locale),
            Chart::FORMAT_COMPACT => static::compact($value, $locale),
            Chart::FORMAT_PERCENT => static::number($value * 100, 1, $locale) . '%',
            Chart::FORMAT_CURRENCY => static::currency($value, $locale),
            default => static::number($value, 2, $locale),
        };
    }

    /**
     * A short label for an axis tick: 12K, 1.5M
     */
    public static function tick(int|float $value, string $format = Chart::FORMAT_NUMBER, ?string $locale = null): string
    {
        if ($format === Chart::FORMAT_PERCENT) {
            return static::format($value, $format, $locale);
        }

        $locale = static::numberLocale($locale);
        $compact = static::compact($value, $locale);

        return $format === Chart::FORMAT_CURRENCY ? static::currencySymbol() . $compact : $compact;
    }

    public static function number(int|float $value, int $max_precision, string $locale): string
    {
        if (class_exists(NumberFormatter::class)) {
            return (string) Number::format($value, maxPrecision: $max_precision, locale: $locale);
        }

        $decimals = floor($value) == $value ? 0 : $max_precision;

        return rtrim(rtrim(number_format($value, $decimals), '0'), '.') ?: '0';
    }

    public static function compact(int|float $value, string $locale): string
    {
        $abs = abs($value);

        foreach ([1e12 => 'T', 1e9 => 'B', 1e6 => 'M', 1e3 => 'K'] as $size => $suffix) {
            if ($abs >= $size) {
                return static::number($value / $size, 1, $locale) . $suffix;
            }
        }

        return static::number($value, 1, $locale);
    }

    public static function currency(int|float $value, string $locale): string
    {
        $formatted = class_exists(NumberFormatter::class)
            ? (string) Number::format($value, precision: 2, locale: $locale)
            : number_format($value, 2);

        return static::currencySymbol() . $formatted;
    }

    /**
     * MVR, or ރ. in Dhivehi
     */
    public static function currencySymbol(): string
    {
        return app()->getLocale() === 'dv' ? 'ރ. ' : 'MVR ';
    }

    /**
     * The locale for numbers: Latin digits for Dhivehi, as Onu shows them
     */
    protected static function numberLocale(?string $locale): string
    {
        $locale ??= app()->getLocale();

        return $locale === 'dv' ? 'en' : str_replace('-', '_', $locale);
    }
}
