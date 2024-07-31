<?php

namespace Javaabu\Stats\Generators;

class SumStatGenerator extends AbstractStatGenerator
{
    public function getStub(): string
    {
        return 'stats::Stats/TimeSeries/SumStat.stub';
    }
}
