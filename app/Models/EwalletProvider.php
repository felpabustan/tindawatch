<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EwalletProvider extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'current_float',
        'low_float_threshold',
    ];

    protected function casts(): array
    {
        return [
            'current_float' => 'integer',
            'low_float_threshold' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EwalletTransaction::class, 'provider_id');
    }

    public function isLowFloat(): bool
    {
        return $this->current_float <= $this->low_float_threshold;
    }
}
