<?php

namespace Javaabu\Stats\Enums;

enum StatListReturnType: string
{
    case METRIC = 'metric';
    case METRIC_AND_NAME = 'metric_and_name';
    case METRIC_AND_CLASS = 'metric_and_class';
}
