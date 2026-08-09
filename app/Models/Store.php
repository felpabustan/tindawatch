<?php

namespace App\Models;

use App\Enums\StoreRole;
use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string|null $address
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $owner
 * @property-read StoreUser|null $pivot
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Category> $categories
 * @property-read Collection<int, Product> $products
 * @property-read Collection<int, Customer> $customers
 * @property-read Collection<int, Sale> $sales
 * @property-read Collection<int, EwalletProvider> $ewalletProviders
 * @property-read Collection<int, ActivityLog> $activityLogs
 */
class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'name',
        'address',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(StoreUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function ewalletProviders(): HasMany
    {
        return $this->hasMany(EwalletProvider::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function roleFor(User $user): ?StoreRole
    {
        $membership = $this->users()->where('user_id', $user->id)->first();

        if (! $membership) {
            return null;
        }

        /** @var StoreUser $pivot */
        $pivot = $membership->pivot;

        return StoreRole::from($pivot->role);
    }
}
