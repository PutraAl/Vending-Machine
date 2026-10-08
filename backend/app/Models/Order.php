<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_code',
        'machine_id',
        'status',
        'total_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(
            Machine::class,
            'machine_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'order_id'
        );
    }

    public function dispenses(): HasMany
    {
        return $this->hasMany(
            Dispense::class,
            'order_id'
        );
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
