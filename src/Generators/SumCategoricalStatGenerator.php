<?php

namespace Javaabu\Stats\Generators;

class SumCategoricalStatGenerator extends AbstractCategoricalStatGenerator
{
    public function getStub(): string
    {
        return 'stats::Stats/Categorical/SumStat.stub';
    }
}
