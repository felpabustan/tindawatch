<?php

namespace App\Services;

use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Enums\EwalletTransactionType;
use App\Models\EwalletProvider;
use App\Models\EwalletTransaction;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordEwalletTransaction
{
    public function __construct(
        private EwalletProviderRepositoryInterface $providers,
        private EwalletTransactionRepositoryInterface $transactions,
        private ActivityLogger $activityLogger,
    ) {}

    public function handle(
        Store $store,
        EwalletProvider $provider,
        User $user,
        EwalletTransactionType $type,
        int $amount,
        int $serviceFee = 0,
        ?string $customerRef = null,
    ): EwalletTransaction {
        if ($provider->store_id !== $store->id) {
            throw new InvalidArgumentException('Provider does not belong to store.');
        }

        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be positive.');
        }

        if ($serviceFee < 0) {
            throw new InvalidArgumentException('Service fee cannot be negative.');
        }

        return DB::transaction(function () use ($store, $provider, $user, $type, $amount, $serviceFee, $customerRef) {
            $provider = $this->providers->findForUpdate($provider->id);

            // Cash-in: customer pays cash, agent sends e-money → float down, cash up.
            // Cash-out: customer receives cash, agent receives e-money → float up, cash down.
            // Fees are stored only during the day; settled from float at close day.
            [$floatDelta, $cashDelta] = match ($type) {
                EwalletTransactionType::CashIn => [-$amount, $amount],
                EwalletTransactionType::CashOut => [$amount, -$amount],
            };

            $newFloat = $provider->current_float + $floatDelta;
            $newCash = $provider->cash_on_hand + $cashDelta;

            if ($newFloat < 0) {
                throw new InvalidArgumentException('Insufficient e-wallet float.');
            }

            if ($newCash < 0) {
                throw new InvalidArgumentException('Insufficient e-wallet cash on hand.');
            }

            $this->providers->updateBalances($provider, $newFloat, $newCash);

            $transaction = $this->transactions->create([
                'store_id' => $store->id,
                'provider_id' => $provider->id,
                'type' => $type,
                'amount' => $amount,
                'service_fee' => $serviceFee,
                'customer_ref' => $customerRef,
                'processed_by' => $user->id,
            ]);

            $this->activityLogger->log($store, $user, 'ewallet.'.$type->value, $transaction, [
                'amount' => $amount,
                'service_fee' => $serviceFee,
                'float' => $newFloat,
                'cash_on_hand' => $newCash,
            ]);

            return $transaction;
        });
    }
}
