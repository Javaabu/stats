<?php
namespace Javaabu\Stats\Traits;

use Javaabu\Stats\Exports\StatsExport;
use Javaabu\Stats\Formatters\TimeSeries\CombinedStatsFormatter;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\StatsRepository;

trait StatsExportable
{
    /**
     * Validate stats filters
     *
     * @param TimeSeriesStatsRequest $request
     * @param array $filters
     */
    protected function validateStatsFilters(TimeSeriesStatsRequest $request, $filters = [])
    {
        if ($filters) {
            $this->validate($request, [
                'metric' => 'required|string|in:'.implode(',', array_keys(StatsRepository::metricsThatAllowFilters(array_keys($filters)))),
            ]);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param TimeSeriesStatsRequest $request
     * @param array $filters
     * @param string $export_title
     * @return \Illuminate\Http\Response
     */
    public function exportStats(TimeSeriesStatsRequest $request, $filters = [], $export_title = '')
    {
        $this->validateStatsFilters($request);

        $metric = $request->input('metric');
        $mode = $request->input('mode');

        if ($request->filled('date_range')) {
            $range = $request->input('date_range');
        } else {
            $range = [
                $request->input('date_from'),
                $request->input('date_to'),
            ];
        }

        $compare_range = null;

        if ($request->filled('compare_date_range')) {
            $compare_range = $request->input('compare_date_range');
        } elseif ($request->filled('compare_date_from')) {
            $compare_range = [
                $request->input('compare_date_from'),
                $request->input('compare_date_to'),
            ];
        } elseif ($request->input('compare')) {
            $compare_range = StatsRepository::getPreviousDateRange($range);
        }

        $stats = StatsRepository::createFromMetric($metric, $range, $filters);

        if ($compare_range) {
            $compare = StatsRepository::createFromMetric($metric, $compare_range, $filters);
        } else {
            $compare = null;
        }

        $formatter = new CombinedStatsFormatter($stats, $compare);
        $exporter = new StatsExport($formatter, $mode);

        $title = $exporter->getReportTitle();

        $document_title = slug_to_title($export_title ?: get_setting('app_name'), '-').
            ' '.$title.' '.
            $stats->formattedDateRange('Ymd', '-').
            ($compare ? ' '.$compare->formattedDateRange('Ymd', '-') : '');

        return $exporter->download($document_title.'.csv');
    }
}
