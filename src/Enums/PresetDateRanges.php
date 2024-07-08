<?php

namespace Javaabu\Stats\Enums;

use Carbon\Carbon;
use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;
use Javaabu\Stats\Contracts\DateRange;

enum PresetDateRanges: string implements IsEnum, DateRange
{
    use NativeEnumsTrait;

    case TODAY = 'today';
    case YESTERDAY = 'yesterday';
    case THIS_WEEK = 'this_week';
    case LAST_WEEK = 'last_week';
    case THIS_MONTH = 'this_month';
    case LAST_MONTH = 'last_month';
    case THIS_YEAR = 'this_year';
    case LAST_YEAR = 'last_year';
    case LAST_7_DAYS = 'last_7_days';
    case LAST_14_DAYS = 'last_14_days';
    case LAST_30_DAYS = 'last_30_days';
    case LIFETIME = 'lifetime';

    public function getDateFrom(?Carbon $min_date = null): Carbon
    {
        $start_of_week = config('stats.week_starts_on');

        return match ($this) {
            self::TODAY => Carbon::today(),
            self::YESTERDAY => Carbon::yesterday(),
            self::THIS_WEEK => now()->startOfWeek($start_of_week),
            self::LAST_WEEK => now()->subWeek()->startOfWeek($start_of_week),
            self::THIS_MONTH => now()->startOfMonth(),
            self::LAST_MONTH => now()->subMonth()->startOfMonth(),
            self::THIS_YEAR => now()->startOfYear(),
            self::LAST_YEAR => now()->subYear()->startOfYear(),
            self::LAST_7_DAYS => now()->subDays(7)->startOfDay(),
            self::LAST_14_DAYS => now()->subDays(14)->startOfDay(),
            self::LAST_30_DAYS => now()->subDays(30)->startOfDay(),
            self::LIFETIME => $min_date ? Carbon::parse($min_date) : now()->subYears(5)->startOfYear(),
        };
    }

    public function getDateTo(?Carbon $max_date = null): Carbon
    {
        $end_of_week = config('stats.week_ends_on');

        return match ($this) {
            self::TODAY => Carbon::today()->endOfDay(),
            self::YESTERDAY => Carbon::yesterday()->endOfDay(),
            self::THIS_WEEK => now()->endOfWeek($end_of_week),
            self::LAST_WEEK => now()->subWeek()->endOfWeek($end_of_week),
            self::THIS_MONTH => now()->endOfMonth(),
            self::LAST_MONTH => now()->subMonth()->endOfMonth(),
            self::THIS_YEAR => now()->endOfYear(),
            self::LAST_YEAR => now()->subYear()->endOfYear(),
            self::LAST_7_DAYS, self::LAST_14_DAYS, self::LAST_30_DAYS => now()->endOfDay(),
            self::LIFETIME => $max_date ? Carbon::parse($max_date) : now(),
        };
    }
}
