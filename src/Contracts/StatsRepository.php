<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats\Contracts;

use Carbon\Carbon;
use \InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Javaabu\Stats\Enums\Modes;

interface StatsRepository
{
    /**
     * Get the stats
     */
    public function results(Modes $mode): Collection;

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
     *
     * @return string
     */
    public function getDateRange()
    {
        return $this->date_range;
    }

    /**
     * Get the filters
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * Get a specific filter
     *
     * @param $filter
     * @param null $default
     * @return mixed
     */
    public function getFilter($filter, $default = null)
    {
        return $this->filters[$filter] ?? $default;
    }

    /**
     * Get the formatted date from to
     *
     * @param string $format
     * @param string $separator
     * @return string
     */
    public function formattedDateRange($format = 'Y-m-d H:i', $separator = ' - ')
    {
        return $this->getDateFrom()->format($format) . $separator . $this->getDateTo()->format($format);
    }

    /**
     * Parse the date
     *
     * @param string|Carbon $date
     * @return Carbon
     */
    public static function parseDate($date)
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return $date;
    }

    /**
     * Set the date from
     *
     * @param string|Carbon $date_from
     */
    public function setDateFrom($date_from)
    {
        $this->date_from = self::parseDate($date_from);
    }

    /**
     * Set the date to
     *
     * @param string|Carbon $date_to
     */
    public function setDateTo($date_to)
    {
        $this->date_to = self::parseDate($date_to);
    }

    /**
     * Get the date field for the repository
     *
     * @return string
     */
    public function getDateField()
    {
        return 'created_at';
    }

    /**
     * Get the max date for the query
     */
    public function getMaxDate()
    {
        return $this->query()->max($this->getDateField());
    }

    /**
     * Get the min date for the query
     */
    public function getMinDate()
    {
        return $this->query()->min($this->getDateField());
    }

    /**
     * Set the date range
     *
     * @param string|Carbon[] $date_range
     */
    public function setDateRange($date_range)
    {
        if (!is_array($date_range)) {
            $this->date_range = $date_range;
            $date_range = static::dateRangeToDates($date_range, null, $this->getMinDate(), $this->getMaxDate());
        } else {
            $this->date_range = 'custom';
        }

        list($date_from, $date_to) = $date_range;

        $this->setDateFrom($date_from);
        $this->setDateTo($date_to);
    }

    /**
     * Get the interval length for the given mode
     *
     * @param $mode
     * @return int
     */
    public function interval($mode)
    {
        $diff_method = Str::camel('diff_in_' . Str::plural($mode));
        $date_to = $mode == 'hour' ? $this->getDateTo()->copy()->addHour() : $this->getDateTo()->copy()->addDay();

        return $this->getDateFrom()->{$diff_method}($date_to);
    }

    /**
     * Apply the date filters
     *
     * @param Builder $query
     * @return Builder
     */
    protected function applyDateFilters(Builder $query): Builder
    {
        return $query->whereBetween($this->getDateField(), [$this->getDateFrom(), $this->getDateTo()])
            ->latest($this->getDateField());
    }

    /**
     * Get the filtered query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function filteredQuery(): Builder
    {
        return $this->applyDateFilters($this->applyFilters($this->query()));
    }

    /**
     * Get the filtered query without date filters
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function filteredQueryWithoutDateFilters(): Builder
    {
        return $this->applyFilters($this->query());
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

    /**
     * Check if all the filters are allowed
     *
     * @param array $filters
     * @throws InvalidArgumentException
     */
    protected function ensureAllFiltersAllowed(array $filters)
    {
        $allowed_filters = $this->allowedFilters();

        foreach ($filters as $filter => $value) {
            if (!in_array($filter, $allowed_filters)) {
                throw new InvalidArgumentException("The filter '$filter' is not allowed");
            }
        }

        $this->filters = $filters;
    }

    /**
     * Get all the allowed filters
     *
     * @return array
     */
    public function allowedFilters(): array
    {
        return property_exists($this, 'allowed_filters') ? $this->allowed_filters : [];
    }

    /**
     * Get the base query
     *
     * @return Builder
     */
    public abstract function query(): Builder;

    /**
     * Apply the filters
     *
     * @param Builder $query
     * @return Builder
     */
    protected abstract function applyFilters(Builder $query): Builder;

    /**
     * Get the hourly query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public abstract function hour();

    /**
     * Get the day query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public abstract function day();

    /**
     * Get the week query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public abstract function week();

    /**
     * Get the month query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public abstract function month();

    /**
     * Get the total
     *
     * @return int
     */
    public abstract function total();
}
