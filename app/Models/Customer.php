<?php

namespace App\Models;

use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string|null $contact
 * @property int $credit_balance
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Store $store
 * @property-read Collection<int, Sale> $sales
 * @property-read Collection<int, CreditPayment> $creditPayments
 */
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'contact',
        'credit_balance',
    ];

    protected function casts(): array
    {
        return [
            'credit_balance' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function creditPayments(): HasMany
    {
        return $this->hasMany(CreditPayment::class);
    }
}
