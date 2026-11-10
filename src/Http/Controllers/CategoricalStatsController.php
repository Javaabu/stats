<?php

namespace Javaabu\Stats\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Charts\CategoryChart;
use Javaabu\Stats\Charts\Chart;
use Javaabu\Stats\Charts\PieChart;
use Javaabu\Stats\Concerns\ExportsCategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Forms\CategoricalStatsForm;
use Javaabu\Stats\Http\Controllers\Concerns\RendersStatsPage;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;
use Javaabu\Stats\Routing\StatsRoutes;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The categorical stats page: the form, the chart and a CSV download. Extend
 * it and set `config('stats.controllers.categorical')` to change what it does.
 */
class CategoricalStatsController extends Controller
{
    use ExportsCategoricalStats;
    use RendersStatsPage;

    /**
     * Fixed filters for every stat on the page, e.g. for a model-specific page
     *
     * @return array<string, mixed>
     */
    protected function filters(Request $request): array
    {
        return [];
    }

    public function index(Request $request): View|Responsable
    {
        $user = $this->statsUser($request);
        $filters = array_merge((array) $request->query('filters', []), $this->filters($request));
        $metrics = CategoricalStats::getMetricNames($filters, $user);

        abort_if($metrics === [], 403);

        [$stats_request, $validator] = $this->pageRequest($request, CategoricalStatsRequest::class, [
            'metric' => array_key_first($metrics),
            'mode' => CategoricalStats::defaultMode()->value,
            'date_range' => CategoricalStats::defaultDateRange()->value,
            'variant' => 'bar',
            'filters' => $filters ?: null,
        ]);

        $chart = null;

        if (! $validator->fails()) {
            $chart = $this->chart($stats_request);
        } else {
            $this->shareErrors($validator);
        }

        $form = CategoricalStatsForm::make()
            ->with([
                'metrics' => $metrics,
                'filters' => $filters,
                'categories_url' => route(StatsRoutes::namePrefix($request) . '.categorical.categories', $request->route()?->parameters() ?? []),
            ])
            ->model($stats_request->all())
            ->action($request->url());

        return $this->views()->render('categorical.index', $this->viewData($request, $form, $chart, $stats_request, $metrics));
    }

    /**
     * The chart for a valid request: a pie for one stat without a comparison
     * when asked for, otherwise a category chart
     */
    protected function chart(CategoricalStatsRequest $request): Chart
    {
        $stats = $request->stats();
        $variant = (string) $request->input('variant', 'bar');
        $compare = $request->compareStats();

        if ($variant === CategoricalStatsForm::PIE && ! $compare && $request->otherMetrics() === []) {
            $chart = PieChart::make()->stat($request->metric(), filters: $stats->getFilters())->donut();
        } else {
            $chart = CategoryChart::make()
                ->stat($request->metric(), filters: $stats->getFilters())
                ->variant($variant === 'horizontal' || $variant === CategoricalStatsForm::PIE ? CategoryChart::BAR : $variant)
                ->horizontal($variant === 'horizontal');

            foreach ($request->otherMetrics() as $metric) {
                $chart->stat($metric, filters: $request->stats($metric)->getFilters());
            }

            if ($compare) {
                $chart->compare($compare->getDateRange());
            }
        }

        $chart->range($stats->getDateRange())
            ->categoryMode($request->mode())
            ->title($stats->getName())
            ->cache((bool) config('stats.cache.pages', false));

        if ($request->mode() === CategoricalModes::SPECIFIC) {
            $chart->categories($request->categoricalValues());
        }

        return $chart;
    }

    /**
     * @param  array<string, string>  $metrics
     * @return array<string, mixed>
     */
    protected function viewData(Request $request, CategoricalStatsForm $form, ?Chart $chart, CategoricalStatsRequest $stats_request, array $metrics): array
    {
        $inertia = $this->views()->isInertia();

        return [
            'form' => $inertia ? $form->render('json') : $form,
            'chart' => $chart?->toArray(),
            'export' => $chart ? [
                'url' => route(StatsRoutes::namePrefix($request) . '.categorical.export', $request->route()?->parameters() ?? []),
                'params' => $stats_request->all(),
                'token' => $inertia ? csrf_token() : null,
            ] : null,
            'strings' => $this->pageStrings(__('Categorical Stats')),
            'layout' => config('stats.layout', 'layouts.app'),
            'layout_section' => config('stats.layout_section', 'content'),
            ...($inertia ? ['errors' => $this->errorMessages()] : []),
        ];
    }

    public function export(CategoricalStatsRequest $request): StreamedResponse
    {
        return $this->exportCategoricalStats($request, $this->filters($request));
    }
}
