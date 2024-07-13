<?php

namespace Javaabu\Stats\Contracts;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Enums\TimeSeriesModes;

interface InteractsWithDateRange
{
    /**
     * Get the date from
     */
    public function getDateFrom(): Carbon;

    /**
     * Get the date to
     */
    public function getDateTo(): Carbon;

    /**
     * Get the date range
     */
    public function getDateRange(): DateRange;


    /**
     * Get the formatted date from to
     */
    public function formattedDateRange(string $format = 'Y-m-d H:i', string $separator = ' - '): string;

    /**
     * Set the date from
     */
    public function setDateFrom(string|Carbon $date_from);

    /**
     * Set the date to
     */
    public function setDateTo(string|Carbon $date_to);

    /**
     * Get the date field for the repository
     */
    public function getDateField(): string;

    /**
     * Get the max date for the query
     */
    public function getMaxDate(?Carbon $fallback = null): ?Carbon;

    /**
     * Get the min date for the query
     */
    public function getMinDate(?Carbon $fallback = null): ?Carbon;

    /**
     * Set the date range
     */
    public function setDateRange(DateRange $date_range);

    /**
     * Get the interval length for the given mode
     */
    public function interval(TimeSeriesModes $mode): int;

    /**
     * Apply the date filters
     */
    public function applyDateFilters(Builder $query): Builder;


    /**
     * Get the filtered query without date filters
     */
    public function filteredQueryWithoutDateFilters(): Builder;
}
