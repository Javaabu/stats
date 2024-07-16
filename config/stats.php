<?php

return [
    /*
    |--------------------------------------------------------------------------
    | First day of the week
    |--------------------------------------------------------------------------
    |
    | Used to determine the start day of week
    |
    */

    'first_day_of_week' => \Carbon\Carbon::SUNDAY,


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
    |
    */

    'date_formats' => [
        'hour' => 'j M y h:i A',
        'day' => 'j M y',
        'week' => 'Y - \W\e\e\k W',
        'month' => 'Y F',
        'year' => 'Y',
    ]
];
