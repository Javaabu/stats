<?php

namespace Javaabu\Stats\Contracts;

use Javaabu\Stats\Enums\TimeSeriesModes;

interface TimeSeriesStatsFormatter
{
    /**
     * Format the data
     */
    public function format(TimeSeriesModes $mode, TimeSeriesStatsRepository $stats, ?TimeSeriesStatsRepository $compare = null): array;

    /**
     * Get the name of the formatter
     */
    public function getName(): string;
}
