<?php

namespace Javaabu\Stats\Concerns;

use Illuminate\Support\Facades\Gate;
use Javaabu\Stats\Exports\TimeSeriesStatsExport;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;
use Javaabu\Stats\Support\ExportFileName;
use Javaabu\Stats\TimeSeriesStats;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a time series stat as CSV, for controllers
 */
trait ExportsTimeSeriesStats
{
    /**
     * @param  array<string, mixed>  $filters  extra fixed filters, e.g. the model of a model-specific page
     */
    public function exportStats(TimeSeriesStatsRequest $request, array $filters = [], string $export_title = ''): StreamedResponse
    {
        $metric = $request->metric();

        abort_unless(in_array($metric, TimeSeriesStats::allowedMetrics(array_merge($request->fixedFilters(), $filters), $request->statsUser()), true), 403);

        $stats = $request->stats();
        $compare = $request->compareStats();

        if ($filters) {
            $stats->setFilters(array_merge($stats->getFilters(), $filters));
            $compare?->setFilters(array_merge($compare->getFilters(), $filters));
        }

        $user = $request->statsUser();

        abort_unless($user && Gate::forUser($user)->allows('export', $stats), 403);

        $exporter = new TimeSeriesStatsExport($request->mode(), $stats, $compare);

        return $exporter->download(ExportFileName::make($exporter->getReportTitle(), $stats, $compare, $export_title));
    }
}
