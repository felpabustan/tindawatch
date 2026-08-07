<?php

namespace App\Contracts\Repositories;

use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface ActivityLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function create(Store $store, User $user, string $action, ?Model $subject = null, array $properties = []): ActivityLog;
}
