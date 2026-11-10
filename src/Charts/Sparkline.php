<?php

namespace Javaabu\Stats\Charts;

use Carbon\CarbonInterface;
use Javaabu\Stats\Charts\Concerns\HasStats;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Exceptions\StatsException;
use Javaabu\Stats\Support\TimeSeriesSteps;
use Javaabu\Stats\TimeSeriesStats;

/**
 * A time series stat as a tiny line or area with no axes, for stat cards and
 * tables. Drawn by Onu's `Sparkline` in React and as an inline SVG in Blade.
 *
 * ```php
 * Sparkline::make('payments_count')->range(PresetDateRanges::LAST_30_DAYS)->area();
 * ```
 */
class Sparkline extends Chart
{
    use HasStats;

    protected ?TimeSeriesModes $mode = null;

    protected string $variant = 'line';

    protected ?string $color = null;

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
        return 'sparkline';
    }

    public function mode(TimeSeriesModes|string $mode): static
    {
        $this->mode = is_string($mode) ? TimeSeriesModes::from($mode) : $mode;

        return $this;
    }

    public function getMode(): TimeSeriesModes
    {
        return $this->mode ?? TimeSeriesStats::defaultMode();
    }

    public function area(bool $area = true): static
    {
        $this->variant = $area ? 'area' : 'line';

        return $this;
    }

    /**
     * Any CSS colour or token. Default: `var(--chart-1)`
     */
    public function color(?string $color): static
    {
        $this->color = $color;

        return $this;
    }

    protected function defaultTitle(): string
    {
        $series = $this->series[0] ?? null;

        return $series ? $this->makeStat($series['metric'], $series['filters'])->getName() : '';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function makeStat(string $metric, array $filters = []): TimeSeriesStatsRepository
    {
        if (! class_exists(TimeSeriesStats::getClassNameForMetric($metric))) {
            throw StatsException::unknownMetric($metric);
        }

        return TimeSeriesStats::createFromMetric($metric, $this->getRange(), array_merge($this->filters, $filters));
    }

    protected function compute(): array
    {
        $series = $this->series[0] ?? null;
        $values = [];

        if ($series) {
            $values = array_column(TimeSeriesSteps::for($this->makeStat($series['metric'], $series['filters']), $this->getMode()), 'value');
        }

        return [
            'data' => $values,
            'label' => $series['label'] ?? $this->getTitle(),
            'variant' => $this->variant,
            'color' => $this->color,
            'mode' => $this->getMode()->value,
        ];
    }

    protected function cacheKey(): array
    {
        return [
            ...$this->seriesCacheKey(),
            'mode' => $this->getMode()->value,
            'range' => static::rangeKey($this->getRange()),
            'variant' => $this->variant,
            'color' => $this->color,
        ];
    }

    protected function endsAt(): ?CarbonInterface
    {
        return $this->getRange()->getDateTo();
    }
}
