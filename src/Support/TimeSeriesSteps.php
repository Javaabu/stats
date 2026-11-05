<?php

namespace Javaabu\Stats\Support;

use Carbon\Carbon;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;

/**
 * Walks a time series stat one step (hour, day, week, …) at a time, with
 * zero for steps without data
 */
class TimeSeriesSteps
{
    /**
     * @return list<array{date: Carbon, key: string, value: int|float}>
     */
    public static function for(TimeSeriesStatsRepository $stats, TimeSeriesModes $mode): array
    {
        $values = $stats->results($mode)->pluck($stats->getAggregateFieldName(), $mode->value);
        $date = $stats->getDateFrom()->copy();
        $count = $stats->interval($mode);
        $steps = [];

        for ($i = 0; $i <= $count; $i++) {
            $key = $mode->formatDate($date, false);
            $value = $values->get($key);

            $steps[] = [
                'date' => $mode->startOfStep($date),
                'key' => $key,
                'value' => $value === null ? 0 : $value + 0,
            ];

            $date = $mode->increment($date);
        }

        return $steps;
    }

    /**
     * A short name for a date range: the preset's label, or its dates
     */
    public static function rangeLabel(DateRange $range, ?Carbon $from = null, ?Carbon $to = null): string
    {
        if ($range instanceof PresetDateRanges && $range !== PresetDateRanges::LIFETIME) {
            return $range->getLabel();
        }

        $from ??= $range->getDateFrom();
        $to ??= $range->getDateTo();
        $locale = app()->getLocale();

        return $from->copy()->locale($locale)->isoFormat('D MMM YYYY') . ' – ' . $to->copy()->locale($locale)->isoFormat('D MMM YYYY');
    }
}
