<?php

namespace Javaabu\Stats\Forms;

use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Contracts\InteractsWithFilters;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\TimeSeriesStats;
use Javaabu\Stencil\Fields\Select;

/**
 * The time series stats form: stat, grouping, date range, comparison,
 * other stats to plot, chart type and the stats' filters
 *
 * ```php
 * TimeSeriesStatsForm::make()
 *     ->with(['metrics' => TimeSeriesStats::getMetricNames([], $user), 'filters' => []])
 *     ->model($request->query())
 *     ->action(route('stats.time-series.index'));
 * ```
 */
class TimeSeriesStatsForm extends StatsForm
{
    protected function definition(): array
    {
        return [
            $this->metricField(),
            Select::make('mode')
                ->label(__('Group by'))
                ->options(TimeSeriesModes::getLabels())
                ->default(TimeSeriesStats::defaultMode()->value)
                ->clearable(false)
                ->required(),
            ...$this->dateRangeFields(),
            $this->withField()->columnSpan(['md' => 2]),
            Select::make('variant')
                ->label(__('Chart'))
                ->options([
                    TimeSeriesChart::LINE => __('Line'),
                    TimeSeriesChart::AREA => __('Area'),
                    TimeSeriesChart::BAR => __('Bar'),
                ])
                ->default(TimeSeriesChart::LINE)
                ->clearable(false),
            ...$this->filterFields(),
            ...$this->fixedFilterFields(),
        ];
    }

    protected function statFor(string $metric): InteractsWithFilters
    {
        return TimeSeriesStats::createFromMetric($metric, filters: $this->fixedFilters());
    }
}
