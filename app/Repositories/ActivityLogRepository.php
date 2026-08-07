<?php

namespace App\Repositories;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogRepository implements ActivityLogRepositoryInterface
{
    public function create(Store $store, User $user, string $action, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return ActivityLog::query()->create([
            'store_id' => $store->id,
            'user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'properties' => $properties ?: null,
        ]);
    }
}
