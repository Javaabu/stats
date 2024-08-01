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

If you want to hide the predefined stats from the stats page, you can set the `merge` option to `false` when registering your stats.

```php
use \Javaabu\Stats\TimeSeriesStats;

class AppServiceProvider extends ServiceProvider
{   
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {       
        TimeSeriesStats::register([           
            'payments_count' => \App\Stats\TimeSeries\PaymentsCountRepository::class,
            'payments_amount' => \App\Stats\TimeSeries\PaymentsAmountRepository::class,            
        ], false);
    }
}
```
