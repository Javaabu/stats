<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Whether the week should start on Sunday
    |--------------------------------------------------------------------------
    |
    | Used to determine the start day of week.
    | true - Week starts on Sunday
    | false - Week starts on Monday
    |
    */

    'week_starts_on_sunday' => true,


    /*
    |--------------------------------------------------------------------------
    | The locale used for dates
    |--------------------------------------------------------------------------
    |
    | The locale used to format dates
    |
    */

    'date_locale' => 'en_GB',

    /*
    |--------------------------------------------------------------------------
    | Date formats for different time modes
    |--------------------------------------------------------------------------
    |
    | Used to format the date when generating stats in different time series modes
    | Uses momentJS compatible formats
    |
    */

    'date_formats' => [
        'hour' => 'D MMM YY hh:mm A',
        'day' => 'D MMM YY',
        'week' => 'gggg - \W\e\e\k w',
        'month' => 'YYYY MMMM',
        'year' => 'YYYY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default time series mode
    |--------------------------------------------------------------------------
    |
    | Default mode to show on the stats form
    |
    */

    'default_time_series_mode' => \Javaabu\Stats\Enums\TimeSeriesModes::DAY,

    /*
    |--------------------------------------------------------------------------
    | Default date range
    |--------------------------------------------------------------------------
    |
    | Default date range to show on stats
    |
    */

    'default_date_range' => \Javaabu\Stats\Enums\PresetDateRanges::LAST_7_DAYS,

    /*
    |--------------------------------------------------------------------------
    | Default CSS Framework
    |--------------------------------------------------------------------------
    |
    | This option controls the default CSS framework that will be used by the
    | package when rendering views
    |
    | Supported: "material-admin-26"
    |
    */

    'framework' => 'material-admin-26',

    /*
    |--------------------------------------------------------------------------
    | Default Layout
    |--------------------------------------------------------------------------
    |
    | Default layout view for stats views
    |
    */

    'default_layout' => 'layouts.admin',

    /*
    |--------------------------------------------------------------------------
    | Scripts Stack
    |--------------------------------------------------------------------------
    |
    | The name of the stack to push scripts
    |
    */

    'scripts_stack' => 'scripts',

   /*
   |--------------------------------------------------------------------------
   | View for time series stats
   |--------------------------------------------------------------------------
   |
   | Used in the main time series stats controller
   |
   */

    'time_series_stats_view' => 'stats::material-admin-26.time-series-stats.index',
];
