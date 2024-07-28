---
title: Installation & Setup
sidebar_position: 1.2
---

You can install the package via composer:

```bash
composer require javaabu/stats
```

# Publishing the config file

Publishing the config file is optional:

```bash
php artisan vendor:publish --provider="Javaabu\Stats\StatsServiceProvider" --tag="stats-config"
```

This is the default content of the config file:

```php
// TODO
```

# Setting up the API Route

Add the following code to your `api.php` route file. You can place it as a publicly accessible JSON route. The package will automatically add the `stats.view-time-series` middleware which will ensure users will be allowed to view only stats they're authorized to view.

This will add a `GET {api_base_url}/stats/time-series` endpoint.

```php
\Javaabu\Stats\TimeSeriesStats::registerApiRoute();
```

# Setting up the Admin Routes

Add the following code to your `admin.php` (if using Javaabu's Laravel Skeleton) or to your `web.php` route file.
This will add a `GET {admin_base_url}/stats/time-series` and a `POST {admin_base_url}/stats/time-series` endpoint.

```php
\Javaabu\Stats\TimeSeriesStats::registerRoutes();
```
