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

    public function incrementMethodName(): string
    {
        return Str::camel('add_' . $this->value);
    }

    public function queryMethodName(): string
    {
        return Str::camel($this->value);
    }

    public function interval(Carbon $date_from, Carbon $date_to): int
    {
        $diff_method = $this->diffMethodName();

        return (int) $date_from->{$diff_method}($date_to, true);
    }

    public function getDateFormat(): string
    {
        return config('stats.date_formats.' . $this->value);
    }

    public function getInternalDateFormat(): string
    {
        return match ($this) {
            self::HOUR => 'YYYY-MM-DD HH:mm',
            self::DAY => 'YYYY-MM-DD',
            self::WEEK => 'ggggww',
            self::MONTH => 'YYYY, MM',
            self::YEAR => 'YYYY',
        };
    }

    public function increment(Carbon $date): Carbon
    {
        $date = $date->copy()->locale(TimeSeriesStats::dateLocale());

        $method = $this->incrementMethodName();

        return $date->{$method}();
    }

    public function formatDate(Carbon $date, bool $for_display = true): string
    {
        $date = $date->copy()->locale(TimeSeriesStats::dateLocale());

        if ($for_display) {
            return $date->isoFormat($this->getDateFormat());
        }

        return $date->isoFormat($this->getInternalDateFormat());
    }

}
