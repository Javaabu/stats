<?php
/**
 * Count Stats Repository base class
 */

namespace Javaabu\Stats\Repositories\TimeSeries;

use Illuminate\Support\Str;

abstract class CountStatsRepository extends AggregateStatsRepository
{
    /**
     * Get the aggregate sql expression for the repository
     */
    public function getAggregateSql(): string
    {
        return 'count(*) as '.$this->getAggregateFieldName();
    }

    protected function generateName(): string
    {
        $class_name = parent::generateName();

        if (Str::endsWith($class_name, 'Count')) {
            $class_name = trim(Str::beforeLast($class_name, 'Count'));
        }

        return $class_name;
    }
}
