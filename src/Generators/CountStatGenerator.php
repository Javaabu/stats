<?php

namespace Javaabu\Stats\Generators;

class CountStatGenerator extends AbstractStatGenerator
{
    public function getStub(): string
    {
        return 'stats::Stats/TimeSeries/CountStat.stub';
    }
}
