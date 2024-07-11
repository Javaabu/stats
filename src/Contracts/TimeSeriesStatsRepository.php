<?php

namespace Javaabu\Stats\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Javaabu\Stats\Enums\TimeSeriesModes;

interface TimeSeriesStatsRepository extends
    InteractsWithFilters,
    InteractsWithDateRange
{
    /**
     * Get the stats
     */
    public function results(TimeSeriesModes $mode): Collection;

    /**
     * Get the aggregate field name
     */
    public function getAggregateFieldName(): string;

    /**
     * Get the name of the metric
     */
    public function getName(): string;

    /**
     * Get the metric
     */
    public function metric(): string;

    /**
     * Get the result formatted
     */
    public function format(string $format, TimeSeriesModes $mode = TimeSeriesModes::DAY): array;

    /**
     * Get the base query
     */
    public function query(): Builder;

    /**
     * Get the hourly query
     */
    public function hour();

    /**
     * Get the day query
     */
    public function day(): Builder;

    /**
     * Get the week query
     */
    public function week(): Builder;

    /**
     * Get the month query
     */
    public function month(): Builder;

    /**
     * Get the total
     */
    public function total();
}
