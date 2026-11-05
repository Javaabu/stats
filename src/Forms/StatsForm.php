<?php

namespace Javaabu\Stats\Forms;

use Javaabu\Stats\Contracts\HasFields;
use Javaabu\Stats\Contracts\InteractsWithFilters;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stencil\Components\Component;
use Javaabu\Stencil\Components\Group;
use Javaabu\Stencil\Fields\DatePicker;
use Javaabu\Stencil\Fields\Hidden;
use Javaabu\Stencil\Fields\Select;
use Javaabu\Stencil\Form;

/**
 * What the time series and categorical stats forms share: the metric, the
 * date range, the comparison, the model filters and the fixed filters.
 *
 * Context (`with()`):
 *
 * | Key       | What                                                         |
 * | --------- | ------------------------------------------------------------ |
 * | `metrics` | Names keyed by metric, the ones the user can see              |
 * | `filters` | Fixed filters, sent as hidden `filters[...]` fields           |
 */
abstract class StatsForm extends Form
{
    protected function setUp(): void
    {
        $this->method('GET')
            ->columns(['md' => 2, 'lg' => 4])
            ->submit(__('Generate'));
    }

    /**
     * The stat of a metric, to read its filters
     */
    abstract protected function statFor(string $metric): InteractsWithFilters;

    /**
     * @return array<string, string>
     */
    protected function metricOptions(): array
    {
        return (array) $this->context('metrics', []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fixedFilters(): array
    {
        return (array) $this->context('filters', []);
    }

    protected function metricField(): Select
    {
        return Select::make('metric')
            ->label(__('Stat'))
            ->options($this->metricOptions())
            ->searchable()
            ->clearable(false)
            ->required();
    }

    protected function withField(): Select
    {
        return Select::make('with')
            ->label(__('Plot with'))
            ->help(__('Other stats to show on the same chart.'))
            ->options($this->metricOptions())
            ->multiple()
            ->searchable();
    }

    /**
     * @return array<int, Component>
     */
    protected function dateRangeFields(): array
    {
        return [
            Select::make('date_range')
                ->label(__('Date range'))
                ->options([...PresetDateRanges::getLabels(), TimeSeriesStatsRequest::CUSTOM_RANGE => __('Custom range')])
                ->clearable(false)
                ->required(),
            Select::make('compare_to')
                ->label(__('Compare to'))
                ->placeholder(__('No comparison'))
                ->options([
                    TimeSeriesStatsRequest::COMPARE_PREVIOUS => __('Previous period'),
                    TimeSeriesStatsRequest::COMPARE_CUSTOM => __('Custom period'),
                ]),
            Group::make([
                DatePicker::make('date_from')->label(__('From'))->required(),
                DatePicker::make('date_to')->label(__('To'))->required(),
            ])->columns(2)->columnSpan(['md' => 2])->visibleWhen('date_range', TimeSeriesStatsRequest::CUSTOM_RANGE),
            Group::make([
                DatePicker::make('compare_date_from')->label(__('Compare from'))->required(),
                DatePicker::make('compare_date_to')->label(__('Compare to date'))->required(),
            ])->columns(2)->columnSpan(['md' => 2])->visibleWhen('compare_to', TimeSeriesStatsRequest::COMPARE_CUSTOM),
        ];
    }

    /**
     * The fields of every filter the stats allow, each shown while a stat
     * that has it is selected
     *
     * @return array<int, Component>
     */
    protected function filterFields(): array
    {
        $fixed = array_keys($this->fixedFilters());
        $filters = [];
        $metrics_by_filter = [];

        foreach (array_keys($this->metricOptions()) as $metric) {
            foreach ($this->statFor($metric)->allowedFilters() as $filter) {
                if (! $filter instanceof HasFields || in_array($filter->getName(), $fixed, true)) {
                    continue;
                }

                $filters[$filter->getName()] ??= $filter;
                $metrics_by_filter[$filter->getName()][] = $metric;
            }
        }

        $components = [];

        foreach ($filters as $name => $filter) {
            $components[] = Group::make($filter->getFields())
                ->visibleWhen('metric', $metrics_by_filter[$name]);
        }

        return $components;
    }

    /**
     * Hidden `filters[...]` fields for the fixed filters
     *
     * @return array<int, Hidden>
     */
    protected function fixedFilterFields(): array
    {
        $fields = [];

        foreach ($this->fixedFilters() as $name => $value) {
            $fields[] = Hidden::make('filters.' . $name)->default($value);
        }

        return $fields;
    }
}
