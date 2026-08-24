---
title: Category providers
sidebar_position: 3
---

A category provider maps the value stored in `getCategoryField()` to a label displayed in graphs, exports, and the category selector.

Each item has an `id` and `label`:

```php
[
    ['id' => 1, 'label' => 'Retail'],
    ['id' => 2, 'label' => 'Wholesale'],
]
```

Choose a provider based on where your categories are stored.

## Export column titles

Providers supply the default titles for the category ID and category name columns in CSV exports. A custom provider can override:

```php
public function getCategoricalStatsCategoryTitle(): string
{
    return __('Customer');
}

public function getCategoricalStatsCategoryIdTitle(): string
{
    return __('Customer Number');
}
```

Eloquent providers derive the category title from the model's morph class when available, otherwise from the model class name. Enum providers use the enum class name. Array, collection, and other in-memory providers default to `Category` and `Category ID`.

The stat repository may override these titles for an individual metric with `getCategoryTitle()` and `getCategoryIdTitle()`.

## Eloquent model providers

Return a model class when the category ID is the model's primary key:

```php
use App\Models\Customer;

public function categoryProvider(): mixed
{
    return Customer::class;
}
```

By default, the provider:

- Uses the model's primary key as `id`.
- Uses `admin_link_name` as the label when that accessor exists.
- Otherwise uses the `name` attribute as the label.
- Orders paginated searches by the primary key when no order is configured.
- Derives export column titles from the model's morph class or class name.

### Customize labels and search

Add these methods to the category model when its fields differ from the defaults:

```php
public function getCategoricalStatsLabelField(): string
{
    return 'display_name';
}

public function getCategoricalStatsSearchField(): string
{
    return 'name';
}
```

For multi-column or relationship searches, define a `categoricalStatsSearch` scope:

```php
use Illuminate\Database\Eloquent\Builder;

public function scopeCategoricalStatsSearch(Builder $query, string $search): Builder
{
    return $query->where(function (Builder $query) use ($search) {
        $query->where('first_name', 'like', "%{$search}%")
            ->orWhere('last_name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%");
    });
}
```

If `categoricalStatsSearch` is not defined, the provider uses an existing `search` scope. If neither scope exists, it applies a `LIKE` condition to `getCategoricalStatsSearchField()`.

### Restrict the available models

Return an Eloquent query to limit the categories users can select:

```php
public function categoryProvider(): mixed
{
    return Customer::query()
        ->where('is_active', true)
        ->where('company_id', auth()->user()->company_id);
}
```

The same query is used when resolving labels and serving paginated search results.

### Configure ordering

For straightforward ordering, create an `EloquentCategoryProvider` and select a field and direction:

```php
use Javaabu\Stats\CategoryProviders\EloquentCategoryProvider;

public function categoryProvider(): mixed
{
    return (new EloquentCategoryProvider(Customer::class))
        ->sortCategoricalStatsItemsBy('name', 'asc');
}
```

For multiple order clauses, return an ordered query:

```php
public function categoryProvider(): mixed
{
    return Customer::query()
        ->orderByDesc('priority')
        ->orderBy('name');
}
```

You can also configure the query through a callback:

```php
return (new EloquentCategoryProvider(Customer::class))
    ->sortCategoricalStatsItemsUsing(
        fn ($query) => $query->orderByRaw('priority is null')->orderBy('priority')
    );
```

### Use the provider trait on a model

A model can implement `CategoryProvider` directly with `IsCategoricalStatsProvider`:

```php
use Javaabu\Stats\Concerns\IsCategoricalStatsProvider;
use Javaabu\Stats\Contracts\CategoryProvider;

class Customer extends Model implements CategoryProvider
{
    use IsCategoricalStatsProvider;
}
```

Return a configured model instance from the stat when you want to use the trait's sorting methods:

```php
return (new Customer)
    ->sortCategoricalStatsItemsBy('name');
```

## Enum providers

Return a native enum class:

```php
public function categoryProvider(): mixed
{
    return PaymentStatus::class;
}
```

Backed enum values become IDs. The label is resolved from `getLabel()`, then `label()`, and finally the title-cased case name.

To configure ordering, wrap the enum:

```php
use Javaabu\Stats\CategoryProviders\EnumCategoryProvider;

return (new EnumCategoryProvider(PaymentStatus::class))
    ->sortCategoricalStatsItemsBy('label', 'asc');
```

## Array and collection providers

Return an associative array when categories are already available in memory:

```php
public function categoryProvider(): mixed
{
    return [
        'card' => 'Card',
        'cash' => 'Cash',
    ];
}
```

Arrays and collections can also contain explicit items:

```php
return collect([
    ['id' => 'card', 'label' => 'Card'],
    ['id' => 'cash', 'label' => 'Cash'],
]);
```

Use `ArrayCategoryProvider` for field or callback sorting:

```php
use Javaabu\Stats\CategoryProviders\ArrayCategoryProvider;

return (new ArrayCategoryProvider($categories))
    ->sortCategoricalStatsItemsBy('label', 'desc');
```

A custom comparator receives two normalized items:

```php
return (new ArrayCategoryProvider($categories))
    ->sortCategoricalStatsItemsUsing(
        fn (array $left, array $right) =>
            strlen($left['label']) <=> strlen($right['label'])
    );
```

Array and collection providers search and paginate after loading their items into memory. Prefer an Eloquent or custom data-source provider for large category sets.

## Custom providers

Implement `CategoryProvider` when categories come from another data source. For an in-memory source, `HasCategoricalStatsItems` provides normalization, searching, sorting, and pagination:

```php
use Javaabu\Stats\Concerns\HasCategoricalStatsItems;
use Javaabu\Stats\Contracts\CategoryProvider;

class ChannelProvider implements CategoryProvider
{
    use HasCategoricalStatsItems;

    protected function categoricalStatsItems(): iterable
    {
        return config('channels');
    }
}
```

Implement the contract methods yourself when the underlying service should perform searching or pagination.

## Use the category search endpoint

The Select2 component calls:

```text
GET /stats/categorical/categories
    ?filter[metric]=payments_by_customer
    &filter[search]=acme
    &page=2
```

Optional parameters are:

- `per_page`, from 1 to 100.
- `filters`, for model-specific stats.

The response is a standard Laravel paginator whose `data` entries contain `id` and `label`. The requested metric must be registered, visible to the authenticated user, and compatible with the supplied filters.



