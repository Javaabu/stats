<?php

namespace Javaabu\Stats\Charts;

use Carbon\CarbonInterface;
use Javaabu\Stats\Charts\Concerns\HasStats;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Exceptions\StatsException;
use Javaabu\Stats\Support\TimeSeriesSteps;
use Javaabu\Stats\TimeSeriesStats;

/**
 * Time series stats on a time axis: one or more stats, one stat over several
 * periods, or one stat against the previous period. Drawn by Onu's
 * `TimeSeriesChart` in React and as an SVG line, area or bar chart in Blade.
 *
 * ```php
 * TimeSeriesChart::make('payments_count')->range(PresetDateRanges::LAST_30_DAYS)->compare();
 * TimeSeriesChart::make()->stats(['payments_count', 'refunds_count'])->variant('bar');
 * TimeSeriesChart::make('payments_count')->mode(TimeSeriesModes::MONTH)
 *     ->periods([PresetDateRanges::THIS_YEAR, PresetDateRanges::LAST_YEAR]);
 * ```
 */
class TimeSeriesChart extends Chart
{
    use HasStats;

    public const string LINE = 'line';

    public const string AREA = 'area';

    public const string BAR = 'bar';

    protected ?TimeSeriesModes $mode = null;

    /** @var list<DateRange> */
    protected array $periods = [];

    protected DateRange|bool $compare = false;

    protected string $variant = self::LINE;

    protected bool $stacked = false;

    protected bool $brush = false;

    protected bool $positive_is_good = true;

    public static function make(?string $metric = null, ?string $label = null): static
    {
        $chart = new static;

        if ($metric !== null) {
            $chart->stat($metric, $label);
        }

        return $chart;
    }

    public function type(): string
    {
        return 'time-series';
    }

    /**
     * Group by hour, day, week, month or year. Default: `config('stats.default_time_series_mode')`
     */
    public function mode(TimeSeriesModes|string $mode): static
    {
        $this->mode = is_string($mode) ? TimeSeriesModes::from($mode) : $mode;

        return $this;
    }

    public function getMode(): TimeSeriesModes
    {
        return $this->mode ?? TimeSeriesStats::defaultMode();
    }

    /**
     * Plot the first stat over each period, step by step on the first period's dates
     *
     * @param  list<DateRange|string>  $periods
     */
    public function periods(array $periods): static
    {
        $this->periods = array_map(fn ($period) => is_string($period) ? PresetDateRanges::from($period) : $period, array_values($periods));

        return $this;
    }

    /**
     * Compare the first stat with the previous period, or with the given period
     */
    public function compare(DateRange|string|bool $with = true): static
    {
        $this->compare = is_string($with) ? PresetDateRanges::from($with) : $with;

        return $this;
    }

    /**
     * Whether a rise is good (green) in the comparison. False for costs, complaints, …
     */
    public function positiveIsGood(bool $positive_is_good = true): static
    {
        $this->positive_is_good = $positive_is_good;

        return $this;
    }

    /**
     * How the series are drawn: line, area or bar
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function line(): static
    {
        return $this->variant(self::LINE);
    }

    public function area(): static
    {
        return $this->variant(self::AREA);
    }

    public function bar(): static
    {
        return $this->variant(self::BAR);
    }

    public function stacked(bool $stacked = true): static
    {
        $this->stacked = $stacked;

        return $this;
    }

    /**
     * Add a range slider to zoom in (React only)
     */
    public function brush(bool $brush = true): static
    {
        $this->brush = $brush;

        return $this;
    }

    public function getCompareRange(): ?DateRange
    {
        if ($this->compare instanceof DateRange) {
            return $this->compare;
        }

        return $this->compare ? $this->getRange()->getPreviousDateRange() : null;
    }

    protected function defaultTitle(): string
    {
        $metric = $this->series[0]['metric'] ?? null;

        return $metric ? $this->makeStat($metric, $this->getRange(), $this->series[0]['filters'])->getName() : '';
    }

    public function makeStat(string $metric, DateRange $range, array $filters = []): TimeSeriesStatsRepository
    {
        if (! class_exists(TimeSeriesStats::getClassNameForMetric($metric))) {
            throw StatsException::unknownMetric($metric);
        }

        return TimeSeriesStats::createFromMetric($metric, $range, array_merge($this->filters, $filters));
    }

