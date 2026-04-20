<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'sale_price',
        'average_cost',
        'stock_quantity',
    ];

    protected function casts(): array
    {
        return [
            'sale_price' => 'decimal:2',
            'average_cost' => 'decimal:2',
            'stock_quantity' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
