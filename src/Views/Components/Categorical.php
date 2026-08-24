<?php

namespace Javaabu\Stats\Views\Components;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Http\Controllers\Api\CategoricalStatsApiController;
use Javaabu\Stats\Http\Controllers\Api\CategoryProviderApiController;
use Javaabu\Stats\Http\Controllers\CategoricalStatsController;

class Categorical extends Component
{
    protected string $view = 'categorical-stats._generator';

    /** @var array<string, mixed> */
    public array $filters;

    public string $url;

    public string $apiUrl;

    /** @var array<string, string> */
    public array $metrics;

    /** @var array<string, string> */
    public array $category_urls;

    public ?Authorizable $user;

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, string>  $metrics
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
        $this->url = $url ?: action([CategoricalStatsController::class, 'export']);
        $this->apiUrl = $apiUrl ?: action([CategoricalStatsApiController::class, 'index']);
        $this->metrics = $metrics ?: CategoricalStats::getMetricNames($filters, $this->user);
        $this->category_urls = [];

        foreach (array_keys($this->metrics) as $metric) {
            $category_url = action([CategoryProviderApiController::class, 'index']);
            $this->category_urls[$metric] = add_query_arg([
                'filter' => ['metric' => $metric],
                'filters' => $filters,
            ], $category_url);
        }
    }
}
