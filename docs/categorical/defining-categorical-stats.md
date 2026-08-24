---
title: Defining categorical stats
sidebar_position: 2
---

A categorical stat repository supplies the records to aggregate, the field to group, the category labels, and the date column used by date-range filters.

## Generate a stat

The generator creates the repository and adds it to an existing `CategoricalStats::register()` map in `AppServiceProvider`. It verifies the generated metric-to-class mapping before reporting that registration succeeded. If the provider cannot be updated automatically, the command prints the import and registration snippet to add manually.

```bash
php artisan stats:categorical PaymentsByCustomer Payment Customer customer_id
```

The arguments are:

1. `name`: the stat class name.
2. `model`: the model used for the base query.
3. `category_model`: the model used as the category provider.
4. `category_id_field`: the category ID field on the base query model.

The command creates a count stat by default. Use `--type=sum` for a sum stat:

```bash
php artisan stats:categorical PaymentAmountsByCustomer \
    Payment Customer customer_id \
    --type=sum
```

You may use fully qualified model classes, model names from `App\Models`, or registered morph names. An unqualified category field such as `customer_id` is generated as `payments.customer_id`. Pass an already-qualified field when the query uses a join or alias.

Additional options:

- `--force` or `-f` overwrites the generated file.
- `--path` or `-p` changes the output directory.
- `--type` or `-t` accepts `count` or `sum`.

The generated date field is the base model's qualified `created_at` column. Update `getDateField()` if the stat should use another timestamp. For a sum stat, also complete `getFieldToSum()`.

The generated `getCategoryFieldAlias()` returns the category model's morph alias when the model is present in Laravel's morph map. It returns `category` when no morph alias is registered. This name is used for the category ID column in grouped query results.

## Define a count stat manually

Extend `CountCategoricalStatsRepository`:

```php
<?php

namespace App\Stats\Categorical;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\Categorical\CountCategoricalStatsRepository;

class PaymentsByCustomer extends CountCategoricalStatsRepository
{
    public function query(): Builder
    {
        return Payment::query()->whereNotNull('paid_at');
    }

    public function categoryProvider(): mixed
    {
        return Customer::class;
    }

    public function getCategoryField(): string
    {
        return 'payments.customer_id';
    }

    public function getCategoryFieldAlias(): string
    {
        return 'customer';
    }

    public function getDateField(): string
    {
        return 'payments.paid_at';
    }

    public function getAggregateFieldName(): string
    {
        return 'count';
    }

    public function canView(?Authorizable $user = null): bool
    {
        return $user?->can('view_payment_stats') ?? false;
    }
}
```

`query()` may contain joins, scopes, or base constraints. `getCategoryField()` must return the database field whose values match the IDs supplied by `categoryProvider()`. It may also return a trusted SQL expression, such as `COALESCE(payments.customer_id, 0)` or a `CASE` expression; the repository uses the expression for selecting, grouping, and ordering. Do not build this value from request or other user-controlled input. `getCategoryFieldAlias()` controls the category ID attribute on grouped and formatted results and defaults to `category`.

The corresponding category-name attribute defaults to `<category field alias>_name`. Override it independently when needed:

```php
public function getCategoryNameFieldAlias(): string
{
    return 'customer_name';
}
```

CSV headings default to the titles supplied by the category provider. Override them for a specific repository with:

```php
public function getCategoryTitle(): string
{
    return __('Customer');
}

public function getCategoryIdTitle(): string
{
    return __('Customer Number');
}
```

`getAggregateFieldName()` is used as the result key and to generate the default translated label. Override `getAggregateFieldLabel()` when you need a custom label.

## Define a sum stat

Extend `SumCategoricalStatsRepository` and return the numeric field from `getFieldToSum()`:

```php
use Javaabu\Stats\Repositories\Categorical\SumCategoricalStatsRepository;

class PaymentAmountsByCustomer extends SumCategoricalStatsRepository
{
    // query(), categoryProvider(), getCategoryField(), getDateField(),
    // and canView() are the same as the count example.

    public function getFieldToSum(): string
    {
        return 'payments.amount';
    }

    public function getAggregateFieldName(): string
    {
        return 'amount';
    }
}
```

For another SQL aggregate, extend `AbstractCategoricalStatsRepository` and implement `getAggregateSql()`.

## Register the stat

Register the repository using a stable metric name:

```php
use App\Stats\Categorical\PaymentsByCustomer;
use Javaabu\Stats\CategoricalStats;

CategoricalStats::register([
    'payments_by_customer' => PaymentsByCustomer::class,
]);
```

## Use a stat in PHP

Create the repository from its metric and a date range:

```php
use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Enums\CategoricalModes;
use Javaabu\Stats\Enums\PresetDateRanges;

$stat = CategoricalStats::createFromMetric(
    'payments_by_customer',
    PresetDateRanges::THIS_MONTH,
);

$rows = $stat->results();

$formatted = $stat->format(
    'default',
    mode: CategoricalModes::ALL,
);
```

The available category modes are:

- `NON_EMPTY`: categories present in the primary or comparison results.
- `ALL`: every item from the provider, with missing values filled with zero.
- `SPECIFIC`: only the IDs supplied in `categorical_values`.

Built-in formats are `default`, `combined`, and `chartjs`. Register additional formatter classes with `CategoricalStats::registerFormatters()`.
