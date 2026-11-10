<?php

namespace Javaabu\Stats\Charts;

use Carbon\CarbonInterface;
use Javaabu\Stats\Charts\Concerns\HasCategories;
use Javaabu\Stats\Charts\Concerns\HasStats;
use Javaabu\Stats\Contracts\DateRange;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Support\TimeSeriesSteps;

/**
 * Categorical stats over their categories: one or more stats side by side,
 * or one stat against the previous period. Drawn by Onu's `CategoryChart`
 * in React and as an SVG bar, line or area chart in Blade.
 *
 * ```php
 * CategoryChart::make('payments_by_status')->range(PresetDateRanges::THIS_YEAR)->compare();
 * CategoryChart::make()->stats(['payments_by_island', 'refunds_by_island'])->horizontal()->top(10);
 * ```
 */
class CategoryChart extends Chart
{
    use HasCategories;
    use HasStats;

    public const string BAR = 'bar';

    public const string LINE = 'line';

    public const string AREA = 'area';

    protected DateRange|bool $compare = false;

    protected string $variant = self::BAR;

    protected string $orientation = 'vertical';

    protected bool $stacked = false;

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
        return 'category';
    }

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

    public function positiveIsGood(bool $positive_is_good = true): static
    {
        $this->positive_is_good = $positive_is_good;

        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function bar(): static
    {
        return $this->variant(self::BAR);
    }

    public function line(): static
    {
        return $this->variant(self::LINE);
    }

    public function area(): static
    {
        return $this->variant(self::AREA);
    }

    /**
     * Bars along the side, for long category names
     */
    public function horizontal(bool $horizontal = true): static
    {
        $this->orientation = $horizontal ? 'horizontal' : 'vertical';

        return $this;
    }

    public function stacked(bool $stacked = true): static
    {
        $this->stacked = $stacked;

        return $this;
    }

    protected function defaultTitle(): string
    {
        $series = $this->series[0] ?? null;

        return $series ? $this->makeStat($series['metric'], $this->getRange(), $series['filters'])->getName() : '';
    }

    protected function compute(): array
    {
        $range = $this->getRange();
        $compare_range = $this->getCompareRange();
        $keys = $this->seriesKeys();
        $columns = [];
        $first_stat = null;

        foreach ($this->series as $i => $series) {
            $stat = $this->makeStat($series['metric'], $range, $series['filters']);
            $first_stat ??= $stat;

            $columns[] = [
                'key' => $keys[$i],
                'label' => $i === 0 && $compare_range
                    ? TimeSeriesSteps::rangeLabel($range, $stat->getDateFrom(), $stat->getDateTo())
                    : ($series['label'] ?? $stat->getName()),
                'values' => $stat->resultsToArray($stat->results()),
            ];
        }

        if ($compare_range && $first_stat) {
            $series = $this->series[0];
            $stat = $this->makeStat($series['metric'], $compare_range, $series['filters']);

            $columns[] = [
                'key' => 'previous',
                'label' => TimeSeriesSteps::rangeLabel($compare_range, $stat->getDateFrom(), $stat->getDateTo()),
                'values' => $stat->resultsToArray($stat->results()),
            ];
        }

        $names = $first_stat ? $this->categoryNames($first_stat, array_column($columns, 'values')) : [];
        $rows = [];

        foreach ($names as $id => $name) {
            $row = ['category' => $name, 'categoryId' => $id];

            foreach ($columns as $column) {
                $row[$column['key']] = ($column['values'][$id] ?? 0) + 0;
            }

            $rows[] = $row;
        }

        if ($this->limit !== null && $columns !== []) {
            $key = $columns[0]['key'];

            usort($rows, fn (array $a, array $b) => $b[$key] <=> $a[$key]);

            $rows = array_slice($rows, 0, $this->limit);
        }

        $config = [];

        foreach ($columns as $column) {
            $config[$column['key']] = ['label' => $column['label']];
        }

        $comparison = $compare_range && $first_stat ? [
            'previousKey' => 'previous',
            'currentLabel' => $columns[0]['label'],
            'previousLabel' => end($columns)['label'],
            'positiveIsGood' => $this->positive_is_good,
        ] : null;

        return [
            'data' => $rows,
            'config' => $config,
            'categoryKey' => 'category',
            'categoryLabel' => $first_stat?->getCategoryTitle(),
            'aggregateLabel' => $first_stat?->getAggregateFieldLabel(),
            'variant' => $this->variant,
            'orientation' => $this->variant === self::BAR ? $this->orientation : null,
            'stacked' => $this->stacked && $comparison === null,
            'comparison' => $comparison,
        ];
    }

    protected function cacheKey(): array
    {
        $compare = $this->getCompareRange();

        return [
            ...$this->seriesCacheKey(),
            ...$this->categoriesCacheKey(),
            'range' => static::rangeKey($this->getRange()),
            'compare' => $compare ? static::rangeKey($compare) : null,
            'variant' => $this->variant,
            'orientation' => $this->orientation,
            'stacked' => $this->stacked,
            'positive_is_good' => $this->positive_is_good,
        ];
    }

    protected function endsAt(): ?CarbonInterface
    {
        return $this->getRange()->getDateTo();
    }
}
