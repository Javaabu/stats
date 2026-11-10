<?php

namespace Javaabu\Stats\Http\Requests\Concerns;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Date;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\HasFields;
use Javaabu\Stats\Contracts\InteractsWithFilters;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Routing\StatsRoutes;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;

/**
 * Reads the date range, the comparison and the filters of a stats request.
 *
 * | Parameter                               | Meaning                                               |
 * | --------------------------------------- | ----------------------------------------------------- |
 * | `date_range`                            | A preset (`last_7_days`), or `custom`                 |
 * | `date_from`, `date_to`                  | A custom range, when `date_range` is empty or custom  |
 * | `compare_to`                            | `previous`, `custom`, or empty for no comparison      |
 * | `compare`                               | `1` compares with the previous period (v1)            |
 * | `compare_date_range`                    | A preset to compare with                              |
 * | `compare_date_from`, `compare_date_to`  | A custom period to compare with                       |
 * | `filters[...]`                          | Fixed filters, e.g. for stats of one model            |
 * | `{filter}`                              | Values of filters with form fields (`status=paid`)    |
 */
trait ResolvesStats
{
    /** The value of date_range that means "use date_from and date_to" */
    public const string CUSTOM_RANGE = 'custom';

    /** The values of compare_to */
    public const string COMPARE_PREVIOUS = 'previous';

    public const string COMPARE_CUSTOM = 'custom';

    /**
     * The user the stats are for, on the guard the routes were registered with
     */
    public function statsUser(): ?Authorizable
    {
        $user = $this->user(StatsRoutes::guard($this));

        return $user instanceof Authorizable ? $user : null;
    }

    /**
     * The fixed filters from `filters[...]`
     *
     * @return array<string, mixed>
     */
    public function fixedFilters(): array
    {
        $filters = $this->input('filters', []);

        return is_array($filters) ? $filters : [];
    }

    /**
     * The fixed filters plus the values of the stat's filters that have fields
     *
     * @return array<string, mixed>
     */
    public function filtersFor(InteractsWithFilters $stat): array
    {
        $filters = $this->fixedFilters();
        $input = $this->all();

        foreach ($stat->allowedFilters() as $filter) {
            if (! $filter instanceof HasFields || array_key_exists($filter->getName(), $filters)) {
                continue;
            }

            $value = $filter->resolveValue($input);

            if ($value !== null) {
                $filters[$filter->getName()] = $value;
            }
        }

        return $filters;
    }

    public function hasCustomDateRange(): bool
    {
        $date_range = $this->input('date_range');

        return blank($date_range) || $date_range === self::CUSTOM_RANGE;
    }

    public function dateRange(?DateRange $default = null): DateRange
    {
        if (! $this->hasCustomDateRange()) {
            return PresetDateRanges::from($this->input('date_range'));
        }

        if ($this->filled('date_from') && $this->filled('date_to')) {
            return new ExactDateRange($this->dateInput('date_from'), $this->dateInput('date_to', end_of_day: true));
        }

        return $default ?? TimeSeriesStats::defaultDateRange();
    }

    /**
     * A date from the input. A date without a time covers the whole day.
     */
    protected function dateInput(string $key, bool $end_of_day = false): CarbonInterface
    {
        $value = (string) $this->input($key);
        $date = Date::parse($value);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value))) {
            return $date;
        }

        return $end_of_day ? $date->endOfDay() : $date->startOfDay();
    }

    /**
     * What the comparison is: a preset's name, `previous`, `custom`, or null for none
     */
    public function compareDateRangeName(): ?string
    {
        if ($this->filled('compare_date_range')) {
            return $this->input('compare_date_range');
        }

        $compare_to = $this->input('compare_to');

        if ($compare_to === self::COMPARE_CUSTOM || (blank($compare_to) && $this->filled('compare_date_from'))) {
            return $this->filled('compare_date_from') && $this->filled('compare_date_to') ? self::COMPARE_CUSTOM : null;
        }

        if ($compare_to === self::COMPARE_PREVIOUS || $this->boolean('compare')) {
            return self::COMPARE_PREVIOUS;
        }

        return null;
    }

    public function compareDateRange(?DateRange $date_range = null): ?DateRange
    {
        return match ($this->compareDateRangeName()) {
            null => null,
            self::COMPARE_PREVIOUS => ($date_range ?? $this->dateRange())->getPreviousDateRange(),
            self::COMPARE_CUSTOM => new ExactDateRange(
                $this->dateInput('compare_date_from'),
                $this->dateInput('compare_date_to', end_of_day: true),
            ),
            default => PresetDateRanges::from($this->input('compare_date_range')),
        };
    }

    /**
     * Rules for the date range and comparison parameters
     *
     * @return array<string, array<int, mixed>>
     */
    protected function dateRangeRules(): array
    {
        $presets = array_map(fn (PresetDateRanges $range) => $range->value, PresetDateRanges::cases());

        return [
            'date_range' => ['nullable', 'string', 'in:' . implode(',', [...$presets, self::CUSTOM_RANGE])],
            'date_from' => ['nullable', 'date', 'required_without:date_range', 'required_if:date_range,' . self::CUSTOM_RANGE],
            'date_to' => ['nullable', 'date', 'required_without:date_range', 'required_if:date_range,' . self::CUSTOM_RANGE, 'after_or_equal:date_from'],
            'compare_to' => ['nullable', 'string', 'in:' . self::COMPARE_PREVIOUS . ',' . self::COMPARE_CUSTOM],
            'compare' => ['nullable', 'boolean'],
            'compare_date_range' => ['nullable', 'string', 'in:' . implode(',', $presets)],
            'compare_date_from' => ['nullable', 'date', 'required_with:compare_date_to', 'required_if:compare_to,' . self::COMPARE_CUSTOM],
            'compare_date_to' => ['nullable', 'date', 'required_with:compare_date_from', 'required_if:compare_to,' . self::COMPARE_CUSTOM, 'after_or_equal:compare_date_from'],
            'filters' => ['nullable', 'array'],
        ];
    }
}
