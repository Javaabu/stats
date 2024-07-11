<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Carbon\Carbon;
use Javaabu\Stats\Concerns\HasFilters;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Javaabu\Stats\Concerns\HasDateRange;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Formatters\StatsFormatter;

class AbstractTimeSeriesStatsRepository implements TimeSeriesStatsRepository
{
    use HasDateRange;
    use HasFilters;

    protected string $aggregate_field;

    /**
     * Create a new stats repository instance.
     */
    public function __construct(DateRange $date_range, array $filters = [])
    {
        $this->setDateRange($date_range);
        $this->ensureAllFiltersAllowed($filters);
    }

    /**
     * Get the metrics that allow these filters
     *
     * @param mixed $filters
     * @return array
     */
    public static function metricsThatAllowFilters($filters): array
    {
        $metrics = self::METRICS;

        $filters = Arr::wrap($filters);
        $filtered = [];

        foreach ($metrics as $slug => $data) {
            $metric = self::createFromMetric($slug);

            $allowed_filters = $metric->allowedFilters();
            $allowed = true;

            foreach ($filters as $filter) {
                if (! in_array($filter, $allowed_filters)) {
                    $allowed = false;
                    break;
                }
            }

            if ($allowed) {
                $filtered[$slug] = $data;
            }
        }

        return $filtered;
    }

    /**
     * Create from metric
     *
     * @param $metric
     * @param string $date_range
     * @param array $filters
     * @return \Javaabu\Stats\StatsRepository
     */
    public static function createFromMetric($metric, $date_range = 'lifetime', $filters = [])
    {
        $class = self::getMetricClass($metric);
        return new $class($date_range, $filters);
    }

    /**
     * Get the metric class
     *
     * @param $metric
     * @return string
     */
    public static function getMetricClass($metric)
    {
        if (!array_key_exists($metric, self::METRICS)) {
            throw new \InvalidArgumentException('Invalid metric');
        }

        return self::METRICS[$metric]['class'];
    }

    /**
     * Get the metric names
     *
     * @param null $filters
     * @return array
     */
    public static function getMetricNames($filters = [])
    {
        $metrics = [];

        $allowed_metrics = $filters ? self::metricsThatAllowFilters($filters) : self::METRICS;

        foreach ($allowed_metrics as $key => $metric) {
            $metrics[$key] = $metric['name'];
        }

        return $metrics;
    }

    /**
     * Get the metric names
     *
     * @param $metric
     * @return string
     */
    public static function getMetricName($metric)
    {
        return self::METRICS[$metric]['name'] ?? '';
    }




    /**
     * Get the number of weeks of the current week-year using given first day of week and first
     * day of year included in the first week. Or use US format if no settings
     * given (Sunday / Jan 6).
     *
     * @param Carbon $date
     * @return int
     *
     */
    public static function carbonWeeksInYear(Carbon $date)
    {
        $year = $date->year;
        $start = $date->copy()->addDays(6 - $date->dayOfYear)->startOfWeek();
        $startDay = $start->dayOfYear;

        if ($start->year !== $year) {
            $startDay -= $start->isLeapYear() ? 366 : 365;
        }

        $end = $date->copy()->addYear();
        $end = $end->addDays(6 - $end->dayOfYear)->startOfWeek();
        $endDay = $end->dayOfYear;

        if ($end->year !== $year) {
            $endDay += $date->isLeapYear() ? 366 : 365;
        }

        return (int)round(($endDay - $startDay) / 7);
    }

    /**
     * Get/set the week number using given first day of week and first
     * day of year included in the first week. Or use US format if no settings
     * given (Sunday / Jan 6).
     *
     * @param int $week_no
     * @param Carbon|null $date
     * @return Carbon
     *
     */
    public static function carbonSetWeek($week_no, Carbon $date = null)
    {
        if (!$date) {
            $date = Carbon::now();
        }

        $week = $week_no;

        $start = $date->copy()->addDays(6 - $date->dayOfYear)->startOfWeek();
        $end = $date->copy()->startOfWeek();

        if ($start > $end) {
            $start = $start->subWeeks(26);
            $start = $start->addDays(6 - $start->dayOfYear)->startOfWeek();
        }

        $week = (int)($start->diffInDays($end) / 7 + 1);
        $week = $week > self::carbonWeeksInYear($end) ? 1 : $week;

        return $date->addWeeks(round($week_no) - $week);
    }


    /**
     * Get the start date for a given mode
     *
     * @param string $date_string
     * @param $mode
     * @return Carbon
     */
    public static function getStartDateForMode($date_string, $mode)
    {
        // verify the range is valid
        if (!static::isValidMode($mode)) {
            throw new \InvalidArgumentException('Invalid mode');
        }

        $start_date = null;

        switch ($mode) {
            case 'hour':
                $start_date = Carbon::createFromFormat('Y-m-d H:i', $date_string)->startOfHour();
                break;

            case 'day':
                $start_date = Carbon::createFromFormat('Y-m-d', $date_string)->startOfDay();
                break;

            case 'week':
                list($year, $week) = explode(', ', $date_string, 2);
                $year = Carbon::createFromDate($year);
                $year = $year->addDays(6 - $year->dayOfYear);
                $start_date = self::carbonSetWeek($week, $year)->startOfWeek();
                break;

            case 'month':
                $start_date = Carbon::createFromFormat('Y, m', $date_string)->startOfMonth();
                break;

            case 'year':
                $start_date = Carbon::createFromFormat('Y', $date_string)->startOfYear();
                break;
        }

        return $start_date;
    }

