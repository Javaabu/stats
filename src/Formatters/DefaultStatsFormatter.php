<?php
/**
 * Default stats formatter
 */

namespace Javaabu\Stats\Formatters;

class DefaultStatsFormatter extends StatsFormatter
{
    /**
     * Format the data
     *
     * @param string $mode
     * @return array
     */
    public function format($mode)
    {
        $stats = $this->getStats()->results($mode);
        $compare = $this->getCompare()->results($mode);

        return compact('stats', 'compare');
    }
}
