<?php

namespace Javaabu\Stats\Http\Controllers\Api;

use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;
use Javaabu\Stats\Support\ExactDateRange;

class CategoricalStatsApiController extends Controller
{
    public function index(CategoricalStatsRequest $request)
    {
        $metric = $request->input('metric');
        $mode = CategoricalModes::from($request->input('mode'));
        $categorical_values = $request->input('categorical_values', []);
        $filters = $request->input('filters', []);
        $range = $request->filled('date_range')
            ? PresetDateRanges::from($request->input('date_range'))
            : new ExactDateRange($request->input('date_from'), $request->input('date_to'));
        $compare_range = null;
        $compare_date_range = 'custom';

        if ($request->filled('compare_date_range')) {
            $compare_range = PresetDateRanges::from($request->input('compare_date_range'));
            $compare_date_range = $compare_range->value;
        } elseif ($request->filled('compare_date_from')) {
            $compare_range = new ExactDateRange(
                $request->input('compare_date_from'),
                $request->input('compare_date_to')
            );
        } elseif ($request->input('compare')) {
            $compare_date_range = 'previous';
            $compare_range = $range->getPreviousDateRange();
        }

        $stats = CategoricalStats::createFromMetric($metric, $range, $filters);
        $compare = $compare_range ? CategoricalStats::createFromMetric($metric, $compare_range, $filters) : null;
        $format = $request->input('format', 'default');
        $result = CategoricalStats::createFromFormat($format)
            ->format($stats, $compare, $mode, $categorical_values);
        $metric_name = $stats->getName();
        $aggregate_field = $stats->getAggregateFieldName();
        $aggregate_field_label = $stats->getAggregateFieldLabel();
        $date_range = $stats->getDateRange()->getName();
        $date_from = $stats->getDateFrom()->toDateTimeString();
        $date_to = $stats->getDateTo()->toDateTimeString();
        $date_range_title = __('Primary Period - :date_from - :date_to', [
            'date_from' => $stats->getDateFrom()->isoFormat('MMM D, YYYY'),
            'date_to' => $stats->getDateTo()->isoFormat('MMM D, YYYY'),
        ]);

        if ($compare) {
            $compare_date_from = $compare->getDateFrom()->toDateTimeString();
            $compare_date_to = $compare->getDateTo()->toDateTimeString();
            $compare_date_range_title = __('Comparison Period - :date_from - :date_to', [
                'date_from' => $compare->getDateFrom()->isoFormat('MMM D, YYYY'),
                'date_to' => $compare->getDateTo()->isoFormat('MMM D, YYYY'),
            ]);
        } else {
            $compare_date_range = null;
            $compare_date_from = null;
            $compare_date_to = null;
            $compare_date_range_title = null;
        }

        return response()->json(compact(
            'metric',
            'metric_name',
            'mode',
            'aggregate_field',
            'aggregate_field_label',
            'date_range_title',
            'date_range',
            'date_from',
            'date_to',
            'compare_date_range_title',
            'compare_date_range',
            'compare_date_from',
            'compare_date_to',
            'format',
            'result',
            'filters'
        ));
    }
}
