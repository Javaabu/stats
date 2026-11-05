<?php

namespace Javaabu\Stats\Tests\TestSupport\Http;

use Illuminate\Http\Request;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;

/**
 * A model-specific stats page: every stat is filtered to one user
 */
class IslandStatsController extends TimeSeriesStatsController
{
    protected function filters(Request $request): array
    {
        return ['user' => (int) $request->query('owner', 0)];
    }
}
