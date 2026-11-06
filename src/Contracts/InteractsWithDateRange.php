<?php

namespace Javaabu\Stats\Contracts;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Enums\TimeSeriesModes;

interface InteractsWithDateRange
{
    /**
     * Get the date from
     */
    public function getDateFrom(): CarbonInterface;

    /**
     * Get the date to
     */
    public function getDateTo(): CarbonInterface;

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
    public function setDateFrom(string|DateTimeInterface $date_from);

    /**
     * Set the date to
     */
    public function setDateTo(string|DateTimeInterface $date_to);

    /**
     * Get the date field for the repository
     */
    public function getDateField(): string;

    /**
     * Get the max date for the query
     */
    public function getMaxDate(?CarbonInterface $fallback = null): ?CarbonInterface;

    /**
     * Get the min date for the query
     */
    public function getMinDate(?CarbonInterface $fallback = null): ?CarbonInterface;

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
