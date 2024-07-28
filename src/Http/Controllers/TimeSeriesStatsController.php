<?php

namespace Javaabu\Stats\Http\Controllers;

use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;

class TimeSeriesStatsController extends Controller
{
    use ExportsTimeSeriesStats;

    /**
     * Display the stats form
     */
    public function index()
    {
        return view(config('stats.time_series_stats_view'));
    }

    /**
     * Export the stats
     */
    public function export(TimeSeriesStatsRequest $request)
    {
        return $this->exportStats($request);
    }
}
