---
title: JSON API
sidebar_position: 5
---

The JSON API gives the numbers of any registered stat, for a mobile app, a front end of your own or another service. The [stats pages](../basic-usage/stats-pages.md) don't need it.

# Routes

```php
// routes/api.php
Route::statsApi(['guard' => 'sanctum']);
```

| Route | Name | What |
| --- | --- | --- |
| `GET {prefix}/time-series` | `{name}.time-series.index` | A time series stat |
| `GET {prefix}/categorical` | `{name}.categorical.index` | A categorical stat |
| `GET {prefix}/categorical/categories` | `{name}.categorical.categories` | Search a categorical stat's categories |

`Route::statsApi()` takes the same options as `Route::stats()`:

| Option | Default | What |
| --- | --- | --- |
| `prefix` | `stats` | The URL prefix, under your API file's own prefix (`/api/stats/...` in `routes/api.php`) |
| `name` | `api.stats` | The route name prefix |
| `guard` | `null` (the default guard) | The guard users authenticate with. Permissions are checked for its user |
| `middleware` | `['auth']`, or `["auth:{guard}"]` with a guard | The route middleware. Replaces the default, so include an `auth` middleware |
| `time_series` | `true` | Register the time series endpoint |
| `categorical` | `true` | Register the categorical endpoint and the category search |
| `controllers` | From `stats.controllers` | `time_series_api`, `categorical_api`, `categories` |

Users see only the stats they may view: asking for a stat the user can't see, or invalid input, gets `422` with the errors.

# Time series

```text
GET /api/stats/time-series?metric=payments_count&mode=day&date_range=custom&date_from=2024-07-03&date_to=2024-07-04&compare_to=previous
```

