<?php

namespace Javaabu\Stats\Repositories\Categorical;

abstract class SumCategoricalStatsRepository extends AbstractCategoricalStatsRepository
{
    abstract public function getFieldToSum(): string;

    public function getAggregateSql(): string
    {
        return 'sum('.$this->getFieldToSum().') as '.$this->getAggregateFieldName();
    }
}
