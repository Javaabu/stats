<?php

namespace Javaabu\Stats\Formatters\Categorical;

use Javaabu\Stats\CategoricalStats;
use Javaabu\Stats\Contracts\CategoricalStatsFormatter;

abstract class AbstractCategoricalStatsFormatter implements CategoricalStatsFormatter
{
    public function getName(): string
    {
        return CategoricalStats::getNameForFormatter(get_class($this));
    }
}
