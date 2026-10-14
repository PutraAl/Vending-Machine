<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispenseReconciliation extends Model
{
    protected $fillable = [
        'dispense_id',
        'user_id',
        'decision',
        'reason',
        'evidence',
        'status_before',
        'status_after',
    ];

    public function dispense(): BelongsTo
    {
        return $this->belongsTo(Dispense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}