    /**
     * Format the date for a given mode
     *
     * @param Carbon $start_date
     * @param $mode
     * @param bool $for_display
     * @return string
     */
    public static function formatDateForMode(Carbon $start_date, $mode, $for_display = true)
    {
        // verify the range is valid
        if (!static::isValidMode($mode)) {
            throw new \InvalidArgumentException('Invalid mode');
        }

        $formatted = null;

        switch ($mode) {
            case 'hour':
                $formatted = $for_display ? $start_date->format('j M y h:iA') : $start_date->format('Y-m-d H:i');
                break;

            case 'day':
                $formatted = $for_display ? $start_date->format('j M y') : $start_date->format('Y-m-d');
                break;

            case 'week':
                $formatted = $for_display ? $start_date->year . ' - Week ' . $start_date->weekOfYear :
                    $start_date->year . ', ' . $start_date->weekOfYear;
                break;

            case 'month':
                $formatted = $for_display ? $start_date->format('Y F') : $start_date->format('Y, m');
                break;

            case 'year':
                $formatted = $start_date->format('Y');
                break;

        }

        return $formatted;
    }

    /**
     * Get the next date for a given mode
     *
     * @param Carbon $start_date
     * @param $mode
     * @return Carbon
     */
    public static function getNextDateForMode(Carbon $start_date, $mode)
    {
        // verify the range is valid
        if (!static::isValidMode($mode)) {
            throw new \InvalidArgumentException('Invalid mode');
        }

        $next_date = null;

        switch ($mode) {
            case 'hour':
                $next_date = $start_date->copy()->addHour();
                break;

            case 'day':
                $next_date = $start_date->copy()->addDay();
                break;

            case 'week':
                $next_date = $start_date->copy()->addWeek();
                break;

            case 'month':
                $next_date = $start_date->copy()->addMonth();
                break;

        }

        return $next_date;
    }

    /**
     * Convert date range to previous range
     *
     * @param string|Carbon[] $date_range
     * @return Carbon[]
     */
    public static function getPreviousDateRange($date_range, $min_date = null)
    {
        if (!is_array($date_range)) {
            $start_date = null;

            switch ($date_range) {
                case 'today':
                    $start_date = Carbon::now()->subDay();
                    break;

                case 'yesterday':
                    $start_date = Carbon::now()->subDay();
                    break;

                case 'this_week':
                    $start_date = Carbon::now()->subWeek();
                    break;

                case 'last_week':
                    $start_date = Carbon::now()->subWeek();
                    break;

                case 'this_month':
                    $start_date = Carbon::now()->subMonth();
                    break;

                case 'last_month':
                    $start_date = Carbon::now()->subMonth();
                    break;

                case 'this_year':
                    $start_date = Carbon::now()->subYear();
                    break;

                case 'last_year':
                    $start_date = Carbon::now()->subYear();
                    break;

                case 'last_7_days':
                    $start_date = Carbon::now()->subDays(7 + 1);
                    break;

                case 'last_14_days':
                    $start_date = Carbon::now()->subDays(14 + 1);
                    break;

                case 'last_30_days':
                    $start_date = Carbon::now()->subDays(30 + 1);
                    break;

                case 'lifetime':
                    $start_date = $min_date ? Carbon::parse($min_date) : Carbon::parse('2019-11-12'); //need a stating date
                    break;
            }

            return static::dateRangeToDates($date_range, $start_date);
        }

        list($date_from, $date_to) = $date_range;
        $date_from = self::parseDate($date_from);
        $date_to = self::parseDate($date_to);

        $interval = $date_from->diff($date_to);

        $prev_date_from = $date_from->copy()->sub($interval)->subSecond();
        $prev_date_to = $prev_date_from->copy()->add($interval);

        return [$prev_date_from, $prev_date_to];
    }



    /**
     * Get the stats
     *
     * @param string $mode
     * @return Collection
     */
    public function results($mode)
    {
        // verify the range is valid
        if (!static::isValidMode($mode)) {
            throw new \InvalidArgumentException('Invalid mode');
        }

        $mode_method = Str::camel($mode);
        return $this->{$mode_method}()->get();
    }









    /**
     * Get the aggregate field name
     *
     * @return string
     */
    public function getAggregateFieldName()
    {
        return $this->aggregate_field;
    }

    /**
     * Get the metric
     *
     * @return string
     */
    public function metric()
    {
        foreach (self::METRICS as $slug => $metric) {
            if ($this instanceof $metric['class']) {
                return $slug;
            }
        }

        return null;
    }

    /**
     * Get the result formatted
     *
     * @param $format
     * @param string $mode
     * @return array
     */
    public function format($format, $mode = 'day')
    {
        return StatsFormatter::createFromFormat($format, $this)->format($mode);
    }
}
