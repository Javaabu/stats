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
 * A KPI tile for a dashboard: a time series stat's total for the range, the
 * change from the previous period and an optional sparkline. Drawn by Onu's
 * `StatTile` in React and by `<x-stats::stat-tile>` in Blade.
 *
 * ```php
 * StatTile::make('payments_count')->range(PresetDateRanges::THIS_MONTH)->compare()->sparkline();
 * ```
 */
class StatTile extends Chart
{
    use HasStats;

    protected DateRange|bool $compare = false;

    protected ?TimeSeriesModes $sparkline_mode = null;

    protected bool $has_sparkline = false;

    protected ?string $icon = null;

    protected ?string $href = null;

    protected bool $positive_is_good = true;

    protected ?string $delta_context = null;

    protected ?string $footer = null;

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
        return 'stat-tile';
    }

    /**
     * The tile's label. Default: the stat's name
     */
    public function label(?string $label): static
    {
        return $this->title($label);
    }

    /**
     * Show the change from the previous period, or from the given period
     */
    public function compare(DateRange|string|bool $with = true): static
    {
        $this->compare = is_string($with) ? PresetDateRanges::from($with) : $with;

        return $this;
    }

    public function getCompareRange(): ?DateRange
    {
        if ($this->compare instanceof DateRange) {
            return $this->compare;
        }

        return $this->compare ? $this->getRange()->getPreviousDateRange() : null;
    }

    /**
     * Show a sparkline of the range, grouped by the mode (default: the default mode)
     */
    public function sparkline(TimeSeriesModes|string|bool $mode = true): static
    {
        $this->has_sparkline = $mode !== false;
        $this->sparkline_mode = is_string($mode) ? TimeSeriesModes::from($mode) : ($mode instanceof TimeSeriesModes ? $mode : null);

        return $this;
    }

    /**
     * An Onu semantic icon name (`file-text`, `credit-card`, …)
     */
    public function icon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Make the tile a link
     */
    public function href(?string $href): static
    {
        $this->href = $href;

        return $this;
    }

    public function positiveIsGood(bool $positive_is_good = true): static
    {
        $this->positive_is_good = $positive_is_good;

        return $this;
    }

    /**
     * Text after the change. Default: "vs previous period"
     */
    public function deltaContext(?string $context): static
    {
        $this->delta_context = $context;

        return $this;
    }

    public function footer(?string $footer): static
    {
        $this->footer = $footer;

        return $this;
    }

    protected function defaultTitle(): string
    {
        $series = $this->series[0] ?? null;

        return $series['label'] ?? ($series ? $this->makeStat($series['metric'], $this->getRange(), $series['filters'])->getName() : '');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeStat(string $metric, DateRange $range, array $filters = []): TimeSeriesStatsRepository
    {
        if (! class_exists(TimeSeriesStats::getClassNameForMetric($metric))) {
            throw StatsException::unknownMetric($metric);
        }

        return TimeSeriesStats::createFromMetric($metric, $range, array_merge($this->filters, $filters));
    }

    protected function compute(): array
    {
        $series = $this->series[0] ?? null;

        if (! $series) {
            return ['value' => null, 'previousValue' => null, 'sparkline' => null, 'period' => null];
        }

        $stat = $this->makeStat($series['metric'], $this->getRange(), $series['filters']);
        $compare_range = $this->getCompareRange();
        $previous = $compare_range ? $this->makeStat($series['metric'], $compare_range, $series['filters'])->total() : null;
        $sparkline = null;

        if ($this->has_sparkline) {
            $sparkline = array_column(TimeSeriesSteps::for($stat, $this->sparkline_mode ?? TimeSeriesStats::defaultMode()), 'value');
        }

        return [
            'value' => $stat->total(),
            'previousValue' => $previous,
            'sparkline' => $sparkline,
            'period' => TimeSeriesSteps::rangeLabel($this->getRange(), $stat->getDateFrom(), $stat->getDateTo()),
        ];
    }

    protected function cacheKey(): array
    {
        $compare = $this->getCompareRange();

        return [
            ...$this->seriesCacheKey(),
            'range' => static::rangeKey($this->getRange()),
            'compare' => $compare ? static::rangeKey($compare) : null,
            'sparkline' => $this->has_sparkline ? ($this->sparkline_mode ?? TimeSeriesStats::defaultMode())->value : null,
        ];
    }

    /**
     * The cached numbers, with the tile's label, link, icon and texts, which
     * aren't part of the cache key
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $compare = $this->getCompareRange() !== null;

        return [
            ...parent::toArray(),
            'label' => $this->getTitle(),
            'deltaContext' => $compare ? ($this->delta_context ?? __('vs previous period')) : null,
            'positiveIsGood' => $this->positive_is_good,
            'icon' => $this->icon,
            'href' => $this->href,
            'footer' => $this->footer,
        ];
    }

    protected function endsAt(): ?CarbonInterface
    {
        return $this->getRange()->getDateTo();
    }
}
