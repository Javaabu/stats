<?php

namespace Javaabu\Stats\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
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
     * Check whether the given user can view the stat
     */
    public function canView(?Authorizable $user = null): bool;

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
     * Get the year query
     */
    public function year(): Builder;

    /**
     * Get the total
     */
    public function total(): int|float;
}
