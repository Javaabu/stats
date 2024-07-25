<?php
namespace Javaabu\Stats\Concerns;

use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Javaabu\Stats\Enums\PresetDateRanges;
use Javaabu\Stats\Enums\TimeSeriesModes;
use Javaabu\Stats\Exports\TimeSeriesStatsExport;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\Support\ExactDateRange;
use Javaabu\Stats\TimeSeriesStats;

trait ExportsTimeSeriesStats
{
    /**
     * Validate stats filters
     * @throws ValidationException
     */
    protected function validateStatsFilters(TimeSeriesStatsRequest $request, array $filters = [])
    {
        if ($filters) {
            $this->validate($request, [
                'metric' => [
                    'required',
                    'string',
                    Rule::in(TimeSeriesStats::allowedMetrics($filters, $request->user())),
                ],
            ]);
        }
    }

    /**
     * Export the stat
     *
     * @param TimeSeriesStatsRequest $request
     * @param array $filters
     * @param string $export_title
     * @return \Illuminate\Http\Response
     * @throws ValidationException
     */
    public function exportStats(TimeSeriesStatsRequest $request, array $filters = [], string $export_title = '')
    {
        $this->validateStatsFilters($request);

        $metric = $request->input('metric');
        $mode = TimeSeriesModes::from($request->input('mode'));

        if ($request->filled('date_range')) {
            $range = PresetDateRanges::from($request->input('date_range'));
        } else {
            $range = new ExactDateRange(
                $request->input('date_from'),
                $request->input('date_to'),
            );
        }

        $compare_range = null;

        if ($request->filled('compare_date_range')) {
            $compare_range = PresetDateRanges::from($request->input('compare_date_range'));
        } elseif ($request->filled('compare_date_from')) {
            $compare_range = new ExactDateRange(
                $request->input('compare_date_from'),
                $request->input('compare_date_to'),
            );
        } elseif ($request->input('compare')) {
            $compare_range = $range->getPreviousDateRange();
        }

        $stats = TimeSeriesStats::createFromMetric($metric, $range, $filters);

        if ($compare_range) {
            $compare = TimeSeriesStats::createFromMetric($metric, $compare_range, $filters);
        } else {
            $compare = null;
        }

        $exporter = new TimeSeriesStatsExport($mode, $stats, $compare);

        $title = $exporter->getReportTitle();

        $document_title = slug_to_title($export_title ?: get_setting('app_name'), '-').
            ' '.$title.' '.
            $stats->formattedDateRange('YYYYMMDD', '-').
            ($compare ? ' '.$compare->formattedDateRange('YYYYMMDD', '-') : '');

        return $exporter->download($document_title.'.csv');
    }
}
