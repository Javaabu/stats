<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\GeneratorHelpers\StringCaser;
use Javaabu\Stats\Concerns\HasFilters;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Javaabu\Stats\Concerns\HasDateRange;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\TimeSeriesStats;

abstract class AbstractTimeSeriesStatsRepository implements TimeSeriesStatsRepository
{
    use HasDateRange;
    use HasFilters;

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
     * Get the aggregate field label
     */
    public function getAggregateFieldLabel(): string
    {
        $aggregate_field = StringCaser::title($this->getAggregateFieldName());

        return __($aggregate_field);
    }

    /**
     * Get the metric
     */
    public function metric(): string
    {
        return TimeSeriesStats::getMetricForStat(get_class($this));
    }

    protected function generateName(): string
    {
        $class_name = StringCaser::title(class_basename($this));

        if (Str::endsWith($class_name, 'Repository')) {
            $class_name = trim(Str::beforeLast($class_name, 'Repository'));
        }

        return $class_name;
    }

    /**
     * Get the name of the metric
     */
    public function getName(): string
    {
        $class_name = $this->generateName();

        return __($class_name);
    }

    /**
     * Get the result formatted
     */
    public function format(string $format, TimeSeriesModes $mode = TimeSeriesModes::DAY): array
    {
        return TimeSeriesStats::createFromFormat($format)->format($mode, $this);
    }
}
