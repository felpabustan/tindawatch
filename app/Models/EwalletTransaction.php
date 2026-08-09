<?php

namespace App\Models;

use App\Enums\EwalletTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $store_id
 * @property int $provider_id
 * @property EwalletTransactionType $type
 * @property int $amount
 * @property int $service_fee
 * @property string|null $customer_ref
 * @property int $processed_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Store $store
 * @property-read EwalletProvider|null $provider
 * @property-read User|null $processor
 */
class EwalletTransaction extends Model
{
    protected $fillable = [
        'store_id',
        'provider_id',
        'type',
        'amount',
        'service_fee',
        'customer_ref',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => EwalletTransactionType::class,
            'amount' => 'integer',
            'service_fee' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(EwalletProvider::class, 'provider_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
