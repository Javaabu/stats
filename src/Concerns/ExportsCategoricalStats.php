<?php

namespace Javaabu\Stats\Concerns;

use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Exports\CategoricalStatsExport;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;
use Javaabu\Stats\Support\ExportFileName;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a categorical stat as CSV, for controllers
 */
trait ExportsCategoricalStats
{
    /**
     * @param  array<string, mixed>  $filters  extra fixed filters, e.g. the model of a model-specific page
     */
    public function exportCategoricalStats(CategoricalStatsRequest $request, array $filters = [], string $export_title = ''): StreamedResponse
    {
        $metric = $request->metric();

        abort_unless(in_array($metric, CategoricalStats::allowedMetrics(array_merge($request->fixedFilters(), $filters), $request->statsUser()), true), 403);

        $stats = $request->stats();
        $compare = $request->compareStats();

        if ($filters) {
            $stats->setFilters(array_merge($stats->getFilters(), $filters));
            $compare?->setFilters(array_merge($compare->getFilters(), $filters));
        }

        $exporter = new CategoricalStatsExport($stats, $compare, $request->mode(), $request->categoricalValues());

        return $exporter->download(ExportFileName::make($exporter->getReportTitle(), $stats, $compare, $export_title));
    }
}
