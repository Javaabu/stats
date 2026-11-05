<?php

namespace Javaabu\Stats\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Javaabu\Stats\Contracts\InteractsWithDateRange;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\TimeSeriesStats;

/**
 * @group Stats
 *
 * Endpoints for generating statistics.
 */
class TimeSeriesStatsApiController extends Controller
{
    /**
     * Time series stats
     *
     * Generate time series stats for the given metric in the given date range and comparison date range. Supports outputs in multiple formats.
     */
    public function index(TimeSeriesStatsRequest $request): JsonResponse
    {
        abort_unless(TimeSeriesStats::canViewAny($request->statsUser()), 403);

        $mode = $request->mode();
        $stats = $request->stats();
        $compare = $request->compareStats();
        $format = $request->input('format', 'default');

        $result = TimeSeriesStats::createFromFormat($format)->format($mode, $stats, $compare);

        return new JsonResponse([
            'metric' => $request->metric(),
            'metric_name' => $stats->getName(),
            'mode' => $mode,
            'aggregate_field' => $stats->getAggregateFieldName(),
            'aggregate_field_label' => $stats->getAggregateFieldLabel(),
            'date_range_title' => $this->periodTitle('Primary Period - :date_from - :date_to', $stats),
            'date_range' => $stats->getDateRange()->getName(),
            'date_from' => $stats->getDateFrom()->toDateTimeString(),
            'date_to' => $stats->getDateTo()->toDateTimeString(),
            'compare_date_range_title' => $compare ? $this->periodTitle('Comparison Period - :date_from - :date_to', $compare) : null,
            'compare_date_range' => $compare ? $request->compareDateRangeName() : null,
            'compare_date_from' => $compare?->getDateFrom()->toDateTimeString(),
            'compare_date_to' => $compare?->getDateTo()->toDateTimeString(),
            'format' => $format,
            'result' => $result,
            'filters' => $stats->getFilters(),
        ]);
    }

    protected function periodTitle(string $key, InteractsWithDateRange $stats): string
    {
        return __($key, [
            'date_from' => $stats->getDateFrom()->isoFormat('MMM D, YYYY'),
            'date_to' => $stats->getDateTo()->isoFormat('MMM D, YYYY'),
        ]);
    }
}
