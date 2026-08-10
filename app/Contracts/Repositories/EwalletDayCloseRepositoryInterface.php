<?php

namespace App\Contracts\Repositories;

use App\Models\EwalletDayClose;
use App\Models\EwalletProvider;
use Carbon\CarbonInterface;

interface EwalletDayCloseRepositoryInterface
{
    public function findForProviderOnDate(EwalletProvider $provider, CarbonInterface $date): ?EwalletDayClose;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): EwalletDayClose;
}
