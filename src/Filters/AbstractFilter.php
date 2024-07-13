<?php

namespace Javaabu\Stats\Filters;

use Javaabu\Stats\Contracts\Filter;

abstract class AbstractFilter implements Filter
{
    protected string $name;
    protected string $internal_name;

    public function __construct(string $name, string $internal_name = '')
    {
        $this->name = $name;
        $this->internal_name = $internal_name ?: $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInternalName(): string
    {
        return $this->internal_name;
    }
}
