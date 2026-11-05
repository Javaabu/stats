<?php

namespace Javaabu\Stats\Tests\TestSupport\Policies;

class DenyAllPolicy
{
    public function view($user, $stat): bool
    {
        return false;
    }
}
