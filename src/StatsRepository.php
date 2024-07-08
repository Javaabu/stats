<?php
/**
 * Stats Repository base class
 */

namespace Javaabu\Stats;

use Javaabu\Stats\Formatters\StatsFormatter;
use Carbon\Carbon;
use \InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class StatsRepository
{
    /**
     * @var string[]
     */
    const METRICS = [
        'active_prompters' => [
            'name' => 'Active Prompters',
            'class' => ActivePromptersCountRepository::class,
        ],

        'active_paid_prompters' => [
            'name' => 'Active Paid Prompters',
            'class' => ActivePaidPromptersCountRepository::class,
        ],

        'active_unpaid_prompters' => [
            'name' => 'Active Never Paid Prompters',
            'class' => ActiveUnPaidPromptersCountRepository::class,
        ],

        'new_sessions' => [
            'name' => 'Chat Sessions',
            'class' => SessionsCountRepository::class,
        ],

        'new_prompts' => [
            'name' => 'Prompts Given',
            'class' => PromptsCountRepository::class,
        ],

        'prompter_signups' => [
            'name' => 'Prompter Signups',
            'class' => PrompterSignupsRepository::class,
        ],

        'paid_prompter_signups' => [
            'name' => 'Paid Prompter Signups',
            'class' => PaidPrompterSignupsRepository::class,
        ],

        'unpaid_prompter_signups' => [
            'name' => 'Never Paid Prompter Signups',
            'class' => UnPaidPrompterSignupsRepository::class,
        ],

        'prompter_logins' => [
            'name' => 'Prompter Logins',
            'class' => PrompterLoginsRepository::class,
        ],

        'payments_count' => [
            'name' => 'Successful Payment Transactions',
            'class' => PaymentsCountRepository::class,
        ],

        'payments_received' => [
            'name' => 'Total Payments Received',
            'class' => PaymentAmountsRepository::class,
        ],

        'payments_deposited' => [
            'name' => 'Total Payments Deposited',
            'class' => PaymentDepositedAmountsRepository::class,
        ],

        'accurate_prompts' => [
            'name' => 'Accurate Prompts',
            'class' => AccuratePromptsCountRepository::class,
        ],

        'in_accurate_prompts' => [
            'name' => 'Inaccurate Prompts',
            'class' => InAccuratePromptsCountRepository::class,
        ],

        'not_specified_prompts' => [
            'name' => 'Accuracy Not Specified Prompts',
            'class' => NotSpecifiedPromptsCountRepository::class,
        ],

        'user_logins' => [
            'name' => 'Admin User Logins',
            'class' => AdminLoginsRepository::class,
        ],

        'user_signups' => [
            'name' => 'Admin User Signups',
            'class' => UserSignupsRepository::class,
        ],
    ];

    /**
     * @var string[]
     */
    protected static $date_ranges = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
        'last_7_days' => 'Last 7 Days',
        'last_14_days' => 'Last 14 Days',
        'last_30_days' => 'Last 30 Days',
        'lifetime' => 'Lifetime',
    ];

    /**
     * @var string[]
     */
    protected static $modes = [
        'hour' => 'Hourly',
        'day' => 'Day',
        'week' => 'Week',
        'month' => 'Month',
        'year' => 'Year',
    ];

    /**
     * @var Carbon
     */
    protected $date_from;

    /**
     * @var Carbon
     */
    protected $date_to;

    /**
     * @var string
     */
    protected $date_range;

    /**
     * @var array
     */
    protected $filters;

    /**
     * @var string
     */
    protected $aggregate_field;

    /**
     * Create a new stats repository instance.
     *
     * @param array|string $date_range
     * @param array $filters
     */
    public function __construct($date_range, $filters = [])
    {
        $this->setDateRange($date_range);
        $this->ensureAllFiltersAllowed(Arr::wrap($filters));
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
     * @return StatsRepository
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
     * Get the modes
     *
     * @return string[]
     */
    public static function getModes()
    {
        return static::$modes;
    }

    /**
     * Check whether is a valid mode
     *
     * @param $mode
     * @return bool
     */
    public static function isValidMode($mode)
    {
        return array_key_exists($mode, static::getModes());
    }

    /**
     * Get the date ranges
     *
     * @return string[]
     */
    public static function getDateRanges()
    {
        return static::$date_ranges;
    }

    /**
     * Check whether is a valid date range
     *
     * @param $date_range
     * @return bool
     */
    public static function isValidDateRange($date_range)
    {
        return array_key_exists($date_range, static::getDateRanges());
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
     * Get date range start and end dates
     *
     * @param $date_range
     * @param Carbon|null $start_date
     * @return Carbon[]
     */
    public static function dateRangeToDates($date_range, $start_date = null, $min_date = null, $max_date = null)
    {
        // verify the range is valid
        if (!static::isValidDateRange($date_range)) {
            throw new \InvalidArgumentException('Invalid date range');
        }

        $date_from = null;
        $date_to = null;

        if (!$start_date) {
            $start_date = Carbon::now();
        }

        switch ($date_range) {
            case 'today':
                $date_from = $start_date->copy()->startOfDay();
                $date_to = $date_from->copy()->endOfDay();
                break;

            case 'yesterday':
                $date_from = $start_date->copy()->subDay()->startOfDay();
                $date_to = $date_from->copy()->endOfDay();
                break;

            case 'this_week':
                $date_from = $start_date->copy()->startOfWeek();
                $date_to = $date_from->copy()->endOfWeek();
                break;

            case 'last_week':
                $date_from = $start_date->copy()->subWeek()->startOfWeek();
                $date_to = $date_from->copy()->endOfWeek();
                break;

            case 'this_month':
                $date_from = $start_date->copy()->startOfMonth();
                $date_to = $date_from->copy()->endOfMonth();
                break;

            case 'last_month':
                $date_from = $start_date->copy()->subMonth()->startOfMonth();
                $date_to = $date_from->copy()->endOfMonth();
                break;

            case 'this_year':
                $date_from = $start_date->copy()->startOfYear();
                $date_to = $date_from->copy()->endOfYear();
                break;

            case 'last_year':
                $date_from = $start_date->copy()->subYear()->startOfYear();
                $date_to = $date_from->copy()->endOfYear();
                break;

            case 'last_7_days':
                $date_from = $start_date->copy()->subDays(7)->startOfDay();
                $date_to = $date_from->copy()->addDays(7)->endOfDay();
                break;

            case 'last_14_days':
                $date_from = $start_date->copy()->subDays(14)->startOfDay();
                $date_to = $date_from->copy()->addDays(14)->endOfDay();
                break;

            case 'last_30_days':
                $date_from = $start_date->copy()->subDays(30)->startOfDay();
                $date_to = $date_from->copy()->addDays(30)->endOfDay();
                break;

            case 'lifetime':
                $date_from = $min_date ? Carbon::parse($min_date) : Carbon::parse('2019-11-12'); //need a stating date
                $date_to = $max_date ? Carbon::parse($max_date) : Carbon::now();

                if ($date_to < Carbon::now()) {
                    $date_to = Carbon::now();
                }

                break;
        }

        return [$date_from, $date_to];
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
     * Get the date from
     *
     * @return Carbon
     */
    public function getDateFrom()
    {
        return $this->date_from;
    }

    /**
     * Get the date to
     *
     * @return Carbon
     */
    public function getDateTo()
    {
        return $this->date_to;
    }

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
