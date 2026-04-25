# Routes, Export & Blade Component

## API Route (JSON)

Register in `routes/api.php`. The `/api` prefix is added automatically — do not include it in the URL.

```php
use Javaabu\Stats\TimeSeriesStats;

// Signature: registerApiRoute(string $url, string $name, array $middleware)
TimeSeriesStats::registerApiRoute(
    '/stats/time-series',                         // URL (no /api prefix)
    'stats.time-series.index',                    // Route name
    ['auth:sanctum', 'stats.view-time-series']    // Middleware
);
```

**Query parameters accepted:** `metric`, `mode`, `date_range`, `date_from`, `date_to`, `format`, `filters`.

## Admin Routes (Web + Export)

```php
// Signature: registerRoutes(string $url, string $index_name, string $export_name, array $middleware)
TimeSeriesStats::registerRoutes(
    '/stats/time-series',          // URL
    'stats.time-series.index',     // GET route name (view)
    'stats.time-series.export',    // POST route name (CSV export)
    ['auth', 'stats.view-time-series']
);
```

## CSV Export

Use `ExportsTimeSeriesStats` trait in a controller:

```php
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;

class StatsController extends Controller
{
    use ExportsTimeSeriesStats;

    public function export(TimeSeriesStatsRequest $request)
    {
        return $this->exportStats($request);
    }
}
```

With pre-applied filters (e.g., model-scoped):

```php
public function export(TimeSeriesStatsRequest $request)
{
    return $this->exportStats($request, ['customer' => $request->route('customer')], 'Customer Stats');
}
```

## Blade Component

```blade
<x-stats::time-series
    :url="route('stats.time-series.export')"
    :api-url="route('api.stats.time-series.index')"
    :filters="['customer' => $customer->id]"
    :user="auth()->user()"
    :metrics="$metrics"
    framework="material-admin-26"
/>
```
