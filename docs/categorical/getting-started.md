---
title: Getting started
sidebar_position: 1
---

Categorical stats group records by a category and calculate either a count or sum for each category. For example, you can display payment counts by customer, order totals by status, or signups by source.

## Prerequisites

The included graph view uses Chart.js v4. The category selector uses the Select2 integration provided by `javaabu/forms`. Make Chart.js available at `public/vendors/chart.js/chart.umd.js`, or publish and customize the package views if your application loads it elsewhere.

## Register the routes

Add the categorical API routes to `routes/api.php` inside the middleware group used by your application:

```php
use Javaabu\Stats\CategoricalStats;

Route::middleware(['auth:sanctum'])->group(function () {
    CategoricalStats::registerApiRoutes();
});
```

This registers:

- `GET /stats/categorical` for graph data.
- `GET /stats/categorical/categories` for the paginated category selector.

Add the page and CSV export routes to `routes/web.php` or your admin route file:

```php
use Javaabu\Stats\CategoricalStats;

Route::middleware(['auth'])->group(function () {
    CategoricalStats::registerRoutes();
});
```

This registers:

- `GET /stats/categorical` for the categorical stats page.
- `POST /stats/categorical` for CSV exports.

Pass custom URLs, route names, or middleware when the defaults do not match your application:

```php
CategoricalStats::registerApiRoutes(
    url: '/api/admin/stats/categorical',
    name: 'api.admin.stats.categorical',
    categories_name: 'api.admin.stats.categorical.categories',
    middleware: ['auth:sanctum', 'stats.view-categorical'],
);

CategoricalStats::registerRoutes(
    url: '/admin/stats/categorical',
    index_name: 'admin.stats.categorical',
    export_name: 'admin.stats.categorical.export',
    middleware: ['auth', 'can:view_stats', 'stats.view-categorical'],
);
```

## Register a metric

Register each categorical stat in a service provider, usually `AppServiceProvider::boot()`:

```php
use App\Stats\Categorical\PaymentsByCustomer;
use Javaabu\Stats\CategoricalStats;

CategoricalStats::register([
    'payments_by_customer' => PaymentsByCustomer::class,
]);
```

The array key is the metric sent to the graph, export, and category-search endpoints.

## Render the stats component

The package page renders the component automatically. To place the generator in another view, use:

```bladehtml
<x-stats::categorical />
```

Only metrics authorized for the selected user are displayed. See [Defining categorical stats](./defining-categorical-stats.md) for repository examples and [Filters and model-specific stats](./filters-and-model-specific-stats.md) for embedding filtered stats in another page.

## Configuration

Publish the package configuration when you need to change the defaults:

```bash
php artisan vendor:publish \
    --provider="Javaabu\Stats\StatsServiceProvider" \
    --tag="stats-config"
```

The categorical options in `config/stats.php` are:

```php
'default_categorical_mode' => \Javaabu\Stats\Enums\CategoricalModes::NON_EMPTY,
'categorical_items_per_page' => 15,
'categorical_stats_view' => 'stats::material-admin-26.categorical-stats.index',
```

`categorical_items_per_page` controls the default page size of the category selector API. The API also accepts a `per_page` value up to 100.

Categorical stats share `default_date_range`, `framework`, `default_layout`, and `scripts_stack` with time-series stats.