    protected function compute(): array
    {
        $mode = $this->getMode();

        $columns = $this->periods !== [] ? $this->periodColumns($mode) : $this->seriesColumns($mode);

        $first = $columns[0] ?? null;
        $rows = [];
        $labels = [];

        foreach ($first['steps'] ?? [] as $i => $step) {
            $row = ['date' => $mode->toIsoDate($step['date'])];

            foreach ($columns as $column) {
                $row[$column['key']] = $column['steps'][$i]['value'] ?? null;
            }

            $rows[] = $row;
            $labels[] = $mode->formatDate($step['date']);
        }

        $config = [];
        $totals = [];

        foreach ($columns as $column) {
            $config[$column['key']] = ['label' => $column['label']];
            $totals[$column['key']] = array_sum(array_column($column['steps'], 'value'));
        }

        $comparison = null;

        if ($this->periods === [] && ($previous = collect($columns)->firstWhere('key', 'previous'))) {
            $comparison = [
                'previousKey' => 'previous',
                'currentLabel' => $first['label'],
                'previousLabel' => $previous['label'],
                'positiveIsGood' => $this->positive_is_good,
            ];
        }

        return [
            'data' => $rows,
            'labels' => $labels,
            'config' => $config,
            'totals' => $totals,
            'dateKey' => 'date',
            'mode' => $mode->value,
            'interval' => $mode->chartInterval(),
            'variant' => $this->variant,
            'stacked' => $this->stacked && $comparison === null,
            'brush' => $this->brush,
            'comparison' => $comparison,
            'aggregateLabel' => $first['aggregate_label'] ?? null,
        ];
    }

    /**
     * One column per stat, and one for the comparison
     *
     * @return list<array{key: string, label: string, steps: list<array<string, mixed>>, aggregate_label: string}>
     */
    protected function seriesColumns(TimeSeriesModes $mode): array
    {
        $range = $this->getRange();
        $compare_range = $this->getCompareRange();
        $keys = $this->seriesKeys();
        $columns = [];

        foreach ($this->series as $i => $series) {
            $stat = $this->makeStat($series['metric'], $range, $series['filters']);

            $label = $series['label'] ?? $stat->getName();

            if ($i === 0 && $compare_range) {
                $label = TimeSeriesSteps::rangeLabel($range, $stat->getDateFrom(), $stat->getDateTo());
            }

            $columns[] = [
                'key' => $keys[$i],
                'label' => $label,
                'steps' => TimeSeriesSteps::for($stat, $mode),
                'aggregate_label' => $stat->getAggregateFieldLabel(),
            ];
        }

        if ($compare_range && $this->series !== []) {
            $series = $this->series[0];
            $stat = $this->makeStat($series['metric'], $compare_range, $series['filters']);

            $columns[] = [
                'key' => 'previous',
                'label' => TimeSeriesSteps::rangeLabel($compare_range, $stat->getDateFrom(), $stat->getDateTo()),
                'steps' => TimeSeriesSteps::for($stat, $mode),
                'aggregate_label' => $stat->getAggregateFieldLabel(),
            ];
        }

        return $columns;
    }

    /**
     * One column per period of the first stat
     *
     * @return list<array{key: string, label: string, steps: list<array<string, mixed>>, aggregate_label: string}>
     */
    protected function periodColumns(TimeSeriesModes $mode): array
    {
        $series = $this->series[0] ?? null;

        if (! $series) {
            return [];
        }

        $columns = [];

        foreach ($this->periods as $i => $period) {
            $stat = $this->makeStat($series['metric'], $period, $series['filters']);

            $columns[] = [
                'key' => 'period_' . ($i + 1),
                'label' => TimeSeriesSteps::rangeLabel($period, $stat->getDateFrom(), $stat->getDateTo()),
                'steps' => TimeSeriesSteps::for($stat, $mode),
                'aggregate_label' => $stat->getAggregateFieldLabel(),
            ];
        }

        return $columns;
    }

    protected function cacheKey(): array
    {
        $compare = $this->getCompareRange();

        return [
            ...$this->seriesCacheKey(),
            'mode' => $this->getMode()->value,
            'range' => static::rangeKey($this->getRange()),
            'compare' => $compare ? static::rangeKey($compare) : null,
            'periods' => array_map(fn (DateRange $period) => static::rangeKey($period), $this->periods),
            'variant' => $this->variant,
            'stacked' => $this->stacked,
            'brush' => $this->brush,
            'positive_is_good' => $this->positive_is_good,
        ];
    }

    protected function endsAt(): ?CarbonInterface
    {
        $ranges = $this->periods !== [] ? $this->periods : [$this->getRange()];

        return collect($ranges)->map(fn (DateRange $range) => $range->getDateTo())->max();
    }
}
