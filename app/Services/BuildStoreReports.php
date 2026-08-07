<?php

namespace App\Services;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\EwalletProviderRepositoryInterface;
use App\Contracts\Repositories\EwalletTransactionRepositoryInterface;
use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Repositories\SaleRepositoryInterface;
use App\Enums\EwalletTransactionType;
use App\Enums\PaymentMethod;
use App\Models\CreditPayment;
use App\Models\Customer;
use App\Models\EwalletProvider;
use App\Models\Product;
use App\Models\Store;
use App\Support\EwalletProviders;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class BuildStoreReports
{
    public function __construct(
        private SaleRepositoryInterface $sales,
        private ProductRepositoryInterface $products,
        private CustomerRepositoryInterface $customers,
        private EwalletProviderRepositoryInterface $ewalletProviders,
        private EwalletTransactionRepositoryInterface $ewalletTransactions,
    ) {}

    /**
     * @return array{
     *     sales: array{
     *         total_amount: int,
     *         count: int,
     *         by_payment: list<array{method: string, label: string, total: int, count: int}>,
     *         daily: list<array{date: string, label: string, total: int, count: int}>
     *     },
     *     bestsellers: list<array{product_id: int, name: string, quantity: int, revenue: int}>,
     *     inventory: array{
     *         products: list<array{id: int, name: string, sku: string|null, stock_qty: int, cost_price: int, sell_price: int, value: int, is_low_stock: bool}>,
     *         total_value: int,
     *         low_stock_count: int
     *     },
     *     utang: array{
     *         open_balance: int,
     *         customers: list<array{id: int, name: string, contact: string|null, credit_balance: int}>,
     *         payments: list<array{id: int, customer: string|null, amount: int, paid_at: string|null, received_by: string|null}>,
     *         payments_total: int
     *     },
     *     ewallet: array{
     *         providers: list<array{id: int, name: string, logo: string|null, current_float: int, is_low_float: bool}>,
     *         by_provider: list<array{provider: string, cash_in: int, cash_out: int, fees: int, count: int}>,
     *         totals: array{cash_in: int, cash_out: int, fees: int, count: int}
     *     }
     * }
     */
    public function handle(Store $store, CarbonInterface $from, CarbonInterface $to): array
    {
        $fromStart = Carbon::parse($from)->startOfDay();
        $toEnd = Carbon::parse($to)->endOfDay();

        return [
            'sales' => $this->salesReport($store, $fromStart, $toEnd),
            'bestsellers' => $this->sales->bestsellersBetween($store, $fromStart, $toEnd),
            'inventory' => $this->inventoryReport($store),
            'utang' => $this->utangReport($store, $fromStart, $toEnd),
            'ewallet' => $this->ewalletReport($store, $fromStart, $toEnd),
        ];
    }

    /**
     * @return array{
     *     total_amount: int,
     *     count: int,
     *     by_payment: list<array{method: string, label: string, total: int, count: int}>,
     *     daily: list<array{date: string, label: string, total: int, count: int}>
     * }
     */
    private function salesReport(Store $store, Carbon $from, Carbon $to): array
    {
        $report = $this->sales->reportBetween($store, $from, $to);

        return [
            'total_amount' => $report['total_amount'],
            'count' => $report['count'],
            'by_payment' => collect($report['by_payment'])->map(fn (array $row) => [
                ...$row,
                'label' => match (PaymentMethod::from($row['method'])) {
                    PaymentMethod::Cash => 'Cash',
                    PaymentMethod::Gcash => 'GCash',
                    PaymentMethod::Utang => 'Utang',
                },
            ])->all(),
            'daily' => collect($report['daily'])->map(fn (array $row) => [
                ...$row,
                'label' => Carbon::parse($row['date'])->format('M j'),
            ])->all(),
        ];
    }

    /**
     * @return array{
     *     products: list<array{id: int, name: string, sku: string|null, stock_qty: int, cost_price: int, sell_price: int, value: int, is_low_stock: bool}>,
     *     total_value: int,
     *     low_stock_count: int
     * }
     */
    private function inventoryReport(Store $store): array
    {
        $products = $this->products->inventoryForStore($store)
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock_qty' => $product->stock_qty,
                'cost_price' => $product->cost_price,
                'sell_price' => $product->sell_price,
                'value' => $product->stock_qty * $product->cost_price,
                'is_low_stock' => $product->stock_qty <= $product->reorder_threshold,
            ]);

        return [
            'products' => $products->all(),
            'total_value' => (int) $products->sum('value'),
            'low_stock_count' => $products->where('is_low_stock', true)->count(),
        ];
    }

    /**
     * @return array{
     *     open_balance: int,
     *     customers: list<array{id: int, name: string, contact: string|null, credit_balance: int}>,
     *     payments: list<array{id: int, customer: string|null, amount: int, paid_at: string|null, received_by: string|null}>,
     *     payments_total: int
     * }
     */
    private function utangReport(Store $store, Carbon $from, Carbon $to): array
    {
        $customers = $this->customers->withOpenBalance($store)
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'contact' => $customer->contact,
                'credit_balance' => $customer->credit_balance,
            ]);

        $payments = $this->customers->creditPaymentsBetween($store, $from, $to)
            ->map(fn (CreditPayment $payment) => [
                'id' => $payment->id,
                'customer' => $payment->customer?->name,
                'amount' => $payment->amount,
                'paid_at' => $payment->paid_at?->toDateTimeString(),
                'received_by' => $payment->receiver?->name,
            ]);

        return [
            'open_balance' => (int) $customers->sum('credit_balance'),
            'customers' => $customers->all(),
            'payments' => $payments->all(),
            'payments_total' => (int) $payments->sum('amount'),
        ];
    }

    /**
     * @return array{
     *     providers: list<array{id: int, name: string, logo: string|null, current_float: int, is_low_float: bool}>,
     *     by_provider: list<array{provider: string, cash_in: int, cash_out: int, fees: int, count: int}>,
     *     totals: array{cash_in: int, cash_out: int, fees: int, count: int}
     * }
     */
    private function ewalletReport(Store $store, Carbon $from, Carbon $to): array
    {
        $providers = $this->ewalletProviders
            ->listForStore($store)
            ->sortBy(fn (EwalletProvider $provider) => EwalletProviders::sortOrder($provider->name))
            ->values()
            ->map(fn (EwalletProvider $provider) => [
                'id' => $provider->id,
                'name' => $provider->name,
                'logo' => EwalletProviders::logoFor($provider->name),
                'current_float' => $provider->current_float,
                'is_low_float' => $provider->isLowFloat(),
            ]);

        $txRows = $this->ewalletTransactions->totalsByProviderBetween($store, $from, $to);

        $byProvider = [];
        foreach ($providers as $provider) {
            $byProvider[$provider['name']] = [
                'provider' => $provider['name'],
                'cash_in' => 0,
                'cash_out' => 0,
                'fees' => 0,
                'count' => 0,
            ];
        }

        foreach ($txRows as $row) {
            if (! isset($byProvider[$row->provider])) {
                $byProvider[$row->provider] = [
                    'provider' => $row->provider,
                    'cash_in' => 0,
                    'cash_out' => 0,
                    'fees' => 0,
                    'count' => 0,
                ];
            }

            $type = $row->type instanceof EwalletTransactionType
                ? $row->type->value
                : (string) $row->type;

            if ($type === EwalletTransactionType::CashIn->value) {
                $byProvider[$row->provider]['cash_in'] += (int) $row->amount;
            } else {
                $byProvider[$row->provider]['cash_out'] += (int) $row->amount;
            }

            $byProvider[$row->provider]['fees'] += (int) $row->fees;
            $byProvider[$row->provider]['count'] += (int) $row->count;
        }

        $rows = array_values($byProvider);

        return [
            'providers' => $providers->all(),
            'by_provider' => $rows,
            'totals' => [
                'cash_in' => (int) collect($rows)->sum('cash_in'),
                'cash_out' => (int) collect($rows)->sum('cash_out'),
                'fees' => (int) collect($rows)->sum('fees'),
                'count' => (int) collect($rows)->sum('count'),
            ],
        ];
    }
}
