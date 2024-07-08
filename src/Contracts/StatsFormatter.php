<?php

namespace Javaabu\Stats\Contracts;

interface StatsFormatter
{
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * Get the comparison data
     *
     * @return StatsRepository
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
