---
title: Category providers
sidebar_position: 3
---

A category provider turns the values in a categorical stat's `getCategoryField()` into labels for charts, CSV files and the category picker. Every provider gives items with an `id` and a `label`:

```php
[
    ['id' => 12, 'label' => 'Male'],
    ['id' => 15, 'label' => 'Hulhumale'],
]
```

Return the source from the stat's `categoryProvider()`. The package wraps it in the right provider:

| You return | Provider |
| --- | --- |
| A model class, a model or an Eloquent query | `EloquentCategoryProvider` |
| An enum class | `EnumCategoryProvider` |
| An array or a collection | `ArrayCategoryProvider` |
| A `CategoryProvider` instance, or the class of one | Used as it is (a class is made with `new`) |

Anything else throws an `InvalidArgumentException`.

# Models

Return the model class when the category id is the model's primary key:

```php
use App\Models\Island;

public function categoryProvider(): mixed
{
    return Island::class;
}
```

By default the provider:

- uses the primary key as the `id`,
- uses `admin_link_name` as the label when the model has that accessor, else the `name` attribute,
- searches the label column with `LIKE` (`name` when the label is `admin_link_name`),
- orders by the primary key,
- titles the CSV columns from the model's morph alias, or its class name: `Island` and `Island ID`.

## Limit the categories

Return a query to limit which models can be categories. The same query is used for labels and for the category search:

```php
public function categoryProvider(): mixed
{
    return Island::query()
        ->where('is_inhabited', true)
        ->where('atoll_id', auth()->user()->atoll_id);
}
```

## Labels, search, order and titles on the model

The provider looks for these methods and scopes on the model, so the model decides how it appears in every stat:

| Model method or scope | What |
| --- | --- |
| `getCategoricalStatsLabelField(): string` | The attribute used as the label |
| `getCategoricalStatsSearchField(): string` | The column the fallback `LIKE` search uses |
| `getCategoricalStatsSortField(): string` | The column to order by |
| `getCategoricalStatsSortDirection(): string` | `asc` or `desc`. Default: `asc` |
| `getCategoricalStatsCategoryTitle(): string` | The CSV title of the category name column |
| `getCategoricalStatsCategoryIdTitle(): string` | The CSV title of the category id column |
| `scopeCategoricalStatsSearch(Builder $query, string $search): Builder` | The search. Preferred over the others |
| `scopeSearch(Builder $query, string $search): Builder` | Used when there is no `categoricalStatsSearch` scope |
| `scopeCategoricalStatsSort(Builder $query): Builder` | Custom order. Preferred over the sort field methods |

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Island extends Model
{
    public function getCategoricalStatsLabelField(): string
    {
        return 'name_dv';
    }

    public function getCategoricalStatsSortField(): string
    {
        return 'name_dv';
    }

    public function getCategoricalStatsCategoryTitle(): string
    {
        return __('ރަށް');
    }

    public function scopeCategoricalStatsSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('name_dv', 'like', "%{$search}%");
        });
    }
}
```

The label is, in order: `getCategoricalStatsLabelField()`, the `admin_link_name` accessor, the `name` attribute.

The search is, in order: the `categoricalStatsSearch` scope, the `search` scope, a `LIKE` on `getCategoricalStatsSearchField()` (or the label column).

Without the title methods, the category title is the model's morph alias or class name, headlined and translated, and the id title is `:category ID`.

## Order

The order is chosen in this sequence:

1. A callback given to `sortCategoricalStatsItemsUsing()`. It gets the query; use `reorder()` in it to replace an existing order.
2. A field given to `sortCategoricalStatsItemsBy()`. It replaces any existing order.
3. `orderBy` clauses on the query you return from `categoryProvider()`.
4. The model's `categoricalStatsSort` scope.
5. The model's `getCategoricalStatsSortField()` and `getCategoricalStatsSortDirection()`.
6. The primary key.

For one stat, order through the query or a configured provider:

```php
use Javaabu\Stats\CategoryProviders\EloquentCategoryProvider;

public function categoryProvider(): mixed
{
    return (new EloquentCategoryProvider(Island::class))
        ->sortCategoricalStatsItemsBy('name', 'asc');
}
```

```php
return Island::query()->orderByDesc('population')->orderBy('name');
```

```php
return (new EloquentCategoryProvider(Island::class))
    ->sortCategoricalStatsItemsUsing(fn (Builder $query) => $query->orderByRaw('code is null')->orderBy('code'));
```

`EloquentCategoryProvider`'s constructor also takes the columns: `new EloquentCategoryProvider(Island::class, id_field: 'code', label_field: 'name_dv', sort_field: 'name_dv', sort_direction: 'asc')`, and a `sort_query` closure.

## The provider trait

A model can be its own provider with `IsCategoricalStatsProvider`:

```php
use Javaabu\Stats\Concerns\IsCategoricalStatsProvider;
use Javaabu\Stats\Contracts\CategoryProvider;

