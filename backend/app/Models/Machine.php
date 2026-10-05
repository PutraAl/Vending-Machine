<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Dispense;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Machine extends Model
{
    protected $fillable = [
        'machine_code',
        'name',
        'location',
        'status',
        'temperature_threshold',
    ];

    protected $casts = [
        'temperature_threshold' => 'decimal:2',
    ];

    public function slots(): HasMany
    {
        return $this->hasMany(
            MachineSlot::class,
            'machine_id'
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(
            Order::class,
            'machine_id'
        );
    }

    public function telemetries(): HasMany
    {
        return $this->hasMany(
            Telemetry::class,
            'machine_id'
        );
    }

    public function errors(): HasMany
    {
        return $this->hasMany(
            MachineError::class,
            'machine_id'
        );
    }

    public function dispenses(): HasMany
    {
        return $this->hasMany(
            Dispense::class,
            'machine_id'
        );
    }

    public function latestTelemetry(): HasOne
    {
        return $this->hasOne(Telemetry::class)->latestOfMany('created_at');
    }
}
