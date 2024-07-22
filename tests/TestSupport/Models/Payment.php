<?php

namespace Javaabu\Stats\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Javaabu\Stats\Tests\TestSupport\Factories\PaymentFactory;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'paid_at' => 'datetime'
    ];

    protected static function newFactory()
    {
        return new PaymentFactory();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
