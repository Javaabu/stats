---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- MySQL, MariaDB, PostgreSQL or SQLite. Other databases need a little code; see [Databases](./advanced-usage/databases.md).
- [javaabu/forms](https://github.com/Javaabu/forms) v2, [javaabu/stencil](https://github.com/Javaabu/stencil) v1, [javaabu/filters](https://github.com/Javaabu/filters) v1, [javaabu/permissions](https://github.com/Javaabu/permissions) v3 and [javaabu/manifest](https://github.com/Javaabu/manifest) v1, installed with this package

Optional:

| Package | For |
| --- | --- |
| `inertiajs/inertia-laravel` ^2.0 | The Inertia React stats pages (`stats.views` = `inertia`). See [Stats pages](./basic-usage/stats-pages.md). |
| `spatie/laravel-activitylog` ^4.10 | The built-in `user_logins` stat, and any stat you build on `LoginsRepository` or `ActivityLogStatsRepository`. See [Time series stats](./basic-usage/time-series-stats.md#built-in-stats). |

# Front end

The Blade charts and pages need no JavaScript and no build step: their stylesheet ships prebuilt and javaabu/manifest adds it to the page. The charts take their colours from [Onu](https://github.com/Javaabu/onu)'s design tokens (`--chart-1` to `--chart-8`, `--card`, `--border`, …), which javaabu/forms' Onu styles provide.

The React charts and the Inertia pages are built by your app's Vite, through manifest's Vite plugin. They need:

| npm package | Version |
| --- | --- |
| `@javaabu/onu` | ^0.2.1 |
| `recharts` | ^3 |
| `@javaabu/forms` | The next-gen branch (`github:Javaabu/react-forms#next-gen`) until it's released |
| `react`, `react-dom` | ^19.0 |
| `@inertiajs/react` | ^2.0 or ^3.0, for the Inertia pages |

`php artisan manifest:check` compares these with your `package.json` and prints the install command for anything missing. The package declares them for its pages only, so the check asks for them only when your `package.json` has an Inertia adapter (`@inertiajs/react`), or when you pass `--scope=pages`; a Blade app is never asked to install React packages for stats. Onu also needs Tailwind CSS 4 and its providers; see [Onu's installation](https://github.com/Javaabu/onu/blob/main/docs/installation-and-setup.md).
