<?php

namespace Javaabu\Stats\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\Concerns\HasLabels;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;

/**
 * Date ranges relative to now, which also give the range before them for comparisons
 */
enum PresetDateRanges: string implements DateRange
{
    use HasLabels;

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
    case LAST_5_YEARS = 'last_5_years';
    case LAST_10_YEARS = 'last_10_years';
    case LIFETIME = 'lifetime';

    public function getDateFrom(): CarbonInterface
    {
        return match ($this) {
            self::TODAY => Date::today(),
            self::YESTERDAY => Date::yesterday(),
            self::THIS_WEEK => now()->locale(TimeSeriesStats::dateLocale())->startOfWeek(),
            self::LAST_WEEK => now()->locale(TimeSeriesStats::dateLocale())->subWeek()->startOfWeek(),
            self::THIS_MONTH => now()->startOfMonth(),
            self::LAST_MONTH => now()->subMonth()->startOfMonth(),
            self::THIS_YEAR => now()->startOfYear(),
            self::LAST_YEAR => now()->subYear()->startOfYear(),
            self::LAST_7_DAYS => now()->subDays(7 - 1)->startOfDay(),
            self::LAST_14_DAYS => now()->subDays(14 - 1)->startOfDay(),
            self::LAST_30_DAYS => now()->subDays(30 - 1)->startOfDay(),
            self::LAST_5_YEARS => now()->subYears(5 - 1)->startOfYear(),
            self::LAST_10_YEARS => now()->subYears(10 - 1)->startOfYear(),
            self::LIFETIME => now()->subYears(5 - 1)->startOfYear(),
        };
    }

    public function getDateTo(): CarbonInterface
    {
        return match ($this) {
            self::TODAY => Date::today()->endOfDay(),
            self::YESTERDAY => Date::yesterday()->endOfDay(),
            self::THIS_WEEK => now()->locale(TimeSeriesStats::dateLocale())->endOfWeek(),
            self::LAST_WEEK => now()->locale(TimeSeriesStats::dateLocale())->subWeek()->endOfWeek(),
            self::THIS_MONTH => now()->endOfMonth(),
            self::LAST_MONTH => now()->subMonth()->endOfMonth(),
            self::THIS_YEAR => now()->endOfYear(),
            self::LAST_YEAR => now()->subYear()->endOfYear(),
            self::LAST_7_DAYS, self::LAST_14_DAYS, self::LAST_30_DAYS => now()->endOfDay(),
            self::LAST_5_YEARS => now()->endOfYear(),
            self::LAST_10_YEARS => now()->endOfYear(),
            self::LIFETIME => now(),
        };
    }

    public function getName(): string
    {
        return $this->value;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::TODAY => __('Today'),
            self::YESTERDAY => __('Yesterday'),
            self::THIS_WEEK => __('This week'),
            self::LAST_WEEK => __('Last week'),
            self::THIS_MONTH => __('This month'),
            self::LAST_MONTH => __('Last month'),
            self::THIS_YEAR => __('This year'),
            self::LAST_YEAR => __('Last year'),
            self::LAST_7_DAYS => __('Last 7 days'),
            self::LAST_14_DAYS => __('Last 14 days'),
            self::LAST_30_DAYS => __('Last 30 days'),
            self::LAST_5_YEARS => __('Last 5 years'),
            self::LAST_10_YEARS => __('Last 10 years'),
            self::LIFETIME => __('Lifetime'),
        };
    }

    protected function generatePreviousFromDate(): CarbonInterface
    {
        $from_date = $this->getDateFrom();

        return match ($this) {
            self::TODAY => $from_date->subDay(),
            self::YESTERDAY => $from_date->subDay(),
            self::THIS_WEEK => $from_date->subWeek(),
            self::LAST_WEEK => $from_date->subWeek(),
            self::THIS_MONTH => $from_date->subMonth(),
            self::LAST_MONTH => $from_date->subMonth(),
            self::THIS_YEAR => $from_date->subYear(),
            self::LAST_YEAR => $from_date->subYear(),
            self::LAST_7_DAYS => $from_date->subDays(7),
            self::LAST_14_DAYS => $from_date->subDays(14),
            self::LAST_30_DAYS => $from_date->subDays(30),
            self::LAST_5_YEARS => $from_date->subYears(5),
            self::LAST_10_YEARS => $from_date->subYears(10),
            self::LIFETIME => $from_date->subYears(5),
        };
    }

    protected function generatePreviousToDate(CarbonInterface $from_date): CarbonInterface
    {
        $from_date = $from_date->copy();

        return match ($this) {
            self::TODAY => $from_date->endOfDay(),
            self::YESTERDAY => $from_date->endOfDay(),
            self::THIS_WEEK => $from_date->endOfWeek(),
            self::LAST_WEEK => $from_date->endOfWeek(),
            self::THIS_MONTH => $from_date->endOfMonth(),
            self::LAST_MONTH => $from_date->endOfMonth(),
            self::THIS_YEAR => $from_date->endOfYear(),
            self::LAST_YEAR => $from_date->endOfYear(),
            self::LAST_7_DAYS => $from_date->addDays(7 - 1)->endOfDay(),
            self::LAST_14_DAYS => $from_date->addDays(14 - 1)->endOfDay(),
            self::LAST_30_DAYS => $from_date->addDays(30 - 1)->endOfDay(),
            self::LAST_5_YEARS => $from_date->addYears(5 - 1)->endOfYear(),
            self::LAST_10_YEARS => $from_date->addYears(10 - 1)->endOfYear(),
            self::LIFETIME => $from_date->addYears(5 - 1)->endOfYear(),
        };
    }

    public function getPreviousDateRange(): DateRange
    {
        $previous_date_from = $this->generatePreviousFromDate();
        $previous_date_to = $this->generatePreviousToDate($previous_date_from);

        return new ExactDateRange($previous_date_from, $previous_date_to);
    }
}
