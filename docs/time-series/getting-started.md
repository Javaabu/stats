---
title: Getting started
sidebar_position: 2
---

# JavaScript Prerequisites

Before getting started, you need to ensure the following JS libraries has been installed and copied to the `public/vendors` directory:

- [`chart.js`](https://www.chartjs.org/)
- [`jquery-sparkline`](https://omnipotent.net/jquery.sparkline)
- [`flot`](https://www.flotcharts.org/)
- [`flot.curvedlines`](http://curvedlines.michaelzinsmaier.de/)
- [`flot-orderbars`](https://www.npmjs.com/package/flot-orderbars)

If you don't have these libraries already installed, you can install the packages by running the following command:

```bash
npm install chart.js jquery-sparkline flot flot.curvedlines flot-orderbars --save
```

If using Javaabu's Laravel Skeleton, these packages should already be installed for you. You should check the `material-admin.config.js` file for any missing libraries and add them there. After adding any missing libraries, you can run `npm run material-admin`.

# Setting up permissions

By default, stats can only be viewed by users that have a `view_stats` permission. If you are using [`spatie/laravel-permission`](https://github.com/spatie/laravel-permission), you can seed a permission for `view_stats` and grant the permission to the users you want to be able to view the stats.

It is possible to define specific permissions for each stat you create, which we will cover how to do later.

# Setting up the API Route

For generating the time series stats graph, the data is loaded from an API. For this, you need to register the API routes for the package.

Add the following code to your `api.php` route file. You can place it as a publicly accessible JSON route. The package will automatically add the `stats.view-time-series` middleware which will ensure users will be allowed to view only stats they're authorized to view.

This will add a `GET {api_base_url}/stats/time-series` endpoint.

```php
// inside api.php route file

/**
 * Public routes
 */
Route::group([
    'middleware' => ['oauth.client:read'],
], function () {   
    /**
     * Public JSON routes
     */
    Route::group([
        'middleware' => ['json'],
    ], function () {       

        \Javaabu\Stats\TimeSeriesStats::registerApiRoute();
        
    });
});
```

# Setting up the Admin Routes

For viewing all the stats in one place, the package comes with a page for displaying the interactive stats graph and exporting the generated stats. To setup this page, you need to register the admin routes for the page.

Add the following code to your `admin.php` (if using Javaabu's Laravel Skeleton) or to your `web.php` route file.
This will add a `GET {admin_base_url}/stats/time-series` and a `POST {admin_base_url}/stats/time-series` endpoint.

```php
// inside admin

/**
 * Protected routes
 */
Route::group([
    'middleware' => ['auth:web_admin', 'active:web_admin', 'password-update-not-required:web_admin'],
], function () {

    /**
     * Stats
     */
    \Javaabu\Stats\TimeSeriesStats::registerRoutes();
});

```

# Setting up sidebar

If you're using Javaabu's Laravel Skeleton, you should also add a link to the stats page to your admin sidebar.

```php
// inside AdminSidebar.php
...
MenuItem::make(__('Stats'))
    ->controller(\Javaabu\Stats\Http\Controllers\TimeSeriesStatsController::class)
    ->can('view_stats')
    ->icon('zmdi-trending-up'),
...
```


