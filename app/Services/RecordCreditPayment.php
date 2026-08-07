<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordCreditPayment
{
    public function __construct(
        private CustomerRepositoryInterface $customers,
        private ActivityLogger $activityLogger,
    ) {}

    public function handle(Store $store, Customer $customer, User $user, int $amount): CreditPayment
    {
        if ($customer->store_id !== $store->id) {
            throw new InvalidArgumentException('Customer does not belong to store.');
        }

        if ($amount < 1) {
            throw new InvalidArgumentException('Payment amount must be positive.');
        }

        if ($amount > $customer->credit_balance) {
            throw new InvalidArgumentException('Payment exceeds outstanding balance.');
        }

        return DB::transaction(function () use ($store, $customer, $user, $amount) {
            $customer = $this->customers->findForUpdate($customer->id);
            $this->customers->decrementCreditBalance($customer, $amount);

            $payment = $this->customers->createCreditPayment([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'received_by' => $user->id,
                'paid_at' => now(),
            ]);

            $this->activityLogger->log($store, $user, 'credit.payment', $payment, [
                'amount' => $amount,
                'remaining_balance' => $customer->fresh()->credit_balance,
            ]);

            return $payment;
        });
    }
}
