<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $store_id
 * @property int|null $category_id
 * @property string $name
 * @property string|null $sku
 * @property string|null $unit
 * @property int|null $pieces_per_case
 * @property int $cost_price
 * @property int $sell_price
 * @property int $stock_qty
 * @property int $reorder_threshold
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Store $store
 * @property-read Category|null $category
 * @property-read Collection<int, StockMovement> $stockMovements
 */
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
