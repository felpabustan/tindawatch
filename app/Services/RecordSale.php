<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Enums\PaymentMethod;
use App\Enums\StockMovementType;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RecordSale
{
    public function __construct(
        private SaleRepositoryInterface $sales,
        private ProductRepositoryInterface $products,
        private CustomerRepositoryInterface $customers,
        private RecordStockMovement $recordStockMovement,
        private ActivityLogger $activityLogger,
    ) {}

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function handle(
        Store $store,
        User $user,
        array $items,
        PaymentMethod $paymentMethod,
        ?Customer $customer = null,
        ?string $paymentReference = null,
        ?int $amountTendered = null,
    ): Sale {
        if ($items === []) {
            throw new InvalidArgumentException('Sale requires at least one item.');
        }

        if ($paymentMethod === PaymentMethod::Utang && ! $customer) {
            throw new InvalidArgumentException('Utang sales require a customer.');
        }

        if ($paymentMethod === PaymentMethod::Gcash) {
            $paymentReference = trim((string) $paymentReference);

            if ($paymentReference === '') {
                throw new InvalidArgumentException('GCash reference number is required.');
            }
        } else {
            $paymentReference = null;
        }

        if ($customer && $customer->store_id !== $store->id) {
            throw new InvalidArgumentException('Customer does not belong to store.');
        }

        return DB::transaction(function () use ($store, $user, $items, $paymentMethod, $customer, $paymentReference, $amountTendered) {
            $total = 0;
            $lineItems = [];

            foreach ($items as $item) {
                $product = $this->products->findForStoreForUpdate($store, (int) $item['product_id']);

                $qty = (int) $item['quantity'];

                if ($qty < 1) {
                    throw new InvalidArgumentException('Invalid quantity.');
                }

                if ($product->stock_qty < $qty) {
                    throw new InvalidArgumentException("Insufficient stock for {$product->name}.");
                }

                $lineTotal = $product->sell_price * $qty;
                $total += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'price_at_sale' => $product->sell_price,
                ];
            }

            $tendered = null;
            $change = null;

            if ($paymentMethod === PaymentMethod::Cash) {
                if ($amountTendered === null) {
                    throw new InvalidArgumentException('Cash tendered amount is required.');
                }

                if ($amountTendered < $total) {
                    throw new InvalidArgumentException('Cash tendered is less than the total.');
                }

                $tendered = $amountTendered;
                $change = $amountTendered - $total;
            }

            $sale = $this->sales->create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'customer_id' => $customer?->id,
                'total_amount' => $total,
                'payment_method' => $paymentMethod,
                'payment_reference' => $paymentReference,
                'amount_tendered' => $tendered,
                'change_amount' => $change,
            ]);

            foreach ($lineItems as $line) {
                $this->sales->createItem($sale, [
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'price_at_sale' => $line['price_at_sale'],
                ]);

                $this->recordStockMovement->handle(
                    $store,
                    $line['product'],
                    $user,
                    StockMovementType::Out,
                    $line['quantity'],
                    'sale:'.$sale->id,
                );
            }

            if ($paymentMethod === PaymentMethod::Utang) {
                $this->customers->incrementCreditBalance($customer, $total);
            }

            $this->activityLogger->log($store, $user, 'sale.created', $sale, [
                'total_amount' => $total,
                'payment_method' => $paymentMethod->value,
                'payment_reference' => $paymentReference,
                'amount_tendered' => $tendered,
                'change_amount' => $change,
            ]);

            return $this->sales->loadDetails($sale);
        });
    }
}
