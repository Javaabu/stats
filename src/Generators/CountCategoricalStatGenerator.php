<?php

namespace Javaabu\Stats\Generators;

class CountCategoricalStatGenerator extends AbstractCategoricalStatGenerator
{
    public function getStub(): string
    {
        return 'stats::Stats/Categorical/CountStat.stub';
    }
}
