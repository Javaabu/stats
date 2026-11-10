---
title: AI assistants
sidebar_position: 1.45
---

The docs are plain Markdown, written for people and AI coding assistants alike. The package ships them, so they always match the version you have installed.

| File | What |
| --- | --- |
| `vendor/javaabu/stats/llms.txt` | The rules for using the package, and every page |
| `vendor/javaabu/stats/docs/` | The pages |
| `vendor/javaabu/stats/resources/boost/` | The Laravel Boost guideline and the `javaabu-stats` skill |

# Laravel Boost

[Laravel Boost](https://github.com/laravel/boost) finds the guideline and the skill by itself in Composer packages. Run this after installing or updating the package:

```bash
php artisan boost:update
```

Choose `javaabu/stats` if Boost asks which third-party packages to use.

The guideline tells the assistant that the project builds stats with this package, and to use the `javaabu-stats` skill when it adds or changes a stat, a chart, a dashboard, the stats pages, stats filters, caching or their tests. The skill covers defining and registering stats, the chart builders in Blade and React, dashboards, caching, the pages and the JSON API, with references for every class and the common mistakes.

# Other assistants

Point your assistant at the docs from the project's instructions file (`AGENTS.md`, `CLAUDE.md`, `.cursor/rules`, `.github/copilot-instructions.md`):

```md
## Stats

Stats, charts and dashboards use javaabu/stats. Before adding a stat or a chart, read vendor/javaabu/stats/llms.txt.
```

Or copy `vendor/javaabu/stats/resources/boost/skills/javaabu-stats` into your assistant's skills folder, such as `.claude/skills/`.
