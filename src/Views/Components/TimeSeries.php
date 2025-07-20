<?php

namespace Javaabu\Stats\Views\Components;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\Stats\Http\Controllers\Api\TimeSeriesStatsApiController;
use Javaabu\Stats\Http\Controllers\TimeSeriesStatsController;
use Javaabu\Stats\TimeSeriesStats;

class TimeSeries extends Component
{
    protected string $view = 'time-series-stats._generator';

    public array $filters;
    public string $url;
    public string $apiUrl;
    public array $metrics;
    public ?Authorizable $user;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(
        string $url = '',
        string $apiUrl = '',
        array $filters = [],
        array $metrics = [],
        ?Authorizable $user = null,
        string $framework = ''
    ) {
        parent::__construct($framework);

        $this->filters = $filters;
        $this->user = $user ?: auth()->user();
        $this->url = $url ?: action([TimeSeriesStatsController::class, 'export']);
        $this->apiUrl = $apiUrl ?: action([TimeSeriesStatsApiController::class, 'index']);
        $this->metrics = $metrics ?: TimeSeriesStats::getMetricNames($this->filters, $this->user);
    }
}
