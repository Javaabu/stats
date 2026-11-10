<?php

namespace Javaabu\Stats\Charts;

use Carbon\CarbonInterface;
use Javaabu\Stats\Charts\Concerns\HasCategories;
use Javaabu\Stats\Charts\Concerns\HasStats;

/**
 * A categorical stat as a pie or donut: each category's share of the total.
 * Drawn by Onu's `PieChart` in React and as an SVG pie in Blade.
 *
 * ```php
 * PieChart::make('payments_by_status')->range(PresetDateRanges::THIS_MONTH)->donut();
 * PieChart::make('applications_by_island')->top(5);   // the rest as "Other"
 * ```
 */
class PieChart extends Chart
{
    use HasCategories;
    use HasStats;

    protected bool $donut = false;

    protected ?string $center_value = null;

    protected ?string $center_label = null;

    protected bool $show_center = true;

    protected bool $keep_empty = false;

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
        return 'pie';
    }

    public function donut(bool $donut = true): static
    {
        $this->donut = $donut;

        return $this;
    }

    /**
     * The donut's centre: a value and a label, or `false` for none. Default: the total
     */
    public function centerLabel(string|false|null $value, ?string $label = null): static
    {
        $this->show_center = $value !== false;
        $this->center_value = is_string($value) ? $value : null;
        $this->center_label = $label;

        return $this;
    }

    /**
     * Keep categories with no value as empty slices in the table
     */
    public function keepEmpty(bool $keep = true): static
    {
        $this->keep_empty = $keep;

        return $this;
    }

    protected function defaultTitle(): string
    {
        $series = $this->series[0] ?? null;

        return $series ? $this->makeStat($series['metric'], $this->getRange(), $series['filters'])->getName() : '';
    }

    protected function compute(): array
    {
        $series = $this->series[0] ?? null;

        if (! $series) {
            return ['data' => [], 'variant' => $this->donut ? 'donut' : 'pie', 'centerLabel' => null, 'total' => 0];
        }

        $stat = $this->makeStat($series['metric'], $this->getRange(), $series['filters']);
        $values = $stat->resultsToArray($stat->results());
        $slices = [];

        foreach ($this->categoryNames($stat, [$values]) as $id => $name) {
            $value = ($values[$id] ?? 0) + 0;

            if ($value <= 0 && ! $this->keep_empty) {
                continue;
            }

            $slices[] = ['name' => $name, 'value' => $value, 'categoryId' => $id];
        }

        usort($slices, fn (array $a, array $b) => $b['value'] <=> $a['value']);

        if ($this->limit !== null && count($slices) > $this->limit) {
            $rest = array_slice($slices, $this->limit);

            $slices = array_slice($slices, 0, $this->limit);
            $slices[] = [
                'name' => __('Other'),
                'value' => array_sum(array_column($rest, 'value')),
                'categoryId' => null,
            ];
        }

        $center = null;

        if ($this->donut && $this->show_center && ($this->center_value !== null || $this->center_label !== null)) {
            $center = array_filter(['value' => $this->center_value, 'label' => $this->center_label], fn ($value) => $value !== null);
        }

        return [
            'data' => $slices,
            'nameKey' => 'name',
            'valueKey' => 'value',
            'variant' => $this->donut ? 'donut' : 'pie',
            'centerLabel' => $this->show_center ? $center : false,
            'nameLabel' => $stat->getCategoryTitle(),
            'aggregateLabel' => $stat->getAggregateFieldLabel(),
            'total' => array_sum(array_column($slices, 'value')),
        ];
    }

    protected function cacheKey(): array
    {
        return [
            ...$this->seriesCacheKey(),
            ...$this->categoriesCacheKey(),
            'range' => static::rangeKey($this->getRange()),
            'donut' => $this->donut,
            'center' => [$this->show_center, $this->center_value, $this->center_label],
            'keep_empty' => $this->keep_empty,
        ];
    }

    protected function endsAt(): ?CarbonInterface
    {
        return $this->getRange()->getDateTo();
    }
}
