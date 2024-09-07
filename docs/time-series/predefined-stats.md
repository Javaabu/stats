---
title: Predefined stats
sidebar_position: 7
---

This package ships with several different predefined stats that might be useful.

# Available Predefined Stats

## user_signups

Shows the counts for `App\Model\User` signups.

## user_logins

Shows the login counts for `App\Model\User`. Enabled only if `spatie/laravel-activitylog` package is installed.

# Hiding Predefined Stats

If you want to hide the predefined stats from the stats page, you can call the `TimeSeriesStats::excludeDefaultStats()` method in the `boot` method of your `AppServiceProvider`.

```php
use \Javaabu\Stats\TimeSeriesStats;

class AppServiceProvider extends ServiceProvider
{   
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {       
        TimeSeriesStats::excludeDefaultStats();
    }
}
```

If you want to later include the default stats again, for example to change the order of the stats, you can call the `TimeSeriesStats::registerDefaultStats()` method in the `boot` method of your `AppServiceProvider`.


```php
use \Javaabu\Stats\TimeSeriesStats;

class AppServiceProvider extends ServiceProvider
{   
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {       
        TimeSeriesStats::excludeDefaultStats();
        
        TimeSeriesStats::register([           
            'payments_count' => \App\Stats\TimeSeries\PaymentsCountRepository::class,
            'payments_amount' => \App\Stats\TimeSeries\PaymentsAmountRepository::class,            
        ]);
        
        TimeSeriesStats::registerDefaultStats();
    }
}
```

