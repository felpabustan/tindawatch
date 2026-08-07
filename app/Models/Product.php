<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'category_id',
        'name',
        'sku',
        'unit',
        'pieces_per_case',
        'cost_price',
        'sell_price',
        'stock_qty',
        'reorder_threshold',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'sell_price' => 'integer',
            'stock_qty' => 'integer',
            'reorder_threshold' => 'integer',
            'pieces_per_case' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty <= $this->reorder_threshold;
    }
}
