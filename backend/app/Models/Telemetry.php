<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Telemetry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'temperature',
        'state',
        'door_status',
        'created_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(
            Machine::class,
            'machine_id'
        );
    }
}