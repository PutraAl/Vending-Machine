<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispense extends Model
{
    protected $fillable = [
        'order_id',
        'machine_id',
        'slot_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class,
            'order_id'
        );
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(
            Machine::class,
            'machine_id'
        );
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(
            MachineSlot::class,
            'slot_id'
        );
    }
}