<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\Stats\Concerns\HasFilters;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Javaabu\Stats\Concerns\HasDateRange;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Formatters\StatsFormatter;
use Javaabu\Stats\TimeSeriesStats;

abstract class AbstractTimeSeriesStatsRepository implements TimeSeriesStatsRepository
{
    use HasDateRange;
    use HasFilters;

    protected string $aggregate_field;

    /**
     * Create a new stats repository instance.
     */
    public function __construct(DateRange $date_range = PresetDateRanges::THIS_YEAR, array $filters = [])
    {
        $this->setDateRange($date_range);
        $this->setFilters($filters);
    }

    /**
     * Check whether the given user can view the stat
     */
    public function canView(?Authorizable $user = null): bool
    {
        return $user && $user->can('view_stats');
    }

    /**
     * Get the stats
     */
    public function results(TimeSeriesModes $mode): Collection
    {
        $mode_method = $mode->queryMethodName();
        return $this->{$mode_method}()->get();
    }

    /**
     * Get the aggregate field name
     *
     * @return string
     */
    public function getAggregateFieldName(): string
    {
        return $this->aggregate_field;
    }

    /**
     * Get the metric
     */
    public function metric(): string
    {
        return TimeSeriesStats::getMetricForStat(get_class($this));
    }

    /**
     * Get the name of the metric
     */
    public function getName(): string
    {
        return __(Str::of(class_basename($this))
                ->snake(' ')
                ->title()
                ->toString());
    }

    /**
     * Get the result formatted
     */
    public function format(string $format, TimeSeriesModes $mode = TimeSeriesModes::DAY): array
    {
        return TimeSeriesStats::createFromFormat($format)->format($mode, $this);
    }
}
