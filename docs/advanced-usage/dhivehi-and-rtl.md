---
title: Dhivehi and RTL
sidebar_position: 9
---

# Dhivehi and right-to-left

Everything the package shows is translated, and the pages and charts work right to left.

## Translations

Every string the package shows goes through `__()` with an English key, and the package ships `lang/en.json` and `lang/dv.json`. Set the app's locale to `dv` and the pages, charts, form labels, preset date ranges, modes, accessible summaries and the `view_stats` permission description are in Dhivehi.

To change a translation, publish the files and edit them:

```bash
php artisan vendor:publish --tag=stats-translations
```

They are copied to `lang/vendor/stats`. Your app's own `lang/dv.json` also wins over the package's for any key it has.

Names of your stats come from your stat classes (`getName()`), and category names from your category providers, so translate them there:

```php
public function getName(): string
{
    return __('Payments');
}
```

The React charts translate their own strings (the "View as table" button, "Up 12%", the summaries) through Onu's `dv` locale. Strings the pages need from PHP (titles, the download button, messages) come in the page's `strings` prop, already translated.

## Dates and numbers

- Chart steps are formatted in the app's locale with the formats in `config('stats.date_formats')`, so months are named in Dhivehi (`ޖުލައި 2024`). Weeks read "Week 29, 2024" (`:year ގެ :week ވަނަ ހަފްތާ`), numbered with `config('stats.date_locale')` and `config('stats.week_starts_on_sunday')`.
- Period names use the presets' labels (`ފާއިތުވި 7 ދުވަސް`) or the dates in the app's locale.
- Numbers use Latin digits in Dhivehi, as Onu does. Currency is shown as `ރ. 1,250.00` in Dhivehi and `MVR 1,250.00` otherwise.
- The CSV exports have headings in the app's locale and steps as ISO dates (`2024-07-03`), so spreadsheets and other tools can read them; category and stat names are written as they are. Files start with a UTF-8 byte order mark so Excel shows Thaana correctly.

## Right to left

- **Blade charts** are drawn mirrored when the app's locale is in `config('stats.rtl_locales')` (`dv`, `ar`, `fa`, `he` and `ur` by default): time runs right to left, so the newest step is on the left, the value axis is on the right, horizontal bars grow to the left, and pies run counter-clockwise from 12 o'clock. This matches Onu's React charts. Pass `:rtl="true"` or `:rtl="false"` to `<x-stats::chart>` to choose for one chart.
- **React charts** follow the page's `dir`, as every Onu component does.
- **Styles** use logical CSS only (`margin-inline-start`, `inset-block-start`, `ms-`, `pe-`), and percentages are wrapped in `dir="ltr"` so the sign stays before the number. A test fails the build if a view, the stylesheet or a React file uses `left` or `right`.
- Set `dir="rtl"` and `lang="dv"` on your layout's `<html>`.

## Thaana input

The stats forms have no free-text fields: stats, modes, ranges and categories are chosen from lists, and dates from date pickers. The category search box searches category names in Thaana (`ޢާއި` finds `ޢާއިޝާ`). Filters from javaabu/filters keep their own Thaana support (`TextFilter` has `->thaana()`).

## Translations to review

These Dhivehi strings are the best choice I could make, but a Dhivehi speaker should check them, mostly technical terms with no settled Dhivehi word:

| English | Dhivehi | Note |
| --- | --- | --- |
| Category, Categories | ބައި, ބައިތައް | "ބާވަތް" may read better for some stats |
| Line, Area, Bar, Pie (chart types) | ލައިން, އޭރިއާ, ބާ, ޕައި | Transliterated |
| Line chart, Area chart, Bar chart, Pie chart, Donut chart | ލައިން ޗާޓް, އޭރިއާ ޗާޓް, ބާ ޗާޓް, ޕައި ޗާޓް, ޑޯނަޓް ޗާޓް | Transliterated |
| Horizontal bar | ބޯޅަ ބާ | |
| Time Series Stats | ވަގުތާއެކު ތަފާސް ހިސާބު | |
| Categorical Stats | ބައިތަކަށް ބަހާލި ތަފާސް ހިސާބު | |
| Generate | ދައްކާ | Literally "show" |
| Group by | ބަހާލާނީ | |
| Plot with | އެކުގައި ދައްކާ | |
| Share (of a pie) | ހިއްސާ | |
| Lifetime | ހުރިހާ ދުވަހެއް | |
| User Logins, User Signups | ޔޫޒަރުންގެ ލޮގިން, ޔޫޒަރުންގެ ސައިން އަޕް | |
| :chart with :series series and :points data points. | :series ސީރީޒް އަދި :points ޑޭޓާ ޕޮއިންޓް ހިމެނޭ :chart. | Screen reader summary |
| Week :week, :year | :year ގެ :week ވަނަ ހަފްތާ | |
