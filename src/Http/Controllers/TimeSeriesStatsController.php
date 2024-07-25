<?php namespace Javaabu\Stats\Http\Controllers;

use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;

class TimeSeriesStatsController extends Controller
{
    use ExportsTimeSeriesStats;

    /**
     * Display a listing of the resource.
     *
     * @return string
     */
    public function index()
    {
        return view('admin.stats.index');
    }

    /**
     * Export the stats
     */
    public function export(TimeSeriesStatsRequest $request)
    {
        return $this->exportStats($request);
    }
}
