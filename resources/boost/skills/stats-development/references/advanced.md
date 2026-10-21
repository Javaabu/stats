# Advanced Features

## Authorization

Override `canView()` to customize per-stat access control. Default requires `view_stats` permission.

```php
use Illuminate\Contracts\Auth\Access\Authorizable;

public function canView(?Authorizable $user = null): bool
{
    return $user && $user->can('view_order_stats');
}
```

Check if any stat is viewable: `TimeSeriesStats::canViewAny($user)`

## Custom Date Field

Override `getDateFieldName()` to group by a column other than `created_at`:

```php
public function getDateFieldName(): string
{
    return 'completed_at';
}
```

## Login & Signup Repositories

Built-in abstract repos for tracking user auth activity. Extend and implement `userModelClass()`:

```php
use Javaabu\Stats\Repositories\TimeSeries\SignupsRepository;

class CustomerSignups extends SignupsRepository
{
    public function userModelClass(): string
    {
        return \App\Models\Customer::class;
    }
}
```

For logins (requires `spatie/laravel-activitylog`), extend `LoginsRepository` the same way.

The package auto-registers `user_signups` and `user_logins` for the default User model. Suppress with `TimeSeriesStats::excludeDefaultStats()`.

## Configuration

Publish: `php artisan vendor:publish --tag=stats-config`

Key options in `config/stats.php`:

| Option | Default | Description |
|--------|---------|-------------|
| `week_starts_on_sunday` | `true` | Week grouping start day |
| `date_locale` | `en_GB` | Date format locale |
| `default_time_series_mode` | `DAY` | Default grouping granularity |
| `default_date_range` | `LAST_7_DAYS` | Default date range |
| `framework` | `material-admin-26` | CSS framework for views |

Other publish tags: `stats-views`, `stats-stubs`.

## MCP Tools

Three read-only MCP tools for AI agent integration (requires `laravel/mcp`):

- **ListMetrics** — lists all registered metrics with classes, aggregate fields, and allowed filters
- **QueryStat** — queries a metric with parameters (mode, date range, filters, format)
- **ListFormatters** — lists available output formats
