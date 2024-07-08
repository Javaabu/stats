<?php

namespace Javaabu\Stats\Enums;

use Illuminate\Support\Str;
use Javaabu\Helpers\Enums\IsEnum;
use Javaabu\Helpers\Enums\NativeEnumsTrait;

enum Modes: string implements IsEnum
{
    use NativeEnumsTrait;

    case HOUR = 'hour';
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case YEAR = 'year';

    public function methodName(): string
    {
        return Str::camel($this->value);
    }

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

}
