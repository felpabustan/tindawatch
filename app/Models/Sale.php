<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
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
