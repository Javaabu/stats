<?php

namespace Javaabu\Stats\Repositories\Categorical;

use Illuminate\Support\Str;

abstract class CountCategoricalStatsRepository extends AbstractCategoricalStatsRepository
{
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
