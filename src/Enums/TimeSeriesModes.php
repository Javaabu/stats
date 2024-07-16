<?php

namespace Javaabu\Stats\Enums;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;
use Javaabu\Stats\TimeSeriesStats;

enum TimeSeriesModes: string implements IsEnum
{
    use NativeEnumsTrait;

    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public static function getLabels(): array
    {
        return [
            self::HOUR->value => __('Hourly'),
            self::DAY->value => __('Day'),
            self::WEEK->value => __('Week'),
            self::MONTH->value => __('Month'),
            self::YEAR->value => __('Year'),
        ];
    }

    public function diffMethodName(): string
    {
        return Str::camel('diff_in_' . Str::plural($this->value));
    }

    public function queryMethodName(): string
    {
        return Str::camel($this->value);
    }

    public function interval(Carbon $date_from, Carbon $date_to): int
    {
        $diff_method = $this->diffMethodName();
        //$date_to = $this == self::HOUR ? $date_to->copy()->addHour() : $date_to->copy()->addDay();

        return (int) $date_from->{$diff_method}($date_to, true);
    }

    public function getDateFormat(): string
    {
        return config('stats.date_formats.' . $this->value);
    }

    public function formatDate(Carbon $date, bool $for_display = true): string
    {
        $date = $date->copy()->locale(TimeSeriesStats::dateLocale());

        if ($for_display) {
            return $date->format($this->getDateFormat());
        }

        return match ($this) {
            self::HOUR => $date->format('Y-m-d H:i'),
            self::DAY => $date->format('Y-m-d'),
            self::WEEK => $date->format('Y, W'),
            self::MONTH => $date->format('Y, m'),
            self::YEAR => $date->format('Y'),
        };
    }

}
