---
title: Defining time series stats
sidebar_position: 3
---

To define a time series stat, you have to provide the query for each of the available time series modes, and a query to get the total for the full date range.
Luckily, this package makes the process easy for you by providing a set of abstract Stat Repository classes that you can extend to define your stat.

# Aggregate Stats

You can define an Aggregate Stat like so:

```php
<?php
namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\AggregateStatsRepository;

class PaymentsCountRepository extends AggregateStatsRepository
{
    public function query(): Builder
    {   // this is the base query for your stat
        return Payment::query();
    }
    
    public function getAggregateSql(): string
    {
        // this is the SQL statement for your aggregate field
        return 'count(*) as '.$this->getAggregateFieldName();
    }

    public function getTable(): string
    {
        // this is the base table for your query
        return 'payments';
    }

    public function getAggregateFieldName(): string
    {
        // this is the name of the aggregate field,
        // which will be also used to generate the 
        // legend for the stats graph
        return 'transactions';
    }
}
```

# Registering your stat

Once your stat class is defined, you can register it so that the stat will appear in the stat graph. You can register stats, by calling the `TimeSeriesStats::register()` method in your `AppServiceProvider`'s `boot` method. When registering the stat, you should provide a unique metric name for each stat.

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
        ]);
    }
}
```

# Modifying the date field

By default, the `created_at` field is used to determine the date when generating the results. You can modify this by overriding the `getDateFieldName` method of your stat class.

```php
...
class PaymentsCountRepository extends AggregateStatsRepository
{
    public function getDateFieldName(): string
    {
        return 'paid_at';
    }
...
```

# Modifying the stat name

By default, the name displayed for the stat in the stats page is generated using the name of the stat class. To customise this name, you can override the `getName` method of your stat class.

```php
...
class PaymentsCountRepository extends AggregateStatsRepository
{
    public function getName(): string
    {
        return __('Successful Payment Transactions');
    }
...
```

# Modifying who can view the stat

By default, a stat can be viewed by a user with the `view_stats` permission. To modify this, you can override the `canView` method of your stat class.

```php
...
class PaymentsCountRepository extends AggregateStatsRepository
{
    public function canView(?Authorizable $user = null): bool
    {
        return $user && $user->can('viewStats', Payment::class);
    }
...
```

# Count Stats

`CountStatsRepository` is an abstract stat class that extends the `AggregateStatsRepository` class. Count Stats can be used to easily display the count of some value over a given time period. You can define a count stat like so:

```php
<?php
namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCountRepository extends CountStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->paid();
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getAggregateFieldName(): string
    {
        return 'transactions';
    }
}
```

The above example will show the count of `paid` payments over time.

## Using artisan to generate a count stat 

Instead of manually creating a count stat class, you can use the provided `stats:time-series` command to generate a count stat for a given model.

```bash
php artisan stats:time-series PaymentsCount App\Models\Payment --type=count
```

The first argument is the name of your stat class and the 2nd argument is the model you're generating the stat for. This will create a `PaymentsCount.php` file inside the `App\Stats\TimeSeries` directory. It will also register the stat in your `AppServiceProvider`. For the automatic stat registration to work, you should already have a call to `TimeSeriesStats::register` in your `AppServiceProvider`.

Note that you for count stats, you can ommit the `--type=count` as `count` is the default option for the command.

```bash
php artisan stats:time-series PaymentsCount App\Models\Payment
```

Also, you can ommit the `App\Models` namespace for models in the default Models directory.

```bash
php artisan stats:time-series PaymentsCount Payment
```

Alternatively, you can use the model morph name as well.

```bash
php artisan stats:time-series PaymentsCount payment
```

# Sum Stats

`SumStatsRepository` is an abstract stat class that extends the `AggregateStatsRepository` class. Sum Stats can be used to easily display the sum of some value over a given time period. You can define a sum stat like so:

```php
<?php
namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Filters\StatsFilter;
use Javaabu\Stats\Repositories\TimeSeries\SumStatsRepository;

class PaymentAmountsRepository extends SumStatsRepository
{

    public function query(): Builder
    {
        return Payment::query()->paid();
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getFieldToSum(): string
    {
        return 'amount';
    }

    public function getAggregateFieldName(): string
    {
        return 'amount_received_mvr';
    }
}
```

The above example will show the sum of the `amount` for `paid` payments over time.

## Using artisan to generate a sum stat 

Instead of manually creating a sum stat class, you can use the provided `stats:time-series` command to generate a sum stat for a given model.

```bash
php artisan stats:time-series PaymentAmounts payment --type=sum
```
