<?php

namespace Javaabu\Stats\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Stats\Charts\TimeSeriesChart;
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Forms\TimeSeriesStatsForm;
use Javaabu\Stats\Http\Controllers\Concerns\RendersStatsPage;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\Routing\StatsRoutes;
use Javaabu\Stats\TimeSeriesStats;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The time series stats page: the form, the chart and a CSV download. Extend
 * it and set `config('stats.controllers.time_series')` (or pass `controllers`
 * to Route::stats()) to change what it does.
 */
class TimeSeriesStatsController extends Controller
{
    use ExportsTimeSeriesStats;
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
        $metrics = TimeSeriesStats::getMetricNames($filters, $user);

        abort_if($metrics === [], 403);

        [$stats_request, $validator] = $this->pageRequest($request, TimeSeriesStatsRequest::class, [
            'metric' => array_key_first($metrics),
            'mode' => TimeSeriesStats::defaultMode()->value,
            'date_range' => TimeSeriesStats::defaultDateRange()->value,
            'variant' => TimeSeriesChart::LINE,
            'filters' => $filters ?: null,
        ]);

        $chart = null;

        if (! $validator->fails()) {
            $chart = $this->chart($stats_request);
        } else {
            $this->shareErrors($validator);
        }

        $form = TimeSeriesStatsForm::make()
            ->with(['metrics' => $metrics, 'filters' => $filters])
            ->model($stats_request->all())
            ->action($request->url());

        return $this->views()->render('time-series.index', $this->viewData($request, $form, $chart, $stats_request, $metrics));
    }

    /**
     * The chart for a valid request
     */
    protected function chart(TimeSeriesStatsRequest $request): TimeSeriesChart
    {
        $stats = $request->stats();
        $chart = TimeSeriesChart::make()
            ->stat($request->metric(), filters: $stats->getFilters())
            ->mode($request->mode())
            ->range($stats->getDateRange())
            ->variant((string) $request->input('variant', TimeSeriesChart::LINE))
            ->title($stats->getName())
            ->cache((bool) config('stats.cache.pages', false));

        foreach ($request->otherMetrics() as $metric) {
            $chart->stat($metric, filters: $request->stats($metric)->getFilters());
        }

        if ($compare = $request->compareStats()) {
            $chart->compare($compare->getDateRange());
        }

        return $chart;
    }

    /**
     * What the Blade view and the Inertia page get
     *
     * @param  array<string, string>  $metrics
     * @return array<string, mixed>
     */
    protected function viewData(Request $request, TimeSeriesStatsForm $form, ?TimeSeriesChart $chart, TimeSeriesStatsRequest $stats_request, array $metrics): array
    {
        $inertia = $this->views()->isInertia();

        return [
            'form' => $inertia ? $form->render('json') : $form,
            'chart' => $chart?->toArray(),
            'export' => $chart ? [
                'url' => route(StatsRoutes::namePrefix($request) . '.time-series.export', $request->route()?->parameters() ?? []),
                'params' => $stats_request->all(),
                'token' => $inertia ? csrf_token() : null,
            ] : null,
            'strings' => $this->pageStrings(__('Time Series Stats')),
            'layout' => config('stats.layout', 'layouts.app'),
            'layout_section' => config('stats.layout_section', 'content'),
            ...($inertia ? ['errors' => $this->errorMessages()] : []),
        ];
    }

    public function export(TimeSeriesStatsRequest $request): StreamedResponse
    {
        return $this->exportStats($request, $this->filters($request));
    }
}
