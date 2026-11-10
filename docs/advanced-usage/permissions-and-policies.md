---
title: Permissions and policies
sidebar_position: 3
---

# The permission

The package registers one permission with [javaabu/permissions](https://github.com/Javaabu/permissions), in a `stats` group labelled "Stats":

| Permission | Lets a user |
| --- | --- |
| `view_stats` | See every stat that doesn't decide otherwise: on the stats pages, in the JSON API and in CSV downloads |

Create it with `php artisan permissions:sync`, for every guard in `config('permissions.guards')`, and give it to roles as usual. The constant is `Javaabu\Stats\Policies\StatsPolicy::VIEW`.

Turn registration off with `'register_permissions' => false` to register it (or something else) yourself.

# The policy

`Javaabu\Stats\Policies\StatsPolicy` is the policy of every stat: it is registered for the `TimeSeriesStatsRepository` and `CategoricalStatsRepository` contracts, so it covers every stat class.

| Ability | Allowed with |
| --- | --- |
| `view` | `view_stats` |
| `export` | The stat's `canView()`. The package's downloads check it; restrict downloads by overriding it in your policy |

A stat's `canView(?Authorizable $user = null)` asks the policy's `view`, and is `false` without a user. The stats pages, the API, the category search, the downloads, the forms' stat lists and `getMetricNames()` / `allowedMetrics()` all go through `canView()`.

```php
use Javaabu\Stats\TimeSeriesStats;

TimeSeriesStats::createFromMetric('payments_count')->canView($user);
$user->can('view', TimeSeriesStats::createFromMetric('payments_count'));
TimeSeriesStats::canViewAny($user);
```

Replace the policy, in order of precedence:

```php
// config/permissions.php
'policies' => [
    Javaabu\Stats\Contracts\TimeSeriesStatsRepository::class => App\Policies\StatsPolicy::class,
    Javaabu\Stats\Contracts\CategoricalStatsRepository::class => App\Policies\StatsPolicy::class,
],

// or in a service provider
Gate::policy(TimeSeriesStatsRepository::class, App\Policies\StatsPolicy::class);

// or config/stats.php
'policy' => App\Policies\StatsPolicy::class,
```

```php
<?php

namespace App\Policies;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Javaabu\Stats\Contracts\CategoricalStatsRepository;
use Javaabu\Stats\Contracts\TimeSeriesStatsRepository;
use Javaabu\Stats\Policies\StatsPolicy as BasePolicy;

class StatsPolicy extends BasePolicy
{
    public function view(Authorizable $user, TimeSeriesStatsRepository|CategoricalStatsRepository $stat): bool
    {
        if (str_starts_with($stat->metric(), 'payment')) {
            return $user->can('view_payment_stats');
        }

        return parent::view($user, $stat);
    }
}
```

# A permission per stat

Or let a stat decide for itself, by overriding `canView()`:

```php
use Illuminate\Contracts\Auth\Access\Authorizable;

class PaymentsCount extends CountStatsRepository
{
    public function canView(?Authorizable $user = null): bool
    {
        return $user?->can('view_payment_stats') ?? false;
    }
}
```

Register `view_payment_stats` with javaabu/permissions in your app, so `permissions:sync` creates it:

```php
use Javaabu\Permissions\Permissions;

Permissions::register('stats', [
    'view_payment_stats' => 'View payment stats',
]);
```

# Charts and dashboards

Chart builders don't check permissions: they draw whatever you give them. Authorize the page that shows them, and leave out charts of stats the user can't see. See [Dashboards](../basic-usage/dashboards.md#the-controller).

# Guards and user models

Nothing assumes the `web` guard or `App\Models\User`. Pass the guard to the routes, and permissions are checked for that guard's user:

```php
Route::stats(['guard' => 'web_admin', 'prefix' => 'admin/stats', 'name' => 'admin.stats']);
Route::statsApi(['guard' => 'sanctum']);
```

The user only needs to be `Authorizable` (every Eloquent user model is).

# Middleware

Two middleware aliases abort with `403` when the user can't see any stat. The v1 route helpers use them; the new routes check inside the controllers instead. Use them on your own routes:

| Alias | Class |
| --- | --- |
| `stats.view-time-series` | `AbortIfCannotViewAnyTimeSeriesStats` |
| `stats.view-categorical` | `AbortIfCannotViewAnyCategoricalStats` |

Both take guards as parameters: `stats.view-time-series:web_admin`.
