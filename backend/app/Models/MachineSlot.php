<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MachineSlot extends Model
{
    protected $fillable = [
        'machine_id',
        'product_id',
        'slot_code',
        'capacity',
        'current_qty',
        'hold_qty',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'current_qty' => 'integer',
        'hold_qty' => 'integer',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(
            Machine::class,
            'machine_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'slot_id'
        );
    }

    public function dispenses(): HasMany
    {
        return $this->hasMany(
            Dispense::class,
            'slot_id'
        );
    }

    public function availableQuantity(): int
    {
        return max(0, $this->current_qty - $this->hold_qty);
    }
}