| Parameter | Required | What |
| --- | --- | --- |
| `metric` | Yes | A metric the user can see, that allows the fixed filters |
| `mode` | Yes | `hour`, `day`, `week`, `month` or `year` |
| `date_range` | Or the dates | A preset (`last_30_days`, see [Date ranges](../basic-usage/time-series-stats.md#date-ranges)), or `custom` |
| `date_from`, `date_to` | When `date_range` is `custom` or missing | The range. A date without a time covers the whole day; `date_to` can't be before `date_from` |
| `compare_to` | No | `previous` for the period before, `custom` for `compare_date_from` to `compare_date_to` |
| `compare_date_from`, `compare_date_to` | With `compare_to=custom` | The comparison period. Giving both compares even without `compare_to` |
| `compare_date_range` | No | A preset to compare with. Wins over `compare_to` |
| `compare` | No | `1` compares with the previous period (v1) |
| `format` | No | A registered [formatter](./formatters.md): `default` (the default), `chartjs`, `sparkline`, `flot`, `combined` |
| `filters[name]` | No | Fixed filters, by name |
| `{filter}` | No | The values of `StatsFilter::from()` filters, by their own query parameters (`status=paid`) |

```json
{
    "metric": "payments_count",
    "metric_name": "Payments",
    "mode": "day",
    "aggregate_field": "payments",
    "aggregate_field_label": "Payments",
    "date_range_title": "Primary Period - Jul 3, 2024 - Jul 4, 2024",
    "date_range": "custom",
    "date_from": "2024-07-03 00:00:00",
    "date_to": "2024-07-04 23:59:59",
    "compare_date_range_title": "Comparison Period - Jul 1, 2024 - Jul 2, 2024",
    "compare_date_range": "previous",
    "compare_date_from": "2024-07-01 00:00:00",
    "compare_date_to": "2024-07-02 23:59:59",
    "format": "default",
    "result": {
        "stats": [
            { "payments": 3, "day": "2024-07-03" },
            { "payments": 2, "day": "2024-07-04" }
        ],
        "compare": []
    },
    "filters": []
}
```

| Field | What |
| --- | --- |
| `metric`, `metric_name` | The metric and the stat's name |
| `mode` | The grouping |
| `aggregate_field`, `aggregate_field_label` | The value's key in `result`, and its label |
| `date_range` | The preset's value, or `custom` |
| `date_from`, `date_to` | The resolved range |
| `date_range_title` | "Primary Period - Jul 3, 2024 - Jul 4, 2024", translated |
| `compare_date_range` | `previous`, `custom`, a preset's value, or `null` without a comparison |
| `compare_date_from`, `compare_date_to`, `compare_date_range_title` | The comparison period, or `null` |
| `format` | The formatter used |
| `result` | The formatter's output |
| `filters` | The filters applied, by name |

The `default` format only has steps with data. With `format=chartjs`, every step is there:

```json
"result": {
    "labels": ["3 Jul 24#1 Jul 24", "4 Jul 24#2 Jul 24"],
    "stats": [3, 2],
    "compare": [0, 0]
}
```

`with[]` is accepted (and validated) but the API returns the main stat only. Ask once per metric.

# Categorical

```text
GET /api/stats/categorical?metric=payments_by_island&mode=non_empty&date_range=this_month&format=combined
```

The same parameters as the time series endpoint, except:

| Parameter | Required | What |
| --- | --- | --- |
| `mode` | Yes | `non_empty`, `all` or `specific`. See [Categorical stats](../basic-usage/categorical-stats.md#which-categories) |
| `categorical_values[]` | When `mode` is `specific` | Category ids |
| `format` | No | `default` (the default), `combined` or `chartjs` |

```json
{
    "metric": "payments_by_island",
    "metric_name": "Payments by island",
    "mode": "non_empty",
    "aggregate_field": "payments",
    "aggregate_field_label": "Payments",
    "date_range_title": "Primary Period - Jul 1, 2024 - Jul 31, 2024",
    "date_range": "this_month",
    "date_from": "2024-07-01 00:00:00",
    "date_to": "2024-07-31 23:59:59",
    "compare_date_range_title": null,
    "compare_date_range": null,
    "compare_date_from": null,
    "compare_date_to": null,
    "format": "default",
    "result": {
        "stats": [
            { "island": 12, "island_name": "Male", "payments": 40 },
            { "island": 15, "island_name": "Hulhumale", "payments": 7 }
        ],
        "compare": null
    },
    "filters": []
}
```

# Category search

```text
GET /api/stats/categorical/categories?filter[metric]=payments_by_island&filter[search]=hulhu&per_page=20
```

| Parameter | Required | What |
| --- | --- | --- |
| `filter[metric]` | Yes | A categorical metric the user can see |
| `filter[search]`, or `search` | No | The search text |
| `page` | No | The page |
| `per_page` | No | 1 to 100. Default: `stats.categorical_items_per_page` (15). `0` or `-1` gives 100 |
| `filters[name]` | No | Fixed filters. The metric must allow them |

The answer is a Laravel paginator; each item in `data` has `id` and `label`:

```json
{
    "current_page": 1,
    "data": [
        { "id": 15, "label": "Hulhumale" }
    ],
    "per_page": 20,
    "total": 1,
    "last_page": 1
}
```

# Errors

| Status | When |
| --- | --- |
| `401` | Not logged in on the guard |
| `403` | Category search: the user can't see the metric, or it doesn't allow the filters |
| `422` | Invalid input, including a metric the user can't see or that doesn't allow the filters. `errors` has a message per field |

```json
{
    "message": "The selected metric is invalid. (and 2 more errors)",
    "errors": {
        "metric": ["The selected metric is invalid."],
        "date_from": ["The date from field is required when date range is not present."],
        "date_to": ["The date to field is required when date range is not present."]
    }
}
```

# Documenting the API

The requests (`TimeSeriesStatsRequest`, `CategoricalStatsRequest`) have `bodyParameters()` describing every parameter, and the controllers have doc blocks in a "Stats" group, which API documentation generators such as Scribe read.
