<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $store_id
 * @property int $provider_id
 * @property Carbon $business_date
 * @property int $opening_float
 * @property int $closing_float_before_fees
 * @property int $fees_settled
 * @property int $closing_float_after_fees
 * @property int $opening_cash
 * @property int $closing_cash
 * @property int $cash_in_total
 * @property int $cash_out_total
 * @property int $fees_total
 * @property int $txn_count
 * @property int $closed_by
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Store $store
 * @property-read EwalletProvider $provider
 * @property-read User $closer
 */
class EwalletDayClose extends Model
{
    protected $fillable = [
        'store_id',
        'provider_id',
        'business_date',
        'opening_float',
        'closing_float_before_fees',
        'fees_settled',
        'closing_float_after_fees',
        'opening_cash',
        'closing_cash',
        'cash_in_total',
        'cash_out_total',
        'fees_total',
        'txn_count',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'business_date' => 'date',
            'opening_float' => 'integer',
            'closing_float_before_fees' => 'integer',
            'fees_settled' => 'integer',
            'closing_float_after_fees' => 'integer',
            'opening_cash' => 'integer',
            'closing_cash' => 'integer',
            'cash_in_total' => 'integer',
            'cash_out_total' => 'integer',
            'fees_total' => 'integer',
            'txn_count' => 'integer',
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

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
