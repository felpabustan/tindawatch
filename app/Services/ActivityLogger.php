<?php

namespace App\Services;

use App\Contracts\Repositories\ActivityLogRepositoryInterface;
use App\Models\ActivityLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function __construct(
        private ActivityLogRepositoryInterface $activityLogs,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function log(Store $store, User $user, string $action, ?Model $subject = null, array $properties = []): ActivityLog
    {
        return $this->activityLogs->create($store, $user, $action, $subject, $properties);
    }
}
