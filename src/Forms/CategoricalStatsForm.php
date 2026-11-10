<?php

namespace Javaabu\Stats\Forms;

use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Contracts\InteractsWithFilters;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Forms\Fields\CategorySelect;
use Javaabu\Stencil\Fields\Select;

/**
 * The categorical stats form: stat, categories, date range, comparison,
 * other stats to plot, chart type and the stats' filters
 *
 * Context (`with()`) adds `categories_url`, the endpoint that searches a
 * stat's categories (`Route::stats()` registers it as `{name}.categorical.categories`).
 */
class CategoricalStatsForm extends StatsForm
{
    public const string PIE = 'pie';

    protected function definition(): array
    {
        return [
            $this->metricField(),
            Select::make('mode')
                ->label(__('Categories'))
                ->options(CategoricalModes::getLabels())
                ->default(CategoricalStats::defaultMode()->value)
                ->clearable(false)
                ->required(),
            ...$this->dateRangeFields(),
            CategorySelect::make('categorical_values')
                ->label(__('Choose categories'))
                ->ajax($this->categoriesUrl(), 'label', 'id')
                ->dependsOn('#metric', 'metric')
                ->columnSpan(['md' => 2])
                ->visibleWhen('mode', CategoricalModes::SPECIFIC->value)
                ->required(),
            $this->withField(),
            Select::make('variant')
                ->label(__('Chart'))
                ->options([
                    'bar' => __('Bar'),
                    'horizontal' => __('Horizontal bar'),
                    'line' => __('Line'),
                    self::PIE => __('Pie'),
                ])
                ->default('bar')
                ->clearable(false),
            ...$this->filterFields(),
            ...$this->fixedFilterFields(),
        ];
    }

    protected function categoriesUrl(): string
    {
        $url = (string) $this->context('categories_url', '');
        $filters = $this->fixedFilters();

        if ($filters === [] || $url === '') {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query(['filters' => $filters]);
    }

    protected function statFor(string $metric): InteractsWithFilters
    {
        return CategoricalStats::createFromMetric($metric, filters: $this->fixedFilters());
    }
}
