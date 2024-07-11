<?php

namespace Javaabu\Stats\Contracts;

interface TimeSeriesStatsFormatter
{
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Get the comparison data
     *
     * @return TimeSeriesStatsRepository
     */
    public function getCompare()
    {
        return $this->compare;
    }

    /**
     * Format the data
     *
     * @param string $mode
     * @return array
     */
    public abstract function format($mode);
}
