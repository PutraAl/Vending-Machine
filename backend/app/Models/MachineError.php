<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineError extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'machine_id',
        'error_code',
        'message',
        'severity',
        'created_at',
        'resolved_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(
            Machine::class,
            'machine_id'
        );
    }
}