<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @property int $store_id
 * @property int $user_id
 * @property string $role
 */
class StoreUser extends Pivot
{
    protected $table = 'store_user';
}
