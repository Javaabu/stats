# stats: notes for AI coding agents

`javaabu/stats` turns stat classes (time series and categorical repositories) into charts, pages, a JSON API and CSV exports. Stats are registered by metric in `TimeSeriesStats` / `CategoricalStats`; chart builders (`src/Charts`) describe a chart and serialise to the props of Onu's chart components; Blade draws the same data as SVG (`src/Views`), React with Onu (`resources/js`, shipped through javaabu/manifest as `@javaabu/stats`). Apps extend the repository base classes, the controllers and the policy, and v1 apps keep their stat classes, so treat the repository contracts and base classes, `TimeSeriesStats`/`CategoricalStats`, `StatsFilter`, the chart builders and their array shape (`resources/js/types.ts`), the Blade components, `Route::stats()`/`Route::statsApi()`, the request parameters, the config keys and the page props as public contracts.

## Layout

| Path | What |
| --- | --- |
| `src/TimeSeriesStats.php`, `src/CategoricalStats.php` | Registries of metrics and formatters; defaults; deprecated v1 route helpers |
| `src/Repositories`, `src/Concerns`, `src/Contracts`, `src/CategoryProviders` | Stat base classes, date ranges and filters, category providers |
| `src/Enums` | `TimeSeriesModes` (date SQL per driver, step keys, display), `PresetDateRanges`, `CategoricalModes` |
| `src/Filters` | `StatsFilter` (exact, scope, closure, `from()` for javaabu/filters → `ModelFilter`) |
| `src/Charts` | `TimeSeriesChart`, `CategoryChart`, `PieChart`, `Sparkline`, `StatTile` and their concerns |
| `src/Cache`, `src/Jobs`, `src/Commands/WarmStatsCacheCommand.php`, `src/StatsManager.php`, `src/Facades/Stats.php` | Versioned cache, warming, the `Stats` facade |
| `src/Forms` | The stencil forms of the stats pages and `CategorySelect` |
| `src/Http`, `src/Routing`, `src/Policies`, `src/Views/ViewRenderer.php` | Page and API controllers, requests (`ResolvesStats` holds the parameter contract), route macros, policy, Blade/Inertia switch |
| `src/Views/Components`, `src/Views/Svg`, `resources/views`, `resources/css/charts.css` | Blade chart components, SVG geometry, views, chart styles (prebuilt into `dist/`) |
| `src/Exports`, `src/Formatters`, `src/Generators`, `stubs` | CSV exports, v1 formatters, generator commands and stubs |
| `resources/js` | React: `StatsChart`, `StatsTile`, `StatsPage`, the Inertia pages, types |
| `lang/en.json`, `lang/dv.json` | Translations; English strings are the keys |
| `docs`, `llms.txt`, `resources/boost` | Docusaurus docs, the index for AI assistants, the Laravel Boost guideline and skill |
| `tests` | Testbench tests (SQLite by default; `DB_CONNECTION=mysql|mariadb|pgsql` runs them on that database); `tests/js` are Vitest tests of the React side, on fixtures generated from PHP |

## Rules

- Follow Javaabu codestyle ([boost-javaabu-guidelines](https://github.com/Javaabu/boost-javaabu-guidelines)): snake_case variables and properties, camelCase methods, early returns, `#[Test]` and `it_…` test names. Pint enforces formatting. The enums use the package's own `HasLabels`, not javaabu/helpers' `IsEnum`: never depend on javaabu/helpers or javaabu/settings ^1 (CI fails).
- Date SQL must work on MySQL, MariaDB, PostgreSQL and SQLite. Add a driver in `TimeSeriesModes::getSql()` and run the suite on each database.
- Keep the chart array shape and `resources/js/types.ts` in step, and keep the Blade SVG and the React charts showing the same thing. When the page props change, regenerate the fixtures: `UPDATE_FIXTURES=1 vendor/bin/phpunit --filter InertiaPagesTest`.
- Anything a chart depends on goes into its `cacheKey()`.
- Nothing may assume the `web` guard or `App\Models\User`. Stats authorize through the gate (`canView()` → `StatsPolicy`).
- Inertia and spatie/laravel-activitylog are optional: feature-detect them, keep them in `suggest`, and mark tests that need them with `#[RequiresMethod]`.
- Every user-facing string goes through `__()` with an English key, and gets a line in `lang/en.json` and `lang/dv.json` (`TranslationsTest` checks). Use Thaana in tests of user-facing behaviour. List uncertain translations in `docs/advanced-usage/dhivehi-and-rtl.md`. Views, CSS and React use logical properties only (`RtlTest` checks).
- After changing `resources/css/charts.css`, run `pnpm build` and commit `dist/` (CI checks).
- Every change needs tests, docs (linked from `llms.txt`), the Boost skill if it changes how the package is used, and a line in `CHANGELOG.md`.
- Work on the `next-gen` branch; don't commit to `main`.

## Commands

```bash
composer install
composer lint       # vendor/bin/pint --test
composer format     # vendor/bin/pint
composer test       # vendor/bin/phpunit (SQLite)
DB_CONNECTION=pgsql DB_DATABASE=stats_test DB_USERNAME=postgres vendor/bin/phpunit
pnpm install && pnpm check    # lint, format, typecheck and Vitest (needs vendor/ for stencil)
pnpm build                     # dist/ for apps without Vite
```
