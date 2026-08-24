<?php

namespace Javaabu\Stats\Http\Controllers\Api;

use Illuminate\Http\Request;
use Javaabu\Helpers\Http\Controllers\Controller;
use Javaabu\Stats\CategoricalStats;

class CategoryProviderApiController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'filter' => ['required', 'array'],
            'filter.metric' => ['required', 'string'],
            'filter.search' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'filters' => ['array'],
        ]);

        $metric = $validated['filter']['metric'];
        $filters = $request->input('filters', []);

        abort_unless(
            in_array($metric, CategoricalStats::allowedMetrics($filters, $request->user()), true),
            403,
            'Cannot view this categorical stat.'
        );

        $search = $validated['filter']['search'] ?? null;
        $per_page = $validated['per_page'] ?? CategoricalStats::categoricalItemsPerPage();
        $provider = CategoricalStats::createFromMetric($metric, filters: $filters)->getCategoryProvider();

        return response()->json($provider->searchCategoricalStatsItems($search, $per_page));
    }
}