class Island extends Model implements CategoryProvider
{
    use IsCategoricalStatsProvider;
}
```

Return a model instance from the stat to use the trait's sort methods: `return (new Island)->sortCategoricalStatsItemsBy('name');`.

| Method | Default |
| --- | --- |
| `getCategoricalStatsIdField(): string` | The primary key |
| `getCategoricalStatsLabelField(): string` | `admin_link_name` when that accessor exists, else `name` |
| `getCategoricalStatsSearchField(): string` | `name` |
| `getCategoricalStatsCategoryTitle(): string` | The morph alias or class name |
| `getCategoricalStatsCategoryIdTitle(): string` | `:category ID` |
| `getCategoricalStatsItems(?array $ids = null): Collection` | The models (those ids only, when given), mapped to items |
| `searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator` | Searched, ordered and paginated items |

The optional sort hooks (`getCategoricalStatsSortField()`, `getCategoricalStatsSortDirection()`, `scopeCategoricalStatsSort()`) work as above.

# Enums

Return the enum class:

```php
use App\Enums\PaymentStatus;

public function categoryProvider(): mixed
{
    return PaymentStatus::class;
}
```

A backed enum's values are the ids; a pure enum's case names are. The label is the case's `getLabel()`, else `label()`, else the headlined case name. The category title is the enum's class name, headlined.

Wrap the enum to order it:

```php
use Javaabu\Stats\CategoryProviders\EnumCategoryProvider;

return (new EnumCategoryProvider(PaymentStatus::class))
    ->sortCategoricalStatsItemsBy('label', 'asc');
```

# Arrays and collections

Return labels keyed by id when the categories are fixed:

```php
public function categoryProvider(): mixed
{
    return [
        'card' => 'ކާޑު',
        'cash' => 'ނަގުދު',
        'transfer' => 'ޓްރާންސްފަރ',
    ];
}
```

or a list of items:

```php
return collect([
    ['id' => 'card', 'label' => 'ކާޑު'],
    ['id' => 'cash', 'label' => 'ނަގުދު'],
]);
```

An item can be an array (`id`, and `label` or `name`), an object (`id` or `value`, and `label`, `name` or `value`), or a label keyed by its id.

Order with `ArrayCategoryProvider`, by a field or with a comparison callback:

```php
use Javaabu\Stats\CategoryProviders\ArrayCategoryProvider;

return (new ArrayCategoryProvider($channels))->sortCategoricalStatsItemsBy('label', 'desc');

return (new ArrayCategoryProvider($channels))
    ->sortCategoricalStatsItemsUsing(fn (array $left, array $right) => mb_strlen($left['label']) <=> mb_strlen($right['label']));
```

Arrays, collections and enums are searched and paginated in memory. Use a model or your own provider for large sets.

Their CSV titles are `Category` and `Category ID`. Override `getCategoryTitle()` and `getCategoryIdTitle()` on the stat to change them.

# Your own provider

Implement `Javaabu\Stats\Contracts\CategoryProvider` for categories from anywhere else. For an in-memory source, `HasCategoricalStatsItems` does the normalising, searching, sorting and paging; you only list the items:

```php
<?php

namespace App\Stats\Providers;

use Javaabu\Stats\Concerns\HasCategoricalStatsItems;
use Javaabu\Stats\Contracts\CategoryProvider;

class PaymentChannels implements CategoryProvider
{
    use HasCategoricalStatsItems;

    protected function categoricalStatsItems(): iterable
    {
        return config('payments.channels');
    }

    public function getCategoricalStatsCategoryTitle(): string
    {
        return __('Channel');
    }
}
```

```php
public function categoryProvider(): mixed
{
    return PaymentChannels::class;
}
```

When an external service should do the searching or paging, implement the contract's methods yourself:

| Method | What |
| --- | --- |
| `getCategoricalStatsItems(?array $ids = null): Collection` | Items (only those ids, when given), in order |
| `searchCategoricalStatsItems(?string $search = null, int $per_page = 15): LengthAwarePaginator` | A page of items matching the search |
| `sortCategoricalStatsItemsBy(string $field, string $direction = 'asc'): static` | Order by a field |
| `sortCategoricalStatsItemsUsing(Closure $callback): static` | Order with a callback |
| `getCategoricalStatsCategoryTitle(): string` | The category name column's title |
| `getCategoricalStatsCategoryIdTitle(): string` | The category id column's title |

# Searching categories

The categorical stats page picks specific categories with a searchable select that calls the categories endpoint. `Route::stats()` and `Route::statsApi()` both register it:

```text
GET /stats/categorical/categories?filter[metric]=payments_by_island&filter[search]=hulhu&page=2
```

| Parameter | What |
| --- | --- |
| `filter[metric]` | Required. The categorical metric |
| `filter[search]` or `search` | The search text |
| `page` | The page |
| `per_page` | Up to 100. Default: `stats.categorical_items_per_page` (15). `0` or `-1` gives 100 |
| `filters[...]` | Fixed filters, for a page of one model's stats |

It answers with a Laravel paginator whose `data` items have `id` and `label`. The metric must be one the user can see, and must allow the `filters`; otherwise it answers `403`.
