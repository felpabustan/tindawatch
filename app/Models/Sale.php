<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $store_id
 * @property int $user_id
 * @property int|null $customer_id
 * @property int $total_amount
 * @property PaymentMethod $payment_method
 * @property string|null $payment_reference
 * @property int|null $amount_tendered
 * @property int|null $change_amount
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $items_count
 * @property-read Store $store
 * @property-read User|null $user
 * @property-read Customer|null $customer
 * @property-read Collection<int, SaleItem> $items
 */
class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'customer_id',
        'total_amount',
        'payment_method',
        'payment_reference',
        'amount_tendered',
        'change_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'amount_tendered' => 'integer',
            'change_amount' => 'integer',
            'payment_method' => PaymentMethod::class,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
