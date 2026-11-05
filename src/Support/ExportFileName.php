<?php

namespace Javaabu\Stats\Support;

use Illuminate\Support\Str;
use Javaabu\Stats\Contracts\InteractsWithDateRange;

/**
 * The file name of a stats export: `{App Name} {Stat name} {20260101-20261231} [{comparison}].csv`
 */
class ExportFileName
{
    public static function make(string $title, InteractsWithDateRange $stats, ?InteractsWithDateRange $compare = null, string $prefix = ''): string
    {
        $prefix = $prefix ?: (string) config('app.name');

        $name = Str::headline($prefix) . ' ' . $title . ' ' . $stats->formattedDateRange('YYYYMMDD', '-');

        if ($compare) {
            $name .= ' ' . $compare->formattedDateRange('YYYYMMDD', '-');
        }

        return trim(str_replace(['/', '\\'], '-', $name)) . '.csv';
    }
}
