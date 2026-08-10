<?php

namespace App\Repositories;

use App\Contracts\Repositories\EwalletDayCloseRepositoryInterface;
use App\Models\EwalletDayClose;
use App\Models\EwalletProvider;
use Carbon\CarbonInterface;

class EwalletDayCloseRepository implements EwalletDayCloseRepositoryInterface
{
    public function findForProviderOnDate(EwalletProvider $provider, CarbonInterface $date): ?EwalletDayClose
    {
        return EwalletDayClose::query()
            ->where('provider_id', $provider->id)
            ->whereDate('business_date', $date->toDateString())
            ->first();
    }

    public function create(array $attributes): EwalletDayClose
    {
        return EwalletDayClose::query()->create($attributes);
    }
}
