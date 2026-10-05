<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'image',
        'is_active',
    ];


    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];
    

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ProductCategory::class,
            'category_id'
        );
    }
    

    public function machineSlots(): HasMany
    {
        return $this->hasMany(
            MachineSlot::class,
            'product_id'
        );
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'product_id'
        );
    }
}