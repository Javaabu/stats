<?php

namespace Javaabu\Stats\Http\Controllers;

use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\Concerns\ExportsCategoricalStats;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;

class CategoricalStatsController extends Controller
{
    use ExportsCategoricalStats;

    public function index()
    {
        return view(config('stats.categorical_stats_view'));
    }

    public function export(CategoricalStatsRequest $request)
    {
        return $this->exportCategoricalStats($request);
    }
}
