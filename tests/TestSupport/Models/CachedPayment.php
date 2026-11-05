<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

use Javaabu\Stats\Concerns\InvalidatesStats;

class CachedPayment extends Payment
{
    use InvalidatesStats;

    protected $table = 'payments';

    protected $guarded = [];

    protected array $invalidates_stats = ['payments_count'];
}
