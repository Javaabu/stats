<?php

namespace Javaabu\Stats\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Routing\StatsRoutes;

/**
 * Searches the categories of a categorical stat, in the shape javaabu/forms'
 * AJAX selects read: `filter[metric]`, `filter[search]`, `page`, `per_page`
 */
class CategoryProviderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filter' => ['required', 'array'],
            'filter.metric' => ['required', 'string'],
            'filter.search' => ['nullable', 'string'],
            'search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:-1', 'max:100'],
            'filters' => ['nullable', 'array'],
        ]);

        $metric = $validated['filter']['metric'];
        $filters = $request->input('filters', []);
        $user = $request->user(StatsRoutes::guard($request));

        abort_unless(
            in_array($metric, CategoricalStats::allowedMetrics($filters, $user instanceof Authorizable ? $user : null), true),
            403,
            'Cannot view this categorical stat.'
        );

        $search = $validated['filter']['search'] ?? $validated['search'] ?? null;
        $per_page = (int) ($validated['per_page'] ?? CategoricalStats::categoricalItemsPerPage());
        $per_page = $per_page < 1 ? 100 : $per_page;
        $provider = CategoricalStats::createFromMetric($metric, filters: $filters)->getCategoryProvider();

        return new JsonResponse($provider->searchCategoricalStatsItems($search, $per_page)->appends($request->query()));
    }
}
