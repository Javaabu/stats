---
title: Displaying model specific stats
sidebar_position: 6
---

Often times you might want to display a model specific stats in a nested route of the model. For example, you might want to have a page where you can display the stats for a specific customer. This package provides helpful traits and view components to achieve that.

# Preparing your Model Controller

You can add the `ExportsTimeSeriesStats` trait to your controller and add a `statsFilters`, `stats` and an `statsExport` methods like so:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Javaabu\Stats\Concerns\ExportsTimeSeriesStats;
use Javaabu\Stats\Http\Requests\TimeSeriesStatsRequest;

class CustomersController extends Controller
{   
    use ExportsTimeSeriesStats;

    /**
     * This defines which filters to use when displaying the customer stats
     * The package will automatically look for stats that allow these filters
     * and display only those. Also, the displayed results will be filtered 
     * to these filter values.
     */
    public function statsFilters(Customer $customer): array
    {
        return [
            'customer' => $customer->id,
        ];
    }

    /**
     * This method is used to show the stats page.
     */
    public function stats(Customer $customer)
    {
        // good practice to add some authorization
        // you can modify this as needed
        $this->authorize('viewStats', $customer);

        $filters = $this->statsFilters($customer);

        return view('admin.customers.stats', compact(
            'customer',
            'filters',
        ));
    }

    /**
     * This method is used to export the stats
     */
    public function statsExport(Customer $customer, TimeSeriesStatsRequest $request)
    {
        // good practice to add some authorization
        // you can modify this as needed  
        $this->authorize('viewStats', $customer);

        return $this->exportStats(
                    $request, 
                    $this->statsFilters($customer), 
                    $customer->name.' Customer' // this is a prefix added to the exported file name
                );
    }
...

```

# Setting up your routes

Next add the routes for the controller methods you just created.

```php
...
// inside admin.php route file

/**
* Customers
*/
Route::get('customers/{customer}/stats', [CustomersController::class, 'stats'])->name('customers.stats');
Route::post('customers/{customer}/stats', [CustomersController::class, 'statsExport'])->name('customers.stats.export');
...
```

# Setting up your view

In your view, you can use the `x-stats::time-series` Blade component provided by this package to display the stat graph generator. Below is the code for `admin.customers.stats.blade.php` view file.

```bladehtml
@extends('admin.customers.customers')

@section('content')
    @parent

    <x-stats::time-series :url="route('admin.customers.stats.export', $customer)" :filters="$filters"/>
@endsection
```

The `stats::time-series` component accepts the following attributes:

- **`url`**: The url of the controller endpoint to submit the stat export form. Defaults to the route defined by `TimeSeriesStats::registerRoutes`.
- **`filters`**: Associative array of filter values.
- **`api-url`**: The API url to call for generating the stats graph. Defaults to the route defined by `TimeSeriesStats::registerApiRoute`.
- **`user`**: The user who you want to display the stats for. Defaults to the current user.
- **`framework`**: Which CSS framework to use. Defaults to framework defined in the package config.

# Add links

Now you are all set to display model specific stats. You can add links to the model stats page in your model index page and tab links in your model show view.
