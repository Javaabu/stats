<?php

namespace Javaabu\Stats\Concerns;

use Illuminate\Validation\Rule;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Exports\CategoricalStatsExport;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;
use Javaabu\Stats\Support\ExactDateRange;

trait ExportsCategoricalStats
{
    /**
     * @param  array<string, mixed>  $filters
     */
    protected function validateCategoricalStatsFilters(CategoricalStatsRequest $request, array $filters = []): void
    {
        if ($filters) {
            $this->validate($request, [
                'metric' => [
                    'required',
                    'string',
                    Rule::in(CategoricalStats::allowedMetrics($filters, $request->user())),
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCategoricalStats(
        CategoricalStatsRequest $request,
        array $filters = [],
        string $export_title = ''
    ) {
        $this->validateCategoricalStatsFilters($request, $filters);
        $filters = array_merge($request->input('filters', []), $filters);
        $metric = $request->input('metric');
        $mode = CategoricalModes::from($request->input('mode'));
        $categorical_values = $request->input('categorical_values', []);
        $range = $request->filled('date_range')
            ? PresetDateRanges::from($request->input('date_range'))
            : new ExactDateRange($request->input('date_from'), $request->input('date_to'));
        $compare_range = null;

        if ($request->filled('compare_date_range')) {
            $compare_range = PresetDateRanges::from($request->input('compare_date_range'));
        } elseif ($request->filled('compare_date_from')) {
            $compare_range = new ExactDateRange(
                $request->input('compare_date_from'),
                $request->input('compare_date_to')
            );
        } elseif ($request->input('compare')) {
            $compare_range = $range->getPreviousDateRange();
        }

        $stats = CategoricalStats::createFromMetric($metric, $range, $filters);
        $compare = $compare_range ? CategoricalStats::createFromMetric($metric, $compare_range, $filters) : null;
        $exporter = new CategoricalStatsExport($stats, $compare, $mode, $categorical_values);
        $document_title = slug_to_title($export_title ?: get_setting('app_name'), '-').' '.
            $exporter->getReportTitle().' '.
            $stats->formattedDateRange('YYYYMMDD', '-').
            ($compare ? ' '.$compare->formattedDateRange('YYYYMMDD', '-') : '');

        return $exporter->download($document_title.'.csv');
    }
}
