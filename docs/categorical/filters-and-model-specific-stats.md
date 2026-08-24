---
title: Filters and model-specific stats
sidebar_position: 4
---

Filters let one categorical repository serve both a global stats page and pages scoped to a specific model, tenant, status, or other application value.

## Define allowed filters

Return the filters accepted by the repository from `allowedFilters()`:

```php
use Javaabu\Stats\Filters\StatsFilter;

public function allowedFilters(): array
{
    return [
        StatsFilter::exact('customer', 'payments.customer_id'),
        StatsFilter::scope('status'),
    ];
}
```

The keys sent by the page or API must match the filter names. Filters are applied to the primary result, comparison result, graph, and export.

Only repositories that allow every supplied filter are included in the metric selector:

```php
use Javaabu\Stats\CategoricalStats;

$metrics = CategoricalStats::getMetricNames(
    ['customer' => $customer->id],
    auth()->user(),
);
```

Use `allowedMetrics()` instead when you only need a list of metric identifiers.

## Add model-specific routes

Create GET and POST routes for the page and export:

```php
use App\Http\Controllers\Admin\CustomersController;

Route::get('customers/{customer}/categorical-stats', [CustomersController::class, 'categoricalStats'])
    ->name('admin.customers.categorical-stats');

Route::post('customers/{customer}/categorical-stats', [CustomersController::class, 'categoricalStatsExport'])
    ->name('admin.customers.categorical-stats.export');
```

## Add controller actions

Use `ExportsCategoricalStats` for the CSV response:

```php
use App\Models\Customer;
use Javaabu\Stats\Concerns\ExportsCategoricalStats;
use Javaabu\Stats\Http\Requests\CategoricalStatsRequest;

class CustomersController extends Controller
{
    use ExportsCategoricalStats;

    protected function categoricalStatsFilters(Customer $customer): array
    {
        return [
            'customer' => $customer->id,
        ];
    }

    public function categoricalStats(Customer $customer)
    {
        $this->authorize('viewStats', $customer);

        return view('admin.customers.categorical-stats', [
            'customer' => $customer,
            'filters' => $this->categoricalStatsFilters($customer),
        ]);
    }

    public function categoricalStatsExport(
        Customer $customer,
        CategoricalStatsRequest $request
    ) {
        $this->authorize('viewStats', $customer);

        return $this->exportCategoricalStats(
            $request,
            $this->categoricalStatsFilters($customer),
            $customer->name.' Customer',
        );
    }
}
```

The filters passed by the controller are merged with filters submitted by the component. The export helper validates that the selected metric is visible and supports the fixed filters.

## Render the component

```bladehtml
<x-stats::categorical
    :url="route('admin.customers.categorical-stats.export', $customer)"
    :filters="$filters" />
```

Supported attributes:

- `url`: form action for CSV exports. Defaults to the export route registered by `CategoricalStats::registerRoutes()`.
- `api-url`: endpoint used to generate graph data. Defaults to the graph route registered by `CategoricalStats::registerApiRoutes()`.
- `filters`: associative array of fixed filters.
- `metrics`: optional metric-to-label map. By default, the component loads authorized metrics compatible with `filters`.
- `user`: user used for metric authorization. Defaults to the authenticated user.
- `framework`: form framework passed to the base stats component.

The component forwards `filters` to both graph requests and category-search requests. Changing the selected metric updates the category selector to search that metric's provider.

## Supply a custom metric list

Pass a metric map when the page should show only a subset:

```bladehtml
<x-stats::categorical
    :filters="$filters"
    :metrics="[
        'payments_by_customer' => __('Payments'),
        'payment_amounts_by_customer' => __('Payment Amounts'),
    ]" />
```

Only include metrics that are registered and authorized for the selected user and filters.


