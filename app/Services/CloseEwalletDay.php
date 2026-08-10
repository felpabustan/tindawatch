<?php

namespace App\Services;

use App\Contracts\Repositories\EwalletDayCloseRepositoryInterface;
use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Models\EwalletDayClose;
use App\Models\EwalletProvider;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CloseEwalletDay
{
    public function __construct(
        private EwalletProviderRepositoryInterface $providers,
        private EwalletTransactionRepositoryInterface $transactions,
        private EwalletDayCloseRepositoryInterface $dayCloses,
        private ActivityLogger $activityLogger,
    ) {}

    public function handle(
        Store $store,
        EwalletProvider $provider,
        User $user,
        ?CarbonInterface $businessDate = null,
        ?string $notes = null,
    ): EwalletDayClose {
        if ($provider->store_id !== $store->id) {
            throw new InvalidArgumentException('Provider does not belong to store.');
        }

        $date = ($businessDate ?? now()->timezone(config('app.timezone')))->toDateString();
        $from = Carbon::parse($date, config('app.timezone'))->startOfDay();
        $to = Carbon::parse($date, config('app.timezone'))->endOfDay();

        return DB::transaction(function () use ($store, $provider, $user, $date, $from, $to, $notes) {
            $provider = $this->providers->findForUpdate($provider->id);

            if ($this->dayCloses->findForProviderOnDate($provider, $from) !== null) {
                throw new InvalidArgumentException('This e-wallet day is already closed.');
            }

            $totals = $this->transactions->totalsForProviderBetween($store, $provider->id, $from, $to);

            // Reverse today's amount deltas to recover opening balances before settle.
            $openingFloat = $provider->current_float + $totals['cash_in'] - $totals['cash_out'];
            $openingCash = $provider->cash_on_hand - $totals['cash_in'] + $totals['cash_out'];

            $closingFloatBeforeFees = $provider->current_float;
            $feesSettled = $totals['fees'];

            if ($closingFloatBeforeFees < $feesSettled) {
                throw new InvalidArgumentException('Insufficient float to settle today\'s fees.');
            }

            $closingFloatAfterFees = $closingFloatBeforeFees - $feesSettled;
            $closingCash = $provider->cash_on_hand;

            $this->providers->updateBalances($provider, $closingFloatAfterFees, $closingCash);

            $close = $this->dayCloses->create([
                'store_id' => $store->id,
                'provider_id' => $provider->id,
                'business_date' => $date,
                'opening_float' => $openingFloat,
                'closing_float_before_fees' => $closingFloatBeforeFees,
                'fees_settled' => $feesSettled,
                'closing_float_after_fees' => $closingFloatAfterFees,
                'opening_cash' => $openingCash,
                'closing_cash' => $closingCash,
                'cash_in_total' => $totals['cash_in'],
                'cash_out_total' => $totals['cash_out'],
                'fees_total' => $totals['fees'],
                'txn_count' => $totals['count'],
                'closed_by' => $user->id,
                'notes' => $notes,
            ]);

            $this->activityLogger->log($store, $user, 'ewallet.day_closed', $close, [
                'provider_id' => $provider->id,
                'business_date' => $date,
                'fees_settled' => $feesSettled,
                'closing_float' => $closingFloatAfterFees,
                'closing_cash' => $closingCash,
            ]);

            return $close;
        });
    }
}
