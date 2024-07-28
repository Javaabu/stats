<?php namespace Javaabu\Stats\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeriesStatsApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param TimeSeriesStatsRequest $request
     * @return JsonResponse|Response
     */
    public function index(TimeSeriesStatsRequest $request)
    {
        $metric = $request->input('metric');
        $mode = TimeSeriesModes::from($request->input('mode'));
        $filters = $request->input('filters', []);
        $metric_name = TimeSeriesStats::getMetricName($metric);

        if ($request->filled('date_range')) {
            $range = PresetDateRanges::from($request->input('date_range'));
        } else {
            $range = new ExactDateRange(
                $request->input('date_from'),
                $request->input('date_to'),
            );
        }

        $compare_range = null;
        $compare_date_range = 'custom';

        if ($request->filled('compare_date_range')) {
            $compare_range = PresetDateRanges::from($request->input('compare_date_range'));
            $compare_date_range = $compare_range->value;
        } elseif ($request->filled('compare_date_from')) {
            $compare_range = new ExactDateRange(
                $request->input('compare_date_from'),
                $request->input('compare_date_to'),
            );
        } elseif ($request->input('compare')) {
            $compare_date_range = 'previous';
            $compare_range = $range->getPreviousDateRange();
        }

        $stats = TimeSeriesStats::createFromMetric($metric, $range, $filters);
        $date_range = $stats->getDateRange()->getName();
        $date_from = $stats->getDateFrom()->toDateTimeString();
        $date_to = $stats->getDateTo()->toDateTimeString();
        $aggregate_field = $stats->getAggregateFieldName();
        $aggregate_field_label = $stats->getAggregateFieldLabel();

        if ($compare_range) {
            $compare = TimeSeriesStats::createFromMetric($metric, $compare_range, $filters);
            $compare_date_from = $compare->getDateFrom()->toDateTimeString();
            $compare_date_to = $compare->getDateTo()->toDateTimeString();
        } else {
            $compare = null;
            $compare_date_range = null;
            $compare_date_from = null;
            $compare_date_to = null;
        }

        $format = $request->input('format', 'default');
        $formatter = TimeSeriesStats::createFromFormat($format);
        $result = $formatter->format($mode, $stats, $compare);

        return response()->json(
            compact(
                'metric',
                'metric_name',
                'mode',
                'aggregate_field',
                'aggregate_field_label',
                'date_range',
                'date_from',
                'date_to',
                'compare_date_range',
                'compare_date_from',
                'compare_date_to',
                'format',
                'result',
                'filters'
            )
        );
    }
}
