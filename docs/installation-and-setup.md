---
title: Installation & Setup
sidebar_position: 1.2
---

```bash
composer require javaabu/stats
```

The service provider registers itself, and so does the `Stats` facade. There is no migration: stats read your own tables.

:::note Before the v2 release

v2 is developed on the `next-gen` branch and depends on unreleased Javaabu packages. Until it's tagged, add the repositories and require the dev version:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/Javaabu/stats" },
        { "type": "vcs", "url": "https://github.com/Javaabu/filters" },
        { "type": "vcs", "url": "https://github.com/Javaabu/forms" },
        { "type": "vcs", "url": "https://github.com/Javaabu/manifest" },
        { "type": "vcs", "url": "https://github.com/Javaabu/permissions" },
        { "type": "vcs", "url": "https://github.com/Javaabu/stencil" }
    ]
}
```

```bash
composer require javaabu/stats:^2.0@dev
```

Composer only accepts dev versions of packages your app requires itself, so either require the other five with `@dev` too, or set `"minimum-stability": "dev"` and `"prefer-stable": true` in your `composer.json`.

:::

# Publish the config

Publishing the config is optional. Do it to register stats in the config file or change a default:

```bash
php artisan vendor:publish --tag=stats-config
```

Every option is described in [Configuration](./advanced-usage/configuration.md).

# Define a stat

```php
<?php

namespace App\Stats\TimeSeries;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Javaabu\Stats\Repositories\TimeSeries\CountStatsRepository;

class PaymentsCount extends CountStatsRepository
{
    public function query(): Builder
    {
        return Payment::query();
    }

    public function getTable(): string
    {
        return 'payments';
    }

    public function getAggregateFieldName(): string
    {
        return 'payments';
    }
}
```

Or generate it: `php artisan stats:time-series PaymentsCount Payment`.

Register it under a metric name, in the config or a service provider:

```php
// config/stats.php
'time_series_stats' => [
    'payments_count' => App\Stats\TimeSeries\PaymentsCount::class,
],
```

```php
// AppServiceProvider::boot()
use App\Stats\TimeSeries\PaymentsCount;
use Javaabu\Stats\TimeSeriesStats;

TimeSeriesStats::register([
    'payments_count' => PaymentsCount::class,
]);
```

See [Time series stats](./basic-usage/time-series-stats.md) and [Categorical stats](./basic-usage/categorical-stats.md) for every option.

# Add the stats pages

Register the routes where you want them:

```php
// routes/web.php
Route::stats();                                       // /stats/time-series and /stats/categorical, auth middleware, stats.* names

// or, for an admin portal on its own guard
Route::stats(['guard' => 'web_admin', 'prefix' => 'admin/stats', 'name' => 'admin.stats']);
```

The routes need the `web` middleware group (sessions and CSRF for the CSV download); `routes/web.php` already has it.

Sync the permissions and give `view_stats` to a role:

```bash
php artisan permissions:sync
```

Users with `view_stats` can now open `/stats/time-series`. See [Stats pages](./basic-usage/stats-pages.md) and [Permissions and policies](./advanced-usage/permissions-and-policies.md).

## Blade

Blade is the default. The pages extend `layouts.app` and fill its `content` section. Change both in the config:

```php
'layout' => 'layouts.admin',
'layout_section' => 'main',
```

The charts are SVG drawn on the server, so there is nothing to build. Their stylesheet (`stats::charts`) ships prebuilt; javaabu/manifest adds it to every page that shows a chart, before `</head>`. If you place manifest's tags yourself, keep `@manifestStyles` in the layout's `<head>`:

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'dv' ? 'rtl' : 'ltr' }}">
<head>
    @manifestStyles
</head>
<body>
    @yield('content')
    @manifestScripts
</body>
</html>
```

If your app builds with Vite and has manifest's Vite plugin, the stylesheet is built with your app instead of using the prebuilt copy.

The charts use Onu's colour tokens. With javaabu/forms' default `onu` framework they are there already. With `bootstrap-6`, define `--chart-1` to `--chart-8` (and optionally `--card`, `--border`, `--muted-foreground`, `--success`, `--destructive`) in your CSS.

## Inertia React

```bash
composer require inertiajs/inertia-laravel
```

```php
// config/stats.php, or STATS_VIEWS=inertia
'views' => 'inertia',
```

The pages are `stats::TimeSeries/Index` and `stats::Categorical/Index`, shipped through javaabu/manifest. Add manifest's Vite plugin:

```js
// vite.config.js
import react from "@vitejs/plugin-react"
import laravel from "laravel-vite-plugin"
import { defineConfig } from "vite"
import manifest from "./vendor/javaabu/manifest/dist/vite.js"

export default defineConfig({
  plugins: [laravel({ input: ["resources/css/app.css", "resources/js/app.tsx"], refresh: true }), react(), manifest()],
})
```

Resolve pages with `resolvePage()`, and give the package's pages your layout:

```tsx
// resources/js/app.tsx
import { createInertiaApp } from "@inertiajs/react"
import { resolvePage } from "@javaabu/manifest/inertia"
import { createRoot } from "react-dom/client"
import AdminLayout from "@/layouts/admin"

createInertiaApp({
  resolve: async (name) => {
    const page = await resolvePage(name, import.meta.glob("./pages/**/*.tsx"))
    page.default.layout ??= (children: React.ReactNode) => <AdminLayout>{children}</AdminLayout>
    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

Install the npm packages the pages and charts use:

```bash
npm install @javaabu/onu recharts
php artisan manifest:check
```

`manifest:check` lists anything still missing (such as `@javaabu/forms`) with the install command. It checks what the pages need once `@inertiajs/react` is in your `package.json`; before that, run `php artisan manifest:check --scope=pages`.

The pages and Onu are styled with Tailwind CSS 4. Import Onu's stylesheet and add the package's components to your Tailwind sources:

```css
/* resources/css/app.css */
@import "tailwindcss";
@import "@javaabu/onu/tailwind.css";
@source "../../vendor/javaabu/stats/resources/js";
```

Wrap your app in Onu's `ThemeProvider` and `OnuProvider` if it doesn't already; see [Onu's installation](https://github.com/Javaabu/onu/blob/main/docs/installation-and-setup.md).

To use the React components in your own pages, import them as `@javaabu/stats`:

```tsx
import { StatsChart, StatsTile } from "@javaabu/stats"
```

Run `php artisan manifest:types` so TypeScript knows the alias. See [Charts](./basic-usage/charts.md).

# Publishing

| Tag | What |
| --- | --- |
| `stats-config` | `config/stats.php` |
| `stats-views` | The Blade views, to `resources/views/vendor/stats` |
| `stats-translations` | `lang/vendor/stats` |
| `stats-stubs` | The generator stubs, to `stubs/vendor/stats` |

```bash
php artisan vendor:publish --tag=stats-config
```

See [Overriding controllers and views](./advanced-usage/overriding-controllers-and-views.md) and [Dhivehi and RTL](./advanced-usage/dhivehi-and-rtl.md).